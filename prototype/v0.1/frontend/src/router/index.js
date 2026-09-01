import { createRouter, createWebHistory } from 'vue-router'
import { isAuthenticated } from '../stores/authStore'

// Cada vista se carga sola cuando se visita esa ruta, en vez de todas
// juntas en el bundle inicial -- así el primer login o splash no paga
// el peso de pantallas que quizás nunca abra en esa sesión.
const router = createRouter({
  history: createWebHistory(),

  routes: [
    { path: '/',                   name: 'splash',          component: () => import('../views/HomeView.vue')                                    },
    { path: '/privacidad',         name: 'privacy',         component: () => import('../views/PrivacyPolicyView.vue')                           },
    { path: '/login',              name: 'login',           component: () => import('../views/LoginView.vue'),          meta: { guest: true }  },
    { path: '/registro',           name: 'register',        component: () => import('../views/RegisterView.vue'),       meta: { guest: true }  },
    { path: '/olvide-contrasena',  name: 'forgot-password', component: () => import('../views/ForgotPasswordView.vue'), meta: { guest: true }  },
    { path: '/reset-password',     name: 'reset-password',  component: () => import('../views/ResetPasswordView.vue'),  meta: { guest: true }  },
    { path: '/auth/google/callback', name: 'google-callback', component: () => import('../views/SocialCallbackView.vue'), props: { provider: 'google' },    meta: { guest: true }  },
    { path: '/auth/microsoft/callback', name: 'microsoft-callback', component: () => import('../views/SocialCallbackView.vue'), props: { provider: 'microsoft' }, meta: { guest: true }  },
    { path: '/hoy',                name: 'today',           component: () => import('../views/TodayView.vue'),          meta: { auth: true  }  },
    { path: '/recuerdos',          name: 'diary',           component: () => import('../views/DiaryView.vue'),          meta: { auth: true  }  },
    { path: '/recuerdos/:date',    name: 'detail',          component: () => import('../views/DetailView.vue'),         meta: { auth: true  }  },
    { path: '/exportar',           name: 'export',          component: () => import('../views/ExportView.vue'),         meta: { auth: true  }  },
    { path: '/ajustes',            name: 'settings',        component: () => import('../views/SettingsView.vue'),       meta: { auth: true  }  },
    { path: '/:pathMatch(.*)*',    name: 'not-found',       component: () => import('../views/NotFoundView.vue')                                 },
  ]
})

router.beforeEach((to) => {
  if (to.meta.auth && !isAuthenticated.value) {
    return { name: 'login' }
  }
  if (to.meta.guest && isAuthenticated.value) {
    return { name: 'today' }
  }
})

export default router
