import { createRouter, createWebHistory } from 'vue-router'

import HomeView   from '../views/HomeView.vue'
import TodayView  from '../views/TodayView.vue'
import DiaryView  from '../views/DiaryView.vue'

const router = createRouter({
  history: createWebHistory(),

  routes: [
    {
      path: '/',
      name: 'splash',
      component: HomeView
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