// Cliente HTTP compartido: antes este mismo bloque (fetch -> chequear
// response.ok -> parsear JSON -> armar mensaje de error) estaba
// reimplementado por separado 4 veces (authStore.js x3, mealEntriesApi.js
// x1). Vive en su propio módulo, sin importar nada de authStore.js, para no
// recrear el mismo círculo de imports que handleUnauthorized ya evita con
// un import dinámico -- quien llama pasa el token y el callback de 401
// como parámetros en vez de que este archivo los busque solo.
export async function apiRequest(baseUrl, path, opts = {}) {
  const { token, onUnauthorized, ...options } = opts

  const response = await fetch(`${baseUrl}${path}`, {
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(options.headers || {}),
    },
    ...options,
  })

  if (!response.ok) {
    // Un 401 significa que el token ya no sirve: se dispara el manejo de
    // sesión expirada (limpia la sesión y redirige a login) *antes* de
    // tirar el error, para después seguir tirando siempre -- igual que
    // cualquier otro status no exitoso. Que quien llama nunca reciba un
    // valor "resuelto pero vacío" para el 401 evita un bug real: antes,
    // updateAvatar() habría seguido hasta persist(state.token, undefined)
    // si esta rama sólo devolvía sin lanzar.
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
