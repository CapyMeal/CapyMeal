import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import GoogleCallbackView from '../../../src/views/GoogleCallbackView.vue'
import { exchangeGoogleCode } from '../../../src/stores/authStore'

vi.mock('../../../src/stores/authStore', () => ({
  exchangeGoogleCode: vi.fn(),
}))

const mockRoute = { query: {} }
const replace = vi.fn()
const push = vi.fn()

vi.mock('vue-router', () => ({
  useRoute: () => mockRoute,
  useRouter: () => ({ replace, push }),
}))

function mountGoogleCallbackView() {
  return mount(GoogleCallbackView, {
    global: {
      stubs: {
        AuthLayout: { template: '<div><slot /></div>' },
        AuthCard: { template: '<div><slot /></div>' },
        CapyLoader: true,
        CapyButton: { template: '<button type="button"><slot /></button>' },
        VAlert: { template: '<div><slot /></div>' },
      },
    },
  })
}

describe('GoogleCallbackView', () => {
  beforeEach(() => {
    mockRoute.query = {}
    replace.mockReset()
    push.mockReset()
    exchangeGoogleCode.mockReset()
  })

  it('canjea el código y redirige a /hoy cuando hay un código en la URL', async () => {
    mockRoute.query = { code: 'un-codigo' }
    exchangeGoogleCode.mockResolvedValue({ id: 1, name: 'Mercedes' })

    mountGoogleCallbackView()
    await flushPromises()

    expect(exchangeGoogleCode).toHaveBeenCalledWith('un-codigo')
    expect(replace).toHaveBeenCalledWith('/hoy')
  })

  it('muestra un error y no llama al store si no hay código en la URL', async () => {
    mockRoute.query = {}

    const wrapper = mountGoogleCallbackView()
    await flushPromises()

    expect(exchangeGoogleCode).not.toHaveBeenCalled()
    expect(replace).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('No pudimos completar el ingreso con Google.')
  })

  it('muestra el mensaje de error del store cuando el canje falla', async () => {
    mockRoute.query = { code: 'codigo-vencido' }
    exchangeGoogleCode.mockRejectedValue(new Error('Este enlace ya no es válido. Iniciá sesión de nuevo.'))

    const wrapper = mountGoogleCallbackView()
    await flushPromises()

    expect(replace).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Este enlace ya no es válido. Iniciá sesión de nuevo.')
  })
})
