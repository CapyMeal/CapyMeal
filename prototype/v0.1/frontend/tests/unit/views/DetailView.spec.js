import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import DetailView from '../../../src/views/DetailView.vue'
import {
  deleteMealEntry,
  exportMealEntriesPdf,
  getMealEntry,
  upsertMealEntry,
  isNetworkError,
} from '../../../src/services/mealEntriesApi'

vi.mock('../../../src/services/mealEntriesApi', () => ({
  deleteMealEntry: vi.fn(),
  exportMealEntriesPdf: vi.fn(),
  getMealEntry: vi.fn(),
  upsertMealEntry: vi.fn(),
  isNetworkError: vi.fn(() => false),
}))

const push = vi.fn()

vi.mock('vue-router', () => ({
  useRoute: () => ({ params: { date: '2026-08-20' } }),
  useRouter: () => ({ push }),
}))

function mountDetailView() {
  return mount(DetailView, {
    global: {
      stubs: {
        MainLayout: { template: '<div><slot /></div>' },
        CapyLoader: true,
        EmptyState: true,
        CapyButton: { template: '<button type="button"><slot /></button>' },
        VCard: { template: '<div><slot /></div>' },
        VCardText: { template: '<div><slot /></div>' },
        VTextarea: {
          props: ['modelValue'],
          emits: ['update:modelValue'],
          template: '<textarea :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
        },
      },
    },
  })
}

function findButtonByText(wrapper, text) {
  return wrapper.findAll('button').find((b) => b.text().includes(text))
}

