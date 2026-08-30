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
    // Sin esto, cualquier extensión del navegador de quien tenga la app
    // abierta (password manager, bloqueador de ads, traductor) termina
    // apareciendo como si fuera un error nuestro -- ya pasó en la práctica
    // (un "Rejected ... wrsParams.serviceWorkers..." que no tenía nada que
    // ver con nuestro código). Lista estándar de Sentry para este ruido.
    ignoreErrors: [
      'top.GLOBALS',
      'originalCreateNotification',
      'canvas.contentDocument',
      'MyApp_RemoveAllHighlights',
      "Can't find variable: ZiteReader",
      'jigsaw is not defined',
      'ComboSearch is not defined',
      'atomicFindClose',
      'fb_xd_fragment',
      'bmi_SafeAddOnload',
      'EBCallBackMessageReceived',
      'conduitPage',
    ],
    denyUrls: [
      /extensions\//i,
      /^chrome:\/\//i,
      /^chrome-extension:\/\//i,
      /^moz-extension:\/\//i,
    ],
  })
}

app.use(router).use(vuetify).mount('#app')