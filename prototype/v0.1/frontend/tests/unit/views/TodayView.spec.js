import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import TodayView from '../../../src/views/TodayView.vue'
import { getMealEntries, getMealEntry, upsertMealEntry, isNetworkError } from '../../../src/services/mealEntriesApi'

vi.mock('../../../src/services/mealEntriesApi', () => ({
  getMealEntries: vi.fn(),
  getMealEntry: vi.fn(),
  upsertMealEntry: vi.fn(),
  isNetworkError: vi.fn(() => false),
}))

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: vi.fn() }),
}))

// Stub liviano, sin Vuetify: el punto de estos tests es saveDay/
// saveSingleField (la lógica de guardado de TodayView), no MealCard en sí,
// que ya tiene su propio contrato simple de v-model + save-field.
const MealCardStub = {
  props: ['modelValue', 'meal', 'title', 'placeholder', 'saving', 'justSaved', 'persisted', 'error'],
  emits: ['update:modelValue', 'save-field'],
  template: `
    <div>
      <textarea :data-test="title" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" />
      <button :data-test="'save-' + title" type="button" @click="$emit('save-field')">guardar</button>
      <p v-if="error" :data-error="meal">{{ error }}</p>
    </div>
  `,
}

function mountTodayView() {
  return mount(TodayView, {
    global: {
      stubs: {
        MainLayout: { template: '<div><slot /></div>' },
        MealCard: MealCardStub,
        UserAvatar: true,
        CapyLoader: true,
        CapyButton: { template: '<button type="button"><slot /></button>' },
        VAlert: { template: '<div><slot /></div>' },
        VIcon: true,
      },
    },
  })
}

function findButtonByText(wrapper, text) {
  return wrapper.findAll('button').find((b) => b.text().includes(text))
}

