import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import DiaryView from '../../../src/views/DiaryView.vue'
import { getMealEntries } from '../../../src/services/mealEntriesApi'

vi.mock('../../../src/services/mealEntriesApi', () => ({
  getMealEntries: vi.fn(),
  isNetworkError: () => false,
}))

const routeQuery = {}

vi.mock('vue-router', () => ({
  useRoute: () => ({ query: routeQuery }),
}))

// Stub liviano y sin dependencia de Vuetify -- el punto de estos tests es
// la lógica de DiaryView (qué estado vacío muestra y cuándo), no el
// selector de fechas en sí, que ya tiene su propio contrato simple de
// v-model:from/v-model:to.
const DateRangeFilterStub = {
  props: ['from', 'to'],
  emits: ['update:from', 'update:to'],
  template: `
    <div>
      <input data-test="from" :value="from" @input="$emit('update:from', $event.target.value)">
      <input data-test="to" :value="to" @input="$emit('update:to', $event.target.value)">
    </div>
  `,
}

function mountDiaryView() {
  return mount(DiaryView, {
    global: {
      stubs: {
        MainLayout: { template: '<div><slot /></div>' },
        DateRangeFilter: DateRangeFilterStub,
        DiaryCard: true,
        CapyButton: true,
        VAlert: { template: '<div><slot /></div>' },
      },
    },
  })
}

describe('DiaryView', () => {
  beforeEach(() => {
    getMealEntries.mockReset()
    delete routeQuery.eliminado
  })

  it('confirma el borrado cuando vuelve del Detalle con ?eliminado=<fecha>', async () => {
    getMealEntries.mockResolvedValue([])
    routeQuery.eliminado = '2026-08-20'

    const wrapper = mountDiaryView()
    await flushPromises()

    expect(wrapper.text()).toContain('Listo, eliminamos el recuerdo del')
  })

  it('ignora un ?eliminado= que no es una fecha', async () => {
    getMealEntries.mockResolvedValue([])
    routeQuery.eliminado = '<script>'

    const wrapper = mountDiaryView()
    await flushPromises()

    expect(wrapper.text()).not.toContain('eliminamos el recuerdo')
  })

  it('muestra el estado vacío general cuando el diario no tiene ningún recuerdo', async () => {
    getMealEntries.mockResolvedValue([])

    const wrapper = mountDiaryView()
    await flushPromises()

    const emptyState = wrapper.findComponent({ name: 'EmptyState' })
    expect(emptyState.exists()).toBe(true)
    expect(emptyState.props('message')).toBe('Todavía no guardamos ningún recuerdo.')
    expect(emptyState.props('actionLabel')).toBe('Registrar mi primer día')
  })

  it('distingue "sin recuerdos para ese rango" de "el diario está vacío"', async () => {
    getMealEntries.mockResolvedValue([])

    const wrapper = mountDiaryView()
    await flushPromises()

    await wrapper.find('[data-test="from"]').setValue('2026-01-01')
    await flushPromises()

    const emptyState = wrapper.findComponent({ name: 'EmptyState' })
    expect(emptyState.props('message')).toBe('No encontré registros para esas fechas.')
    expect(emptyState.props('actionLabel')).toBeFalsy()
  })

  it('si la carga falla muestra el error, no "todavía no guardamos ningún recuerdo"', async () => {
    getMealEntries.mockRejectedValue(new Error('server error'))

    const wrapper = mountDiaryView()
    await flushPromises()

    expect(wrapper.text()).toContain('No pude cargar el diario. Intentá nuevamente.')
    expect(wrapper.findComponent({ name: 'EmptyState' }).exists()).toBe(false)
  })

  it('muestra las tarjetas del diario cuando hay recuerdos', async () => {
    getMealEntries.mockResolvedValue([
      { date: '2026-01-10', breakfast: 'Café' },
    ])

    const wrapper = mountDiaryView()
    await flushPromises()

    expect(wrapper.findComponent({ name: 'EmptyState' }).exists()).toBe(false)
    expect(wrapper.findAllComponents({ name: 'DiaryCard' })).toHaveLength(1)
  })
})