describe('DetailView', () => {
  beforeEach(() => {
    push.mockReset()
    deleteMealEntry.mockReset()
    exportMealEntriesPdf.mockReset()
    getMealEntry.mockReset()
    upsertMealEntry.mockReset()
    isNetworkError.mockReset().mockReturnValue(false)
  })

  describe('loadEntry', () => {
    it('muestra el detalle cuando el día tiene un registro', async () => {
      getMealEntry.mockResolvedValue({ breakfast: 'Café', lunch: '', snack: '', dinner: '', notes: '' })

      const wrapper = mountDetailView()
      await flushPromises()

      expect(wrapper.findComponent({ name: 'EmptyState' }).exists()).toBe(false)
      expect(wrapper.text()).toContain('Café')
    })

    it('muestra el estado vacío cuando no hay registro para ese día', async () => {
      getMealEntry.mockResolvedValue(null)

      const wrapper = mountDetailView()
      await flushPromises()

      expect(wrapper.findComponent({ name: 'EmptyState' }).exists()).toBe(true)
    })

    it('muestra un mensaje de sin conexión cuando falla por red, sin el EmptyState engañoso', async () => {
      getMealEntry.mockRejectedValue(new Error('network'))
      isNetworkError.mockReturnValue(true)

      const wrapper = mountDetailView()
      await flushPromises()

      expect(wrapper.text()).toContain('Estás sin conexión — no pude cargar este día.')
      expect(wrapper.findComponent({ name: 'EmptyState' }).exists()).toBe(false)
    })

    it('muestra un mensaje genérico cuando falla por otra razón, sin el EmptyState engañoso', async () => {
      getMealEntry.mockRejectedValue(new Error('server error'))
      isNetworkError.mockReturnValue(false)

      const wrapper = mountDetailView()
      await flushPromises()

      expect(wrapper.text()).toContain('No pude cargar este día. Intentá nuevamente.')
      expect(wrapper.findComponent({ name: 'EmptyState' }).exists()).toBe(false)
    })
  })

  describe('saveSingleMeal', () => {
    it('no llama a la API si el borrador está vacío', async () => {
      getMealEntry.mockResolvedValue({ breakfast: 'Café', lunch: '', snack: '', dinner: '', notes: '' })
      const wrapper = mountDetailView()
      await flushPromises()

      await findButtonByText(wrapper, 'Editar desayuno').trigger('click')
      await wrapper.find('textarea').setValue('   ')
      await findButtonByText(wrapper, 'Guardar').trigger('click')
      await flushPromises()

      expect(upsertMealEntry).not.toHaveBeenCalled()
      expect(wrapper.text()).toContain('Completá desayuno antes de guardar.')
    })

    it('guarda el campo, actualiza la vista y sale del modo edición', async () => {
      getMealEntry.mockResolvedValue({ breakfast: 'Café', lunch: '', snack: '', dinner: '', notes: '' })
      upsertMealEntry.mockResolvedValue({})
      const wrapper = mountDetailView()
      await flushPromises()

      await findButtonByText(wrapper, 'Editar desayuno').trigger('click')
      await wrapper.find('textarea').setValue('Café con tostadas')
      await findButtonByText(wrapper, 'Guardar').trigger('click')
      await flushPromises()

      expect(upsertMealEntry).toHaveBeenCalledWith({ date: '2026-08-20', breakfast: 'Café con tostadas' })
      expect(wrapper.text()).toContain('Café con tostadas')
      // Vuelve al modo lectura: el textarea de edición ya no está.
      expect(wrapper.find('textarea').exists()).toBe(false)
    })

    it('muestra un mensaje de sin conexión cuando falla por red y no sale de edición', async () => {
      getMealEntry.mockResolvedValue({ breakfast: 'Café', lunch: '', snack: '', dinner: '', notes: '' })
      upsertMealEntry.mockRejectedValue(new Error('network'))
      isNetworkError.mockReturnValue(true)
      const wrapper = mountDetailView()
      await flushPromises()

      await findButtonByText(wrapper, 'Editar desayuno').trigger('click')
      await wrapper.find('textarea').setValue('Otra cosa')
      await findButtonByText(wrapper, 'Guardar').trigger('click')
      await flushPromises()

      expect(wrapper.text()).toContain('No se pudo guardar: estás sin conexión.')
      expect(wrapper.find('textarea').exists()).toBe(true)
    })

    it('muestra un mensaje genérico cuando falla por otra razón', async () => {
      getMealEntry.mockResolvedValue({ breakfast: 'Café', lunch: '', snack: '', dinner: '', notes: '' })
      upsertMealEntry.mockRejectedValue(new Error('server error'))
      isNetworkError.mockReturnValue(false)
      const wrapper = mountDetailView()
      await flushPromises()

      await findButtonByText(wrapper, 'Editar desayuno').trigger('click')
      await wrapper.find('textarea').setValue('Otra cosa')
      await findButtonByText(wrapper, 'Guardar').trigger('click')
      await flushPromises()

      expect(wrapper.text()).toContain('No pude guardar desayuno. Intentá nuevamente.')
    })
  })

  describe('deleteEntry', () => {
    it('navega al diario cuando el borrado tiene éxito', async () => {
      getMealEntry.mockResolvedValue({ breakfast: 'Café', lunch: '', snack: '', dinner: '', notes: '' })
      deleteMealEntry.mockResolvedValue(undefined)
      const wrapper = mountDetailView()
      await flushPromises()

      await findButtonByText(wrapper, 'Eliminar').trigger('click')
      await findButtonByText(wrapper, 'Sí, eliminar').trigger('click')
      await flushPromises()

      expect(deleteMealEntry).toHaveBeenCalledWith('2026-08-20')
      expect(push).toHaveBeenCalledWith('/recuerdos')
    })

    it('muestra un error y no navega cuando el borrado falla', async () => {
      getMealEntry.mockResolvedValue({ breakfast: 'Café', lunch: '', snack: '', dinner: '', notes: '' })
      deleteMealEntry.mockRejectedValue(new Error('server error'))
      isNetworkError.mockReturnValue(false)
      const wrapper = mountDetailView()
      await flushPromises()

      await findButtonByText(wrapper, 'Eliminar').trigger('click')
      await findButtonByText(wrapper, 'Sí, eliminar').trigger('click')
      await flushPromises()

      expect(push).not.toHaveBeenCalled()
      expect(wrapper.text()).toContain('No pude eliminar este día. Intentá nuevamente.')
    })
  })
})
