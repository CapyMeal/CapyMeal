import { reactive, computed } from 'vue'

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

async function authRequest(path, body) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method:  'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body:    JSON.stringify(body),
  })

  const raw = await response.text()
  let data = null

  try {
    data = raw ? JSON.parse(raw) : null
  } catch {
    const error = new Error('El servidor devolvió una respuesta inválida.')
    error.status = response.status
    throw error
  }

  if (!response.ok) {
    const message = data?.message
      || Object.values(data?.errors || {})[0]?.[0]
      || 'Ocurrió un error.'
    const error = new Error(message)
    error.status = response.status
    throw error
  }

  return data
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
    await fetch(`${API_BASE_URL}/api/logout`, {
      method:  'POST',
      headers: {
        'Content-Type':  'application/json',
        'Authorization': `Bearer ${state.token}`,
      },
    }).catch(() => {})
  }
  clear()
}

export async function deleteAccount(password) {
  const response = await fetch(`${API_BASE_URL}/api/me`, {
    method:  'DELETE',
    headers: {
      'Content-Type':  'application/json',
      'Accept':        'application/json',
      'Authorization': `Bearer ${state.token}`,
    },
    body: JSON.stringify({ password }),
  })

  if (!response.ok) {
    const raw = await response.text()
    const data = raw ? JSON.parse(raw) : null
    const message = data?.message
      || Object.values(data?.errors || {})[0]?.[0]
      || 'No pude eliminar la cuenta.'
    const error = new Error(message)
    error.status = response.status
    throw error
  }

  // A diferencia de logout(), acá no se llama clear() si falla -- la
  // cuenta sigue existiendo y hay que poder reintentar sin perder la
  // sesión.
  clear()
}

export async function updateAvatar(avatar) {
  const response = await fetch(`${API_BASE_URL}/api/me/avatar`, {
    method:  'PUT',
    headers: {
      'Content-Type':  'application/json',
      'Accept':        'application/json',
      'Authorization': `Bearer ${state.token}`,
    },
    body: JSON.stringify({ avatar }),
  })

  const raw = await response.text()
  const data = raw ? JSON.parse(raw) : null

  if (!response.ok) {
    const message = data?.message
      || Object.values(data?.errors || {})[0]?.[0]
      || 'No pude actualizar el avatar.'
    const error = new Error(message)
    error.status = response.status
    throw error
  }

  persist(state.token, data)
  return data
}
