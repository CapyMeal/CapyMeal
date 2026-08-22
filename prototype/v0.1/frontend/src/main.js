// main.css declara el layer "reset" y tiene que importarse antes que
// plugins/vuetify (que trae el CSS de Vuetify) para que ese layer quede
// registrado primero -- y por lo tanto con menor prioridad que Vuetify,
// layers mediante. Ver el comentario en styles/main.css.
import './styles/main.css'

import { createApp } from 'vue'
import * as Sentry from '@sentry/vue'
import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import { getInitialTheme } from './utils/theme'

import './styles/vuetify-overrides.css'

document.documentElement.setAttribute('data-theme', getInitialTheme())

const app = createApp(App)

// Sin DSN (ej. en desarrollo local, si no se configuró) queda desactivado
// en vez de tirar errores por una config vacía.
if (import.meta.env.VITE_SENTRY_DSN) {
  Sentry.init({
    app,
    dsn: import.meta.env.VITE_SENTRY_DSN,
    environment: import.meta.env.MODE,
  })
}

app.use(router).use(vuetify).mount('#app')