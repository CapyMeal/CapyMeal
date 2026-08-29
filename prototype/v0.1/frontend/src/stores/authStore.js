import { reactive, computed } from 'vue'
import { apiRequest } from '../services/httpClient'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8080'

const state = reactive({
  token: localStorage.getItem('capymeal-token') || null,
  user:  JSON.parse(localStorage.getItem('capymeal-user') || 'null'),
})

export const isAuthenticated = computed(() => !!state.token)
export const currentUser     = computed(() => state.user)

export function getToken() {
  return state.token
}

function persist(token, user) {
  state.token = token
  state.user  = user
  localStorage.setItem('capymeal-token', token)
  localStorage.setItem('capymeal-user', JSON.stringify(user))
}

function clear() {
  state.token = null
  state.user  = null
  localStorage.removeItem('capymeal-token')
  localStorage.removeItem('capymeal-user')
}

// Un 401 de la API significa que el token ya no sirve (expiró o se revocó
// en otro lado) -- a diferencia de un logout manual, acá hay que avisar
// por qué se cerró la sesión. Import dinámico para no crear una
// dependencia circular con router/index.js (que importa este archivo).
export async function handleUnauthorized() {
  clear()

  const { default: router } = await import('../router')

  if (router.currentRoute.value.name !== 'login') {
    router.push({ name: 'login', query: { expired: '1' } })
  }
}

function authRequest(path, body) {
  return apiRequest(API_BASE_URL, path, {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function register({ name, email, password, password_confirmation }) {
  const data = await authRequest('/api/register', { name, email, password, password_confirmation })
  persist(data.token, data.user)
  return data.user
}

export async function login({ email, password }) {
  const data = await authRequest('/api/login', { email, password })
  persist(data.token, data.user)
  return data.user
}

export async function logout() {
  if (state.token) {
    // A diferencia del resto de las funciones de acá abajo, no pasa
    // onUnauthorized: si el token ya venció, no hay nada que "manejar" --
    // de cualquier forma se va a limpiar la sesión local ahora mismo.
    await apiRequest(API_BASE_URL, '/api/logout', {
      method: 'POST',
      token: state.token,
    }).catch(() => {})
  }
  clear()
}

export async function deleteAccount(password) {
  // A diferencia de logout(), acá no se limpia la sesión si falla -- la
  // cuenta sigue existiendo y hay que poder reintentar sin perderla. Un 401
  // es la excepción: ahí apiRequest ya disparó handleUnauthorized() (limpia
  // y redirige) antes de tirar, así que no hay nada que reintentar.
  await apiRequest(API_BASE_URL, '/api/me', {
    method: 'DELETE',
    token: state.token,
    onUnauthorized: handleUnauthorized,
    body: JSON.stringify({ password }),
  })

  clear()
}

export async function updateAvatar(avatar) {
  const data = await apiRequest(API_BASE_URL, '/api/me/avatar', {
    method: 'PUT',
    token: state.token,
    onUnauthorized: handleUnauthorized,
    body: JSON.stringify({ avatar }),
  })

  persist(state.token, data)
  return data
}
