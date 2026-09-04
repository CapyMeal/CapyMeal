import { reactive, computed } from 'vue'
import { apiRequest } from '../services/httpClient'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8080'

const state = reactive({
  user: null,
})

export const isAuthenticated = computed(() => !!state.user)
export const currentUser     = computed(() => state.user)

function persist(user) {
  state.user = user
}

function clear() {
  state.user = null
}

// Se dispara una sola vez al cargar el módulo: la sesión ahora vive en una
// cookie httpOnly (Sanctum), invisible para este JS, así que no hay nada
// que leer de localStorage al arrancar -- hay que preguntarle al backend.
// El router (`router/index.js`) espera esta promesa antes de resolver el
// guard de rutas protegidas/de invitado. Un 401 acá es el caso normal de
// "nadie logueado todavía", no un error real -- se ignora en silencio en
// vez de pasar por handleUnauthorized (que redirige a login).
export const authReady = apiRequest(API_BASE_URL, '/api/me')
  .then((user) => { state.user = user })
  .catch(() => { state.user = null })

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
  persist(data.user)
  return data.user
}

export async function login({ email, password }) {
  const data = await authRequest('/api/login', { email, password })
  persist(data.user)
  return data.user
}

// Canjea el código de un solo uso que SocialAuthController::callback()
// generó (Google o Microsoft, llega como ?code= en /auth/{provider}/callback)
// por la sesión real -- mismo shape de respuesta que login()/register().
export async function exchangeSocialCode(provider, code) {
  const data = await authRequest(`/api/auth/${provider}/exchange`, { code })
  persist(data.user)
  return data.user
}

export async function logout() {
  // A diferencia del resto de las funciones de acá abajo, no pasa
  // onUnauthorized: si la sesión ya venció, no hay nada que "manejar" -- de
  // cualquier forma se va a limpiar el estado local ahora mismo.
  await apiRequest(API_BASE_URL, '/api/logout', { method: 'POST' }).catch(() => {})
  clear()
}

export async function deleteAccount(password) {
  // A diferencia de logout(), acá no se limpia la sesión si falla -- la
  // cuenta sigue existiendo y hay que poder reintentar sin perderla. Un 401
  // es la excepción: ahí apiRequest ya disparó handleUnauthorized() (limpia
  // y redirige) antes de tirar, así que no hay nada que reintentar.
  await apiRequest(API_BASE_URL, '/api/me', {
    method: 'DELETE',
    onUnauthorized: handleUnauthorized,
    body: JSON.stringify({ password }),
  })

  clear()
}

export async function updateAvatar(avatar) {
  const data = await apiRequest(API_BASE_URL, '/api/me/avatar', {
    method: 'PUT',
    onUnauthorized: handleUnauthorized,
    body: JSON.stringify({ avatar }),
  })

  persist(data)
  return data
}
