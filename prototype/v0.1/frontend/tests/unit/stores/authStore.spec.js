import { describe, it, expect, vi, beforeEach } from 'vitest'
import { login, exchangeSocialCode, getToken, handleUnauthorized } from '../../../src/stores/authStore'

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
  beforeEach(() => {
    localStorage.clear()
    pushMock.mockClear()
    mockRouter.currentRoute.value.name = 'today'
    global.fetch = vi.fn()
  })

  describe('login', () => {
    it('guarda el token y el usuario en localStorage tras un login exitoso', async () => {
      global.fetch.mockResolvedValue(
        new Response(JSON.stringify({ token: 'nuevo-token', user: { id: 1, name: 'Mercedes' } }), { status: 200 })
      )

      const user = await login({ email: 'mer@example.com', password: '123' })

      expect(user).toEqual({ id: 1, name: 'Mercedes' })
      expect(getToken()).toBe('nuevo-token')
      expect(localStorage.getItem('capymeal-token')).toBe('nuevo-token')
    })

    it('tira un error con el mensaje del servidor y no toca el token si las credenciales son incorrectas', async () => {
      global.fetch.mockResolvedValue(
        new Response(JSON.stringify({ message: 'El email o la contraseña son incorrectos.' }), { status: 422 })
      )

      // Se compara contra el valor de antes (no se asume "null") porque
      // authStore es un módulo singleton: su estado interno persiste entre
      // tests de este archivo, no se reinicia solo entre "it()".
      const tokenAntes = getToken()

      await expect(login({ email: 'mer@example.com', password: 'mal' }))
        .rejects.toThrow('El email o la contraseña son incorrectos.')

      expect(getToken()).toBe(tokenAntes)
    })
  })

  describe.each(['google', 'microsoft'])('exchangeSocialCode (%s)', (provider) => {
    it('guarda el token y el usuario en localStorage tras canjear el código, contra la URL del proveedor correcto', async () => {
      global.fetch.mockResolvedValue(
        new Response(JSON.stringify({ token: 'token-social', user: { id: 2, name: 'Mercedes' } }), { status: 200 })
      )

      const user = await exchangeSocialCode(provider, 'un-codigo')

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining(`/api/auth/${provider}/exchange`),
        expect.anything()
      )
      expect(user).toEqual({ id: 2, name: 'Mercedes' })
      expect(getToken()).toBe('token-social')
      expect(localStorage.getItem('capymeal-token')).toBe('token-social')
    })

    it('tira un error con el mensaje del servidor y no toca el token si el código no es válido', async () => {
      global.fetch.mockResolvedValue(
        new Response(JSON.stringify({ message: 'Este enlace ya no es válido. Iniciá sesión de nuevo.' }), { status: 422 })
      )

      const tokenAntes = getToken()

      await expect(exchangeSocialCode(provider, 'codigo-vencido'))
        .rejects.toThrow('Este enlace ya no es válido. Iniciá sesión de nuevo.')

      expect(getToken()).toBe(tokenAntes)
    })
  })

  describe('handleUnauthorized', () => {
    it('limpia la sesión y redirige a login con expired=1', async () => {
      global.fetch.mockResolvedValue(
        new Response(JSON.stringify({ token: 'token-vencido', user: { id: 1 } }), { status: 200 })
      )
      await login({ email: 'mer@example.com', password: '123' })
      expect(getToken()).toBe('token-vencido')

      await handleUnauthorized()

      expect(getToken()).toBeNull()
      expect(localStorage.getItem('capymeal-token')).toBeNull()
      expect(pushMock).toHaveBeenCalledWith({ name: 'login', query: { expired: '1' } })
    })

    it('no redirige de nuevo si ya está en la pantalla de login', async () => {
      mockRouter.currentRoute.value.name = 'login'

      await handleUnauthorized()

      expect(pushMock).not.toHaveBeenCalled()
    })
  })
})
