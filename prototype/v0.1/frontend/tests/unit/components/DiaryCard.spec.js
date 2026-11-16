import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import DiaryCard from '../../../src/components/diary/DiaryCard.vue'

function mountCard(entry) {
  return mount(DiaryCard, {
    props: { date: '2026-11-11', entry },
    global: {
      stubs: {
        VCard: { template: '<a><slot /></a>' },
        VCardText: { template: '<div><slot /></div>' },
      },
    },
  })
}

describe('DiaryCard', () => {
  it('nombra cada comida a la vista, no solo con la ilustración', () => {
    const wrapper = mountCard({ breakfast: 'Café', dinner: 'Sopa' })

    const labels = wrapper.findAll('.diary-card__label').map((l) => l.text())
    expect(labels).toEqual(['Desayuno', 'Cena'])
  })

  it('dice qué comidas no se anotaron, en una sola línea', () => {
    const wrapper = mountCard({ breakfast: 'Café', dinner: 'Sopa' })

    expect(wrapper.find('.diary-card__missing').text()).toBe('Sin anotar: almuerzo y merienda.')
  })

  it('no muestra la línea si están las cuatro comidas', () => {
    const wrapper = mountCard({ breakfast: 'a', lunch: 'b', snack: 'c', dinner: 'd' })

    expect(wrapper.find('.diary-card__missing').exists()).toBe(false)
  })

  it('con solo el recuerdo no lista comidas faltantes', () => {
    const wrapper = mountCard({ notes: 'Cumple de la abuela' })

    expect(wrapper.find('.diary-card__missing').exists()).toBe(false)
  })
})
