import { describe, it, expect, vi, beforeEach } from 'vitest'
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
  props: ['modelValue', 'title', 'placeholder', 'saving', 'justSaved', 'icon', 'iconImage'],
  emits: ['update:modelValue', 'save-field'],
  template: `
    <div>
      <textarea :data-test="title" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" />
      <button :data-test="'save-' + title" type="button" @click="$emit('save-field')">guardar</button>
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

      await findButtonByText(wrapper, 'Guardar mi día').trigger('click')
      await flushPromises()

      expect(upsertMealEntry).not.toHaveBeenCalled()
      expect(wrapper.text()).toContain('Completá al menos una comida o recuerdo antes de guardar.')
    })

    it('guarda el día y muestra la confirmación cuando hay al menos un campo lleno', async () => {
      upsertMealEntry.mockResolvedValue({})
      const wrapper = mountTodayView()
      await flushPromises()

      await wrapper.find('[data-test="Desayuno"]').setValue('Café con tostadas')
      await findButtonByText(wrapper, 'Guardar mi día').trigger('click')
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
      await findButtonByText(wrapper, 'Guardar mi día').trigger('click')
      await flushPromises()

      expect(wrapper.text()).toContain('No se pudo guardar: estás sin conexión.')
    })

    it('muestra un mensaje genérico cuando falla por otra razón', async () => {
      upsertMealEntry.mockRejectedValue(new Error('server error'))
      isNetworkError.mockReturnValue(false)
      const wrapper = mountTodayView()
      await flushPromises()

      await wrapper.find('[data-test="Desayuno"]').setValue('Café')
      await findButtonByText(wrapper, 'Guardar mi día').trigger('click')
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

      expect(wrapper.text()).toContain('No se pudo guardar automáticamente: estás sin conexión.')
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
  })
})
