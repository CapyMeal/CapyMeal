import { getToken } from '../stores/authStore'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8080'

async function request(path, options = {}) {
  const token = getToken()

  const response = await fetch(`${API_BASE_URL}${path}`, {
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
      ...(options.headers || {}),
    },
    ...options,
  })

  if (!response.ok) {
    const text = await response.text()
    let data = null

    try {
      data = text ? JSON.parse(text) : null
    } catch {
      // respuesta no-JSON (ej. error de servidor/proxy), seguimos con data = null
    }

    const message = data?.message
      || Object.values(data?.errors || {})[0]?.[0]
      || `API ${response.status}: ${text}`
    const error = new Error(message)
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

  const response = await fetch(url, {
    headers: {
      Accept: 'application/pdf',
      ...(getToken() ? { 'Authorization': `Bearer ${getToken()}` } : {}),
    },
  })

  if (!response.ok) {
    const text = await response.text()
    const error = new Error(`API ${response.status}: ${text}`)
    error.status = response.status
    throw error
  }

  return response.blob()
}
