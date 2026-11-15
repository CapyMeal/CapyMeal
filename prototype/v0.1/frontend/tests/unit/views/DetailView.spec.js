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

// Stub liviano de MealCard: el punto de estos tests es la lógica de guardado
// del Detalle (useDayEntry), no la tarjeta en sí.
const MealCardStub = {
  props: ['modelValue', 'meal', 'title', 'placeholder', 'saving', 'justSaved'],
  emits: ['update:modelValue', 'save-field'],
  template: `
    <textarea
      :data-meal="meal"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      @blur="$emit('save-field')"
    />
  `,
}

function mountDetailView() {
  return mount(DetailView, {
    global: {
      stubs: {
        MainLayout: { template: '<div><slot /></div>' },
        MealCard: MealCardStub,
        CapyLoader: true,
        EmptyState: true,
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

async function editField(wrapper, meal, value) {
  const field = wrapper.find(`[data-meal="${meal}"]`)
  await field.setValue(value)
  await field.trigger('blur')
  await flushPromises()
}

const DAY = { breakfast: 'Café', lunch: '', snack: '', dinner: '', notes: '' }

describe('DetailView', () => {
  beforeEach(() => {
    push.mockReset()
    deleteMealEntry.mockReset()
    exportMealEntriesPdf.mockReset()
    getMealEntry.mockReset()
    upsertMealEntry.mockReset()
    isNetworkError.mockReset().mockReturnValue(false)
  })

  describe('carga', () => {
    it('muestra las tarjetas con el contenido cuando el día tiene un registro', async () => {
      getMealEntry.mockResolvedValue(DAY)

      const wrapper = mountDetailView()
      await flushPromises()

      expect(wrapper.findComponent({ name: 'EmptyState' }).exists()).toBe(false)
      expect(wrapper.find('[data-meal="breakfast"]').element.value).toBe('Café')
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

      expect(wrapper.text()).toContain('No pude cargar este día: estás sin conexión.')
      expect(wrapper.findComponent({ name: 'EmptyState' }).exists()).toBe(false)
    })

    it('muestra un mensaje genérico cuando falla por otra razón, sin el EmptyState engañoso', async () => {
      getMealEntry.mockRejectedValue(new Error('server error'))

      const wrapper = mountDetailView()
      await flushPromises()

      expect(wrapper.text()).toContain('No pude cargar este día. Intentá nuevamente.')
      expect(wrapper.findComponent({ name: 'EmptyState' }).exists()).toBe(false)
    })
  })

  describe('guardado automático', () => {
    it('guarda solo ese campo al salir, con el texto nuevo', async () => {
      getMealEntry.mockResolvedValue(DAY)
      upsertMealEntry.mockResolvedValue({})
      const wrapper = mountDetailView()
      await flushPromises()

      await editField(wrapper, 'breakfast', 'Café con tostadas')

      expect(upsertMealEntry).toHaveBeenCalledWith({ date: '2026-08-20', breakfast: 'Café con tostadas' })
    })

    it('no llama a la API si el campo no cambió', async () => {
      getMealEntry.mockResolvedValue(DAY)
      const wrapper = mountDetailView()
      await flushPromises()

      await editField(wrapper, 'breakfast', 'Café')
      await editField(wrapper, 'lunch', '   ')

      expect(upsertMealEntry).not.toHaveBeenCalled()
    })

    it('guarda un campo vaciado cuando el día tiene otro contenido', async () => {
      getMealEntry.mockResolvedValue({ ...DAY, lunch: 'Fideos' })
      upsertMealEntry.mockResolvedValue({})
      const wrapper = mountDetailView()
      await flushPromises()

      await editField(wrapper, 'breakfast', '')

      expect(upsertMealEntry).toHaveBeenCalledWith({ date: '2026-08-20', breakfast: '' })
    })

    it('no deja vaciar el último campo con contenido y lo restaura', async () => {
      getMealEntry.mockResolvedValue(DAY)
      const wrapper = mountDetailView()
      await flushPromises()

      await editField(wrapper, 'breakfast', '')

      expect(upsertMealEntry).not.toHaveBeenCalled()
      expect(wrapper.text()).toContain('Un día guardado necesita al menos una comida o un recuerdo.')
      expect(wrapper.find('[data-meal="breakfast"]').element.value).toBe('Café')
    })

    it('muestra un mensaje de sin conexión cuando falla por red', async () => {
      getMealEntry.mockResolvedValue(DAY)
      upsertMealEntry.mockRejectedValue(new Error('network'))
      isNetworkError.mockReturnValue(true)
      const wrapper = mountDetailView()
      await flushPromises()

      await editField(wrapper, 'breakfast', 'Otra cosa')

      expect(wrapper.text()).toContain('No se pudo guardar: estás sin conexión.')
    })

    it('muestra un mensaje genérico con el nombre del campo cuando falla por otra razón', async () => {
      getMealEntry.mockResolvedValue(DAY)
      upsertMealEntry.mockRejectedValue(new Error('server error'))
      const wrapper = mountDetailView()
      await flushPromises()

      await editField(wrapper, 'breakfast', 'Otra cosa')

      expect(wrapper.text()).toContain('No pude guardar desayuno. Intentá nuevamente.')
    })
  })

  describe('eliminar', () => {
    it('pide confirmación nombrando el día y vuelve al diario con el aviso', async () => {
      getMealEntry.mockResolvedValue(DAY)
      deleteMealEntry.mockResolvedValue(undefined)
      const wrapper = mountDetailView()
      await flushPromises()

      await findButtonByText(wrapper, 'Eliminar este día').trigger('click')
      expect(wrapper.text()).toContain('No se puede deshacer.')

      await findButtonByText(wrapper, 'Sí, eliminar').trigger('click')
      await flushPromises()

      expect(deleteMealEntry).toHaveBeenCalledWith('2026-08-20')
      expect(push).toHaveBeenCalledWith({ path: '/recuerdos', query: { eliminado: '2026-08-20' } })
    })

    it('muestra el error dentro de la confirmación y no navega cuando el borrado falla', async () => {
      getMealEntry.mockResolvedValue(DAY)
      deleteMealEntry.mockRejectedValue(new Error('server error'))
      const wrapper = mountDetailView()
      await flushPromises()

      await findButtonByText(wrapper, 'Eliminar este día').trigger('click')
      await findButtonByText(wrapper, 'Sí, eliminar').trigger('click')
      await flushPromises()

      expect(push).not.toHaveBeenCalled()
      expect(wrapper.text()).toContain('No pude eliminar este día. Intentá nuevamente.')
      expect(findButtonByText(wrapper, 'Sí, eliminar').exists()).toBe(true)
    })
  })
})
