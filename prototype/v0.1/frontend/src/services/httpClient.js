const CSRF_COOKIE_NAME = 'XSRF-TOKEN'
const CSRF_HEADER_NAME = 'X-XSRF-TOKEN'
const MUTATING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE']

function readCsrfCookie() {
  const match = document.cookie.match(new RegExp(`(?:^|; )${CSRF_COOKIE_NAME}=([^;]*)`))
  return match ? decodeURIComponent(match[1]) : null
}

// Sanctum exige esta cookie (legible por JS a propósito, es la mitad
// "double submit" del esquema CSRF) antes de aceptar cualquier request que
// mute estado -- sin pedirla primero, login/register/etc. responden 419.
// Cachea la promesa en vez de por-cookie-presente para no disparar N
// requests en paralelo si varias llamadas mutantes arrancan a la vez antes
// de que la primera termine de setear la cookie.
let csrfCookiePromise = null

function ensureCsrfCookie(baseUrl) {
  if (readCsrfCookie()) return Promise.resolve()

  if (!csrfCookiePromise) {
    csrfCookiePromise = fetch(`${baseUrl}/sanctum/csrf-cookie`, { credentials: 'include' })
      .finally(() => { csrfCookiePromise = null })
  }

  return csrfCookiePromise
}

// Cliente HTTP compartido: antes este mismo bloque (fetch -> chequear
// response.ok -> parsear JSON -> armar mensaje de error) estaba
// reimplementado por separado 4 veces (authStore.js x3, mealEntriesApi.js
// x1). Vive en su propio módulo, sin importar nada de authStore.js, para no
// recrear el mismo círculo de imports que handleUnauthorized ya evita con
// un import dinámico -- quien llama pasa el callback de 401 como parámetro
// en vez de que este archivo lo busque solo. La sesión viaja sola via
// cookie httpOnly (credentials: 'include'), no hace falta que quien llama
// pase ningún token.
export async function apiRequest(baseUrl, path, opts = {}) {
  const { onUnauthorized, ...options } = opts
  const method = (options.method || 'GET').toUpperCase()
  const isMutating = MUTATING_METHODS.includes(method)

  if (isMutating) {
    await ensureCsrfCookie(baseUrl)
  }

  const response = await fetch(`${baseUrl}${path}`, {
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(isMutating ? { [CSRF_HEADER_NAME]: readCsrfCookie() } : {}),
      ...(options.headers || {}),
    },
    ...options,
  })

  if (!response.ok) {
    // Un 401 significa que la sesión ya no sirve: se dispara el manejo de
    // sesión expirada (limpia la sesión y redirige a login) *antes* de
    // tirar el error, para después seguir tirando siempre -- igual que
    // cualquier otro status no exitoso. Que quien llama nunca reciba un
    // valor "resuelto pero vacío" para el 401 evita un bug real: antes,
    // updateAvatar() habría seguido hasta persist(undefined) si esta rama
    // sólo devolvía sin lanzar.
    if (response.status === 401 && onUnauthorized) {
      await onUnauthorized()
    }

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
