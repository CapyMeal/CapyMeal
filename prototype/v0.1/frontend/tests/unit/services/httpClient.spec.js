import { describe, it, expect, vi, beforeEach } from 'vitest'
import { apiRequest, setCsrfToken } from '../../../src/services/httpClient'

const BASE_URL = 'http://localhost:8080'

describe('apiRequest', () => {
  beforeEach(() => {
    global.fetch = vi.fn()
    setCsrfToken(null)
  })

  it('siempre manda credentials: include (la sesión viaja por cookie httpOnly)', async () => {
    global.fetch.mockResolvedValue(new Response('{}', { status: 200 }))

    await apiRequest(BASE_URL, '/api/me')

    const [, options] = global.fetch.mock.calls[0]
    expect(options.credentials).toBe('include')
  })

  it('no agrega X-CSRF-TOKEN en un GET', async () => {
    global.fetch.mockResolvedValue(new Response('{}', { status: 200 }))

    await apiRequest(BASE_URL, '/api/me')

    const [, options] = global.fetch.mock.calls[0]
    expect(options.headers['X-CSRF-TOKEN']).toBeUndefined()
  })

  it('en un método mutante sin token CSRF cacheado, primero lo pide a /api/csrf-token', async () => {
    global.fetch.mockResolvedValueOnce(
      new Response(JSON.stringify({ token: 'token-recien-pedido' }), { status: 200 })
    )
    global.fetch.mockResolvedValueOnce(new Response('{}', { status: 200 }))

    await apiRequest(BASE_URL, '/api/logout', { method: 'POST' })

    expect(global.fetch).toHaveBeenCalledTimes(2)
    expect(global.fetch.mock.calls[0][0]).toBe(`${BASE_URL}/api/csrf-token`)
    const [, options] = global.fetch.mock.calls[1]
    expect(options.headers['X-CSRF-TOKEN']).toBe('token-recien-pedido')
  })

  it('en un método mutante con token CSRF ya cacheado, no lo vuelve a pedir', async () => {
    setCsrfToken('ya-cacheado')
    global.fetch.mockResolvedValue(new Response('{}', { status: 200 }))

    await apiRequest(BASE_URL, '/api/logout', { method: 'POST' })

    expect(global.fetch).toHaveBeenCalledTimes(1)
    const [, options] = global.fetch.mock.calls[0]
    expect(options.headers['X-CSRF-TOKEN']).toBe('ya-cacheado')
  })

  it('devuelve null para un 204 en vez de intentar parsear el body', async () => {
    global.fetch.mockResolvedValue(new Response(null, { status: 204 }))

    const result = await apiRequest(BASE_URL, '/api/logout')

    expect(result).toBeNull()
  })

  it('arma el mensaje de error a partir de "message" cuando el servidor lo manda', async () => {
    global.fetch.mockResolvedValue(
      new Response(JSON.stringify({ message: 'Ocurrió un problema.' }), { status: 500 })
    )

    await expect(apiRequest(BASE_URL, '/api/algo')).rejects.toThrow('Ocurrió un problema.')
  })

  it('arma el mensaje de error a partir del primer campo de "errors" si no hay "message"', async () => {
    global.fetch.mockResolvedValue(
      new Response(JSON.stringify({ errors: { password: ['La contraseña no es correcta.'] } }), { status: 422 })
    )

    await expect(apiRequest(BASE_URL, '/api/me')).rejects.toThrow('La contraseña no es correcta.')
  })

  it('en un 401, dispara onUnauthorized y siempre termina lanzando (nunca resuelve en silencio)', async () => {
    global.fetch.mockResolvedValue(new Response('{}', { status: 401 }))
    const onUnauthorized = vi.fn()

    await expect(apiRequest(BASE_URL, '/api/me', { onUnauthorized })).rejects.toThrow()

    expect(onUnauthorized).toHaveBeenCalledTimes(1)
  })
})
