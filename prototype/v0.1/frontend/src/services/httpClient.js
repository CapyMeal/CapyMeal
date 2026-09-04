// "X-CSRF-TOKEN" y no "X-XSRF-TOKEN": Laravel intenta desencriptar el valor
// de X-XSRF-TOKEN (asumiendo que es el string ya encriptado de la cookie que
// pone EncryptCookies), mientras que X-CSRF-TOKEN se compara tal cual contra
// session()->token() -- justo lo que hace falta acá, porque el token viaja
// en texto plano en el body de la respuesta (ver el comentario más abajo),
// no como el valor encriptado de una cookie real.
const CSRF_HEADER_NAME = 'X-CSRF-TOKEN'
const MUTATING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE']

// El patrón habitual de Sanctum (cookie XSRF-TOKEN legible por JS) no sirve
// acá: frontend (Vercel) y backend (Render) son dominios sin ninguna
// relación, y una cookie que puso capymeal.onrender.com es directamente
// invisible para el JS que corre en capy-meal.vercel.app -- document.cookie
// nunca la va a mostrar, sin importar SameSite/Secure/lo que sea (es una
// restricción de origen para lectura vía JS, algo distinto de si la cookie
// viaja o no en el request). Por eso el token viaja en el body de la
// respuesta en vez de en una cookie -- ver AuthController::csrfToken() y el
// campo "csrfToken" que login/register/exchange devuelven.
let cachedCsrfToken = null
let csrfTokenPromise = null

export function setCsrfToken(token) {
  cachedCsrfToken = token
}

// Cachea la promesa (no sólo el valor) para no disparar N requests en
// paralelo si varias llamadas mutantes arrancan a la vez antes de que la
// primera termine de traer el token.
function ensureCsrfToken(baseUrl) {
  if (cachedCsrfToken) return Promise.resolve()

  if (!csrfTokenPromise) {
    csrfTokenPromise = fetch(`${baseUrl}/api/csrf-token`, { credentials: 'include' })
      .then((response) => response.json())
      .then((data) => { cachedCsrfToken = data.token })
      .finally(() => { csrfTokenPromise = null })
  }

  return csrfTokenPromise
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
    await ensureCsrfToken(baseUrl)
  }

  const response = await fetch(`${baseUrl}${path}`, {
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(isMutating ? { [CSRF_HEADER_NAME]: cachedCsrfToken } : {}),
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
