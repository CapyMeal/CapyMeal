import { clearAuthState, getToken } from '../stores/authStore'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8080'
const REQUEST_TIMEOUT_MS = 60000
const PDF_TIMEOUT_MS = 180000

async function fetchWithTimeout(url, options = {}, timeoutMs = REQUEST_TIMEOUT_MS) {
  const controller = new AbortController()
  const timeoutId = setTimeout(() => controller.abort(), timeoutMs)

  try {
    return await fetch(url, {
      ...options,
      signal: controller.signal,
    })
  } catch (error) {
    if (error.name === 'AbortError') {
      const timeoutError = new Error('La conexión está tardando demasiado. Intentá nuevamente.')
      timeoutError.status = 0
      throw timeoutError
    }

    const networkError = new Error('No pude conectar con el servidor. Intentá nuevamente.')
    networkError.status = 0
    throw networkError
  } finally {
    clearTimeout(timeoutId)
  }
}

async function request(path, options = {}, timeoutMs = REQUEST_TIMEOUT_MS) {
  const token = getToken()

  const response = await fetchWithTimeout(`${API_BASE_URL}${path}`, {
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(options.headers || {}),
    },
    ...options,
  }, timeoutMs)

  if (!response.ok) {
    const text = await response.text()
    if (response.status === 401) {
      clearAuthState()
      window.location.href = '/login'
      const error = new Error('Tu sesión venció. Volvé a iniciar sesión.')
      error.status = 401
      throw error
    }
    const error = new Error(`API ${response.status}: ${text}`)
    error.status = response.status
    throw error
  }

  if (response.status === 204) {
    return null
  }

  return response.json()
}

export function getMealEntry(date) {
  return request(`/api/meal-entries/${date}`)
}

// Export genérico para rutas públicas (sin prefijo forzado)
export function apiFetch(path, options = {}) {
  return request(`/api${path}`, options)
}

export function getMealEntries() {
  return request('/api/meal-entries')
}

export async function upsertMealEntry(payload) {
  try {
    return await request(`/api/meal-entries/${payload.date}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    })
  } catch (error) {
    if (error.status !== 404) {
      throw error
    }

    return request('/api/meal-entries', {
      method: 'POST',
      body: JSON.stringify(payload),
    })
  }
}

export function deleteMealEntry(date) {
  return request(`/api/meal-entries/${date}`, { method: 'DELETE' })
}

export async function exportMealEntriesPdf({ from, to }) {
  const params = new URLSearchParams()

  if (from) {
    params.set('from', from)
  }

  if (to) {
    params.set('to', to)
  }

  const query = params.toString()
  const url = `${API_BASE_URL}/api/meal-entries/export/pdf${query ? `?${query}` : ''}`

  const response = await fetchWithTimeout(url, {
    headers: {
      Accept: 'application/pdf',
      ...(getToken() ? { Authorization: `Bearer ${getToken()}` } : {}),
    },
  }, PDF_TIMEOUT_MS)

  if (!response.ok) {
    const text = await response.text()
    if (response.status === 401) {
      clearAuthState()
      window.location.href = '/login'
      const error = new Error('Tu sesión venció. Volvé a iniciar sesión.')
      error.status = 401
      throw error
    }
    const error = new Error(`API ${response.status}: ${text}`)
    error.status = response.status
    throw error
  }

  return response.blob()
}
