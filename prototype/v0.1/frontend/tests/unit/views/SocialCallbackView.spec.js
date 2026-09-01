import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import SocialCallbackView from '../../../src/views/SocialCallbackView.vue'
import { exchangeSocialCode } from '../../../src/stores/authStore'

vi.mock('../../../src/stores/authStore', () => ({
  exchangeSocialCode: vi.fn(),
}))

const mockRoute = { query: {} }
const replace = vi.fn()
const push = vi.fn()

vi.mock('vue-router', () => ({
  useRoute: () => mockRoute,
  useRouter: () => ({ replace, push }),
}))

function mountSocialCallbackView(provider) {
  return mount(SocialCallbackView, {
    props: { provider },
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

describe.each([
  ['google', 'Google'],
  ['microsoft', 'Microsoft'],
])('SocialCallbackView (%s)', (provider, label) => {
  beforeEach(() => {
    mockRoute.query = {}
    replace.mockReset()
    push.mockReset()
    exchangeSocialCode.mockReset()
  })

  it('canjea el código y redirige a /hoy cuando hay un código en la URL', async () => {
    mockRoute.query = { code: 'un-codigo' }
    exchangeSocialCode.mockResolvedValue({ id: 1, name: 'Mercedes' })

    mountSocialCallbackView(provider)
    await flushPromises()

    expect(exchangeSocialCode).toHaveBeenCalledWith(provider, 'un-codigo')
    expect(replace).toHaveBeenCalledWith('/hoy')
  })

  it('muestra un error y no llama al store si no hay código en la URL', async () => {
    mockRoute.query = {}

    const wrapper = mountSocialCallbackView(provider)
    await flushPromises()

    expect(exchangeSocialCode).not.toHaveBeenCalled()
    expect(replace).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain(`No pudimos completar el ingreso con ${label}.`)
  })

  it('muestra el mensaje de error del store cuando el canje falla', async () => {
    mockRoute.query = { code: 'codigo-vencido' }
    exchangeSocialCode.mockRejectedValue(new Error('Este enlace ya no es válido. Iniciá sesión de nuevo.'))

    const wrapper = mountSocialCallbackView(provider)
    await flushPromises()

    expect(replace).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Este enlace ya no es válido. Iniciá sesión de nuevo.')
  })
})
