import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import VerifyEmailBanner from '../../../src/components/layout/VerifyEmailBanner.vue'
import { apiFetch } from '../../../src/services/mealEntriesApi'

vi.mock('../../../src/services/mealEntriesApi', () => ({
  apiFetch: vi.fn(),
}))

// Mismo estilo que TodayView.spec.js: stubs livianos de Vuetify en vez del
// plugin real, el punto acá es la lógica de reenvío, no el look del alert.
function mountBanner() {
  return mount(VerifyEmailBanner, {
    global: {
      stubs: {
        VAlert: { template: '<div><slot /></div>' },
        VBtn: { template: '<button type="button" v-bind="$attrs" @click="$emit(\'click\')"><slot /></button>' },
      },
    },
  })
}

describe('VerifyEmailBanner', () => {
  beforeEach(() => {
    apiFetch.mockReset()
  })

  it('pide reenviar el mail de verificación al hacer click', async () => {
    apiFetch.mockResolvedValue({ message: 'Te mandamos un nuevo enlace.' })

    const wrapper = mountBanner()
    await wrapper.find('button').trigger('click')
    await flushPromises()

    expect(apiFetch).toHaveBeenCalledWith('/email/verification-notification', { method: 'POST' })
    expect(wrapper.text()).toContain('¡Listo, te lo mandamos de nuevo!')
  })

  it('deshabilita el botón mientras envía y después de un envío exitoso', async () => {
    let resolveFetch
    apiFetch.mockReturnValue(new Promise((resolve) => { resolveFetch = resolve }))

    const wrapper = mountBanner()
    const button = wrapper.find('button')
    await button.trigger('click')

    expect(button.attributes('disabled')).not.toBeUndefined()

    resolveFetch({ message: 'ok' })
    await flushPromises()

    expect(wrapper.find('button').attributes('disabled')).not.toBeUndefined()
  })

  it('si el reenvío falla, no rompe y deja reintentar', async () => {
    apiFetch.mockRejectedValue(new Error('429'))

    const wrapper = mountBanner()
    await wrapper.find('button').trigger('click')
    await flushPromises()

    expect(wrapper.find('button').attributes('disabled')).toBeUndefined()
    expect(wrapper.text()).toContain('Todavía no confirmaste tu email')
  })
})
