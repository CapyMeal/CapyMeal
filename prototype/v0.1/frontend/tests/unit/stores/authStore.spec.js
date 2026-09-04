import { describe, it, expect, vi, beforeEach, beforeAll } from 'vitest'
import { login, exchangeSocialCode, currentUser, authReady, handleUnauthorized } from '../../../src/stores/authStore'

const pushMock = vi.fn()
const mockRouter = {
  currentRoute: { value: { name: 'today' } },
  push: pushMock,
}

// handleUnauthorized importa el router de forma dinámica (para evitar el
// círculo con router/index.js, que importa este mismo store) -- se mockea
// igual, Vitest intercepta el import dinámico por la ruta resuelta.
vi.mock('../../../src/router', () => ({ default: mockRouter }))

describe('authStore', () => {
  // authStore dispara un GET a /api/me apenas se importa el módulo (para
  // saber si la cookie de sesión, si existe, todavía es válida), con
  // cualquier fetch global que hubiera en ese momento (no el mock de acá
  // abajo, que recién se instala en el primer beforeEach). Hay que esperar
  // a que esa promesa asiente antes de correr los tests, porque si resuelve
  // tarde pisa el state.user que haya dejado el login() de un test anterior.
  beforeAll(async () => {
    await authReady
  })

  beforeEach(() => {
    pushMock.mockClear()
    mockRouter.currentRoute.value.name = 'today'
    global.fetch = vi.fn()
    document.cookie = 'XSRF-TOKEN=test'
  })

  describe('login', () => {
    it('guarda el usuario tras un login exitoso', async () => {
      global.fetch.mockResolvedValue(
        new Response(JSON.stringify({ user: { id: 1, name: 'Mercedes' } }), { status: 200 })
      )

      const user = await login({ email: 'mer@example.com', password: '123' })

      expect(user).toEqual({ id: 1, name: 'Mercedes' })
      expect(currentUser.value).toEqual({ id: 1, name: 'Mercedes' })
    })

    it('tira un error con el mensaje del servidor y no toca la sesión si las credenciales son incorrectas', async () => {
      global.fetch.mockResolvedValue(
        new Response(JSON.stringify({ message: 'El email o la contraseña son incorrectos.' }), { status: 422 })
      )

      // Se compara contra el valor de antes (no se asume "null") porque
      // authStore es un módulo singleton: su estado interno persiste entre
      // tests de este archivo, no se reinicia solo entre "it()".
      const userAntes = currentUser.value

      await expect(login({ email: 'mer@example.com', password: 'mal' }))
        .rejects.toThrow('El email o la contraseña son incorrectos.')

      expect(currentUser.value).toEqual(userAntes)
    })
  })

  describe.each(['google', 'microsoft'])('exchangeSocialCode (%s)', (provider) => {
    it('guarda el usuario tras canjear el código, contra la URL del proveedor correcto', async () => {
      global.fetch.mockResolvedValue(
        new Response(JSON.stringify({ user: { id: 2, name: 'Mercedes' } }), { status: 200 })
      )

      const user = await exchangeSocialCode(provider, 'un-codigo')

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining(`/api/auth/${provider}/exchange`),
        expect.anything()
      )
      expect(user).toEqual({ id: 2, name: 'Mercedes' })
      expect(currentUser.value).toEqual({ id: 2, name: 'Mercedes' })
    })

    it('tira un error con el mensaje del servidor y no toca la sesión si el código no es válido', async () => {
      global.fetch.mockResolvedValue(
        new Response(JSON.stringify({ message: 'Este enlace ya no es válido. Iniciá sesión de nuevo.' }), { status: 422 })
      )

      const userAntes = currentUser.value

      await expect(exchangeSocialCode(provider, 'codigo-vencido'))
        .rejects.toThrow('Este enlace ya no es válido. Iniciá sesión de nuevo.')

      expect(currentUser.value).toEqual(userAntes)
    })
  })

  describe('handleUnauthorized', () => {
    it('limpia la sesión y redirige a login con expired=1', async () => {
      global.fetch.mockResolvedValue(
        new Response(JSON.stringify({ user: { id: 1 } }), { status: 200 })
      )
      await login({ email: 'mer@example.com', password: '123' })
      expect(currentUser.value).toEqual({ id: 1 })

      await handleUnauthorized()

      expect(currentUser.value).toBeNull()
      expect(pushMock).toHaveBeenCalledWith({ name: 'login', query: { expired: '1' } })
    })

    it('no redirige de nuevo si ya está en la pantalla de login', async () => {
      mockRouter.currentRoute.value.name = 'login'

      await handleUnauthorized()

      expect(pushMock).not.toHaveBeenCalled()
    })
  })
})
