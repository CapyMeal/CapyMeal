import { describe, it, expect, vi, beforeEach } from 'vitest'
import { apiRequest } from '../../../src/services/httpClient'

const BASE_URL = 'http://localhost:8080'

describe('apiRequest', () => {
  beforeEach(() => {
    global.fetch = vi.fn()
  })

  it('agrega el header Authorization sólo cuando se pasa un token', async () => {
    global.fetch.mockResolvedValue(new Response('{}', { status: 200 }))

    await apiRequest(BASE_URL, '/api/me', { token: 'abc123' })

    const [, options] = global.fetch.mock.calls[0]
    expect(options.headers.Authorization).toBe('Bearer abc123')
  })

  it('no agrega Authorization si no se pasa token', async () => {
    global.fetch.mockResolvedValue(new Response('{}', { status: 200 }))

    await apiRequest(BASE_URL, '/api/register')

    const [, options] = global.fetch.mock.calls[0]
    expect(options.headers.Authorization).toBeUndefined()
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
