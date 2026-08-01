import { createRouter, createWebHistory } from 'vue-router'
import { isAuthenticated } from '../stores/authStore'

import HomeView             from '../views/HomeView.vue'
import LoginView            from '../views/LoginView.vue'
import RegisterView         from '../views/RegisterView.vue'
import ForgotPasswordView   from '../views/ForgotPasswordView.vue'
import ResetPasswordView    from '../views/ResetPasswordView.vue'
import TodayView            from '../views/TodayView.vue'
import DiaryView            from '../views/DiaryView.vue'
import DetailView           from '../views/DetailView.vue'
import ExportView           from '../views/ExportView.vue'
import SettingsView         from '../views/Settingsview.vue'

const router = createRouter({
  history: createWebHistory(),

  routes: [
    { path: '/',                   name: 'splash',          component: HomeView                                    },
    { path: '/login',              name: 'login',           component: LoginView,          meta: { guest: true }  },
    { path: '/registro',           name: 'register',        component: RegisterView,       meta: { guest: true }  },
    { path: '/olvide-contrasena',  name: 'forgot-password', component: ForgotPasswordView, meta: { guest: true }  },
    { path: '/reset-password',     name: 'reset-password',  component: ResetPasswordView,  meta: { guest: true }  },
    { path: '/hoy',                name: 'today',           component: TodayView,          meta: { auth: true  }  },
    { path: '/recuerdos',          name: 'diary',           component: DiaryView,          meta: { auth: true  }  },
    { path: '/recuerdos/:date',    name: 'detail',          component: DetailView,         meta: { auth: true  }  },
    { path: '/exportar',           name: 'export',          component: ExportView,         meta: { auth: true  }  },
    { path: '/ajustes',            name: 'settings',        component: SettingsView,       meta: { auth: true  }  },
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
