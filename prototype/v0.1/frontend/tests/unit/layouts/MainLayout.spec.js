import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import MainLayout from '../../../src/layouts/MainLayout.vue'
import VerifyEmailBanner from '../../../src/components/layout/VerifyEmailBanner.vue'
import { currentUser } from '../../../src/stores/authStore'

// currentUser es el mock definido más abajo (un ref real de Vue, no un
// objeto plano) -- así el auto-unwrap de <script setup> en el template de
// MainLayout sigue funcionando igual que con el currentUser real.
vi.mock('../../../src/stores/authStore', async () => {
  const { ref } = await import('vue')

  return { currentUser: ref(null) }
})

function mountLayout() {
  return mount(MainLayout, {
    global: {
      stubs: {
        BottomNavigation: true,
        VAlert: { template: '<div><slot /></div>' },
        VerifyEmailBanner: true,
      },
    },
  })
}

describe('MainLayout', () => {
  it('no muestra el banner de verificación sin usuario logueado', () => {
    currentUser.value = null

    const wrapper = mountLayout()

    expect(wrapper.findComponent(VerifyEmailBanner).exists()).toBe(false)
  })

  it('muestra el banner de verificación si el usuario no confirmó el email', () => {
    currentUser.value = { id: 1, email_verified: false }

    const wrapper = mountLayout()

    expect(wrapper.findComponent(VerifyEmailBanner).exists()).toBe(true)
  })

  it('no muestra el banner de verificación si el email ya está confirmado', () => {
    currentUser.value = { id: 1, email_verified: true }

    const wrapper = mountLayout()

    expect(wrapper.findComponent(VerifyEmailBanner).exists()).toBe(false)
  })
})
