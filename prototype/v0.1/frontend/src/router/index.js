import { createRouter, createWebHistory } from 'vue-router'

import TodayView from '../views/TodayView.vue'
import DiaryView from '../views/DiaryView.vue'

const router = createRouter({
  history: createWebHistory(),

  routes: [
    {
      path: '/',
      redirect: '/hoy'
    },
    {
      path: '/hoy',
      name: 'today',
      component: TodayView
    },
    {
      path: '/recuerdos',
      name: 'diary',
      component: DiaryView
    }
  ]
})

export default router