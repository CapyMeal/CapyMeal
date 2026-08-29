import { getToken, handleUnauthorized } from '../stores/authStore'
import { apiRequest } from './httpClient'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8080'

function request(path, options = {}) {
  return apiRequest(API_BASE_URL, path, {
    ...options,
    token: getToken(),
    onUnauthorized: handleUnauthorized,
  })
}

// Un fallo de red (fetch no llega a completarse, ej. sin conexión) tira un
// TypeError sin .status; cualquier error con .status vino de una respuesta
// HTTP real del servidor. Sirve para distinguir "estás sin conexión" de un
// error de verdad en las vistas que lo necesitan.
export function isNetworkError(error) {
  return error?.status === undefined
}

export function getMealEntry(date) {
  return request(`/api/meal-entries/${date}`)
}

// Export genérico para rutas públicas (sin prefijo forzado)
export function apiFetch(path, options = {}) {
  return request(`/api${path}`, options)
}

// Sin "from"/"to" trae el diario entero (comportamiento de siempre);
// pasarlos le permite a la vista pedir sólo el rango que va a mostrar en
// vez de traer todo y filtrar del lado del cliente.
function dateRangeQuery({ from, to } = {}) {
  const params = new URLSearchParams()

  if (from) {
    params.set('from', from)
  }

  if (to) {
    params.set('to', to)
  }

  const query = params.toString()
  return query ? `?${query}` : ''
}

export function getMealEntries({ from, to } = {}) {
  return request(`/api/meal-entries${dateRangeQuery({ from, to })}`)
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
  const url = `${API_BASE_URL}/api/meal-entries/export/pdf${dateRangeQuery({ from, to })}`

  const response = await fetch(url, {
    headers: {
      Accept: 'application/pdf',
      ...(getToken() ? { 'Authorization': `Bearer ${getToken()}` } : {}),
    },
  })

  if (!response.ok) {
    // No usa apiRequest (esto es un blob, no JSON), pero el 401 se maneja
    // igual que ahí: dispara handleUnauthorized() y sigue tirando, en vez
    // de devolver sin lanzar -- consistente con el resto de la app.
    if (response.status === 401) {
      await handleUnauthorized()
    }

    const text = await response.text()
    const error = new Error(`API ${response.status}: ${text}`)
    error.status = response.status
    throw error
  }

  return response.blob()
}
