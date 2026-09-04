import { describe, it, expect, vi, beforeEach } from 'vitest'
import { apiRequest } from '../../../src/services/httpClient'

const BASE_URL = 'http://localhost:8080'

describe('apiRequest', () => {
  beforeEach(() => {
    global.fetch = vi.fn()
    // Arranca cada test sin cookie CSRF, salvo que el test la simule.
    document.cookie = 'XSRF-TOKEN=; Max-Age=0'
  })

  it('siempre manda credentials: include (la sesión viaja por cookie httpOnly)', async () => {
    global.fetch.mockResolvedValue(new Response('{}', { status: 200 }))

    await apiRequest(BASE_URL, '/api/me')

    const [, options] = global.fetch.mock.calls[0]
    expect(options.credentials).toBe('include')
  })

  it('no agrega X-XSRF-TOKEN en un GET', async () => {
    global.fetch.mockResolvedValue(new Response('{}', { status: 200 }))

    await apiRequest(BASE_URL, '/api/me')

    const [, options] = global.fetch.mock.calls[0]
    expect(options.headers['X-XSRF-TOKEN']).toBeUndefined()
  })

  it('en un método mutante sin cookie CSRF previa, primero la pide a /sanctum/csrf-cookie', async () => {
    global.fetch.mockImplementationOnce(async () => {
      // Simula lo que haría el Set-Cookie real de esa respuesta.
      document.cookie = 'XSRF-TOKEN=recien%20emitida'
      return new Response(null, { status: 204 })
    })
    global.fetch.mockResolvedValueOnce(new Response('{}', { status: 200 }))

    await apiRequest(BASE_URL, '/api/logout', { method: 'POST' })

    expect(global.fetch).toHaveBeenCalledTimes(2)
    expect(global.fetch.mock.calls[0][0]).toBe(`${BASE_URL}/sanctum/csrf-cookie`)
    const [, options] = global.fetch.mock.calls[1]
    expect(options.headers['X-XSRF-TOKEN']).toBe('recien emitida')
  })

  it('en un método mutante con cookie CSRF ya presente, no la vuelve a pedir', async () => {
    document.cookie = 'XSRF-TOKEN=ya-existe'
    global.fetch.mockResolvedValue(new Response('{}', { status: 200 }))

    await apiRequest(BASE_URL, '/api/logout', { method: 'POST' })

    expect(global.fetch).toHaveBeenCalledTimes(1)
    const [, options] = global.fetch.mock.calls[0]
    expect(options.headers['X-XSRF-TOKEN']).toBe('ya-existe')
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
