import { describe, it, expect, vi, beforeEach } from 'vitest'
import { getMealEntries, upsertMealEntry } from '../../../src/services/mealEntriesApi'
import { setCsrfToken } from '../../../src/services/httpClient'

// vi.mock se hoistea antes que los imports de arriba, así que esto reemplaza
// authStore para todo el archivo sin importar el orden en que se escriba.
vi.mock('../../../src/stores/authStore', () => ({
  handleUnauthorized: vi.fn(),
}))

describe('getMealEntries', () => {
  beforeEach(() => {
    global.fetch = vi.fn()
    // Simula que el token CSRF ya está cacheado para que ensureCsrfToken()
    // no dispare un fetch extra a /api/csrf-token y corra los índices de los
    // mocks de fetch de abajo.
    setCsrfToken('test')
  })

  it('sin from/to no agrega ningún query string', async () => {
    global.fetch.mockResolvedValue(new Response('[]', { status: 200 }))

    await getMealEntries()

    const [url] = global.fetch.mock.calls[0]
    expect(url).toMatch(/\/api\/meal-entries$/)
  })

  it('con from y to arma el query string con ambos', async () => {
    global.fetch.mockResolvedValue(new Response('[]', { status: 200 }))

    await getMealEntries({ from: '2026-01-01', to: '2026-01-31' })

    const [url] = global.fetch.mock.calls[0]
    expect(url).toContain('from=2026-01-01')
    expect(url).toContain('to=2026-01-31')
  })
})

describe('upsertMealEntry', () => {
  beforeEach(() => {
    global.fetch = vi.fn()
    setCsrfToken('test')
  })

  it('intenta PUT primero, y si el registro no existe (404) reintenta con POST', async () => {
    global.fetch
      .mockResolvedValueOnce(new Response('No encontrado', { status: 404 }))
      .mockResolvedValueOnce(new Response(JSON.stringify({ date: '2026-01-01', breakfast: 'Café' }), { status: 201 }))

    const result = await upsertMealEntry({ date: '2026-01-01', breakfast: 'Café' })

    expect(global.fetch).toHaveBeenCalledTimes(2)
    expect(global.fetch.mock.calls[0][1].method).toBe('PUT')
    expect(global.fetch.mock.calls[1][1].method).toBe('POST')
    expect(result).toEqual({ date: '2026-01-01', breakfast: 'Café' })
  })

  it('si el PUT falla con un error que no es 404, no reintenta con POST', async () => {
    global.fetch.mockResolvedValueOnce(
      new Response(JSON.stringify({ message: 'Error de servidor' }), { status: 500 })
    )

    await expect(upsertMealEntry({ date: '2026-01-01' })).rejects.toThrow('Error de servidor')
    expect(global.fetch).toHaveBeenCalledTimes(1)
  })
})