describe('TodayView', () => {
  beforeEach(() => {
    getMealEntries.mockReset().mockResolvedValue([])
    getMealEntry.mockReset().mockResolvedValue(null)
    upsertMealEntry.mockReset()
    isNetworkError.mockReset().mockReturnValue(false)
  })

  describe('saveDay', () => {
    it('no llama a la API y muestra un error si el formulario está vacío', async () => {
      const wrapper = mountTodayView()
      await flushPromises()

      await findButtonByText(wrapper, 'Cerrar el día').trigger('click')
      await flushPromises()

      expect(upsertMealEntry).not.toHaveBeenCalled()
      expect(wrapper.text()).toContain('Completá al menos una comida o un recuerdo antes de cerrar el día.')
    })

    it('guarda el día y muestra la confirmación cuando hay al menos un campo lleno', async () => {
      upsertMealEntry.mockResolvedValue({})
      const wrapper = mountTodayView()
      await flushPromises()

      await wrapper.find('[data-test="Desayuno"]').setValue('Café con tostadas')
      await findButtonByText(wrapper, 'Cerrar el día').trigger('click')
      await flushPromises()

      expect(upsertMealEntry).toHaveBeenCalledWith(
        expect.objectContaining({ breakfast: 'Café con tostadas' })
      )
      expect(wrapper.text()).toContain('Listo')
    })

    it('muestra un mensaje de sin conexión cuando falla por red', async () => {
      upsertMealEntry.mockRejectedValue(new Error('network'))
      isNetworkError.mockReturnValue(true)
      const wrapper = mountTodayView()
      await flushPromises()

      await wrapper.find('[data-test="Desayuno"]').setValue('Café')
      await findButtonByText(wrapper, 'Cerrar el día').trigger('click')
      await flushPromises()

      expect(wrapper.text()).toContain('No pude guardar: estás sin conexión.')
    })

    it('muestra un mensaje genérico cuando falla por otra razón', async () => {
      upsertMealEntry.mockRejectedValue(new Error('server error'))
      isNetworkError.mockReturnValue(false)
      const wrapper = mountTodayView()
      await flushPromises()

      await wrapper.find('[data-test="Desayuno"]').setValue('Café')
      await findButtonByText(wrapper, 'Cerrar el día').trigger('click')
      await flushPromises()

      expect(wrapper.text()).toContain('No pude guardar este día. Intentá nuevamente.')
    })
  })

  describe('saveSingleField', () => {
    it('no llama a la API si el campo está vacío', async () => {
      const wrapper = mountTodayView()
      await flushPromises()

      await wrapper.find('[data-test="save-Desayuno"]').trigger('click')
      await flushPromises()

      expect(upsertMealEntry).not.toHaveBeenCalled()
    })

    it('guarda solo ese campo cuando tiene contenido', async () => {
      upsertMealEntry.mockResolvedValue({})
      const wrapper = mountTodayView()
      await flushPromises()

      await wrapper.find('[data-test="Desayuno"]').setValue('Café')
      await wrapper.find('[data-test="save-Desayuno"]').trigger('click')
      await flushPromises()

      expect(upsertMealEntry).toHaveBeenCalledWith(
        expect.objectContaining({ breakfast: 'Café' })
      )
    })

    it('muestra un mensaje de sin conexión cuando falla por red', async () => {
      upsertMealEntry.mockRejectedValue(new Error('network'))
      isNetworkError.mockReturnValue(true)
      const wrapper = mountTodayView()
      await flushPromises()

      await wrapper.find('[data-test="Desayuno"]').setValue('Café')
      await wrapper.find('[data-test="save-Desayuno"]').trigger('click')
      await flushPromises()

      expect(wrapper.text()).toContain('No pude guardar automáticamente: estás sin conexión.')
    })

    it('muestra un mensaje genérico cuando falla por otra razón', async () => {
      upsertMealEntry.mockRejectedValue(new Error('server error'))
      isNetworkError.mockReturnValue(false)
      const wrapper = mountTodayView()
      await flushPromises()

      await wrapper.find('[data-test="Desayuno"]').setValue('Café')
      await wrapper.find('[data-test="save-Desayuno"]').trigger('click')
      await flushPromises()

      expect(wrapper.text()).toContain('No pude guardar ese cambio. Intentá nuevamente.')
    })

    it('el error de un campo va en su tarjeta, no abajo de "Cerrar el día"', async () => {
      upsertMealEntry.mockRejectedValue(new Error('server error'))
      const wrapper = mountTodayView()
      await flushPromises()

      await wrapper.find('[data-test="Desayuno"]').setValue('Café')
      await wrapper.find('[data-test="save-Desayuno"]').trigger('click')
      await flushPromises()

      expect(wrapper.find('[data-error="breakfast"]').text()).toBe('No pude guardar ese cambio. Intentá nuevamente.')
    })
  })

  describe('guardado al escribir', () => {
    beforeEach(() => { vi.useFakeTimers() })
    afterEach(() => { vi.useRealTimers() })

    it('guarda sola tras una pausa, sin tener que salir del campo', async () => {
      upsertMealEntry.mockResolvedValue({})
      const wrapper = mountTodayView()
      await flushPromises()

      await wrapper.find('[data-test="Desayuno"]').setValue('Café')
      expect(upsertMealEntry).not.toHaveBeenCalled()

      await vi.advanceTimersByTimeAsync(1300)

      expect(upsertMealEntry).toHaveBeenCalledWith(expect.objectContaining({ breakfast: 'Café' }))
    })

    it('guarda lo pendiente cuando la app pasa a segundo plano', async () => {
      upsertMealEntry.mockResolvedValue({})
      const wrapper = mountTodayView()
      await flushPromises()

      await wrapper.find('[data-test="Cena"]').setValue('Sopa')
      Object.defineProperty(document, 'visibilityState', { value: 'hidden', configurable: true })
      document.dispatchEvent(new Event('visibilitychange'))
      await flushPromises()

      expect(upsertMealEntry).toHaveBeenCalledWith(expect.objectContaining({ dinner: 'Sopa' }))
      delete document.visibilityState
    })
  })

  describe('días sin anotar', () => {
    beforeEach(() => { localStorage.clear() })

    it('invita a anotar desde el primer día sin registro, sin tono de advertencia', async () => {
      getMealEntries.mockResolvedValue([{ date: addDaysISO(-5) }])
      const wrapper = mountTodayView()
      await flushPromises()

      expect(wrapper.text()).toContain('¿Te quedaron días sin anotar?')
      expect(findButtonByText(wrapper, 'Anotar el')).toBeTruthy()
    })

    it('"Ahora no" lo oculta y no vuelve a aparecer en el mismo día', async () => {
      getMealEntries.mockResolvedValue([{ date: addDaysISO(-5) }])
      const wrapper = mountTodayView()
      await flushPromises()

      await findButtonByText(wrapper, 'Ahora no').trigger('click')
      expect(wrapper.text()).not.toContain('¿Te quedaron días sin anotar?')

      const again = mountTodayView()
      await flushPromises()
      expect(again.text()).not.toContain('¿Te quedaron días sin anotar?')
    })

    it('si falta un solo día lo nombra, sin hablar de un rango', async () => {
      getMealEntries.mockResolvedValue([{ date: addDaysISO(-2) }])
      const wrapper = mountTodayView()
      await flushPromises()

      expect(wrapper.text()).toContain('¿Te quedó sin anotar el')
      expect(wrapper.text()).not.toContain('ponerte al día desde')
    })
  })

  describe('después de cerrar el día', () => {
    it('si se cerró hoy ofrece seguir editando, no "registrar otro día"', async () => {
      upsertMealEntry.mockResolvedValue({})
      const wrapper = mountTodayView()
      await flushPromises()

      await wrapper.find('[data-test="Desayuno"]').setValue('Café')
      await findButtonByText(wrapper, 'Cerrar el día').trigger('click')
      await flushPromises()

      expect(findButtonByText(wrapper, 'Registrar otro día')).toBeFalsy()
      await findButtonByText(wrapper, 'Seguir editando').trigger('click')
      expect(wrapper.find('[data-test="Desayuno"]').exists()).toBe(true)
    })
  })
})

function addDaysISO(days) {
  const d = new Date()
  d.setDate(d.getDate() + days)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}
