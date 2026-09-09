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
import i18n from './plugins/i18n'
import { getInitialTheme } from './utils/theme'

import './styles/vuetify-overrides.css'

document.documentElement.setAttribute('data-theme', getInitialTheme())
document.documentElement.setAttribute('lang', i18n.global.locale.value)

// Si llegamos hasta acá, el HTML actual cargó bien -- se limpia la marca
// de abajo para que un deploy futuro (más adelante, en la misma pestaña
// de larga vida) pueda volver a disparar una recarga si hace falta.
sessionStorage.removeItem('capymeal-reloaded-after-stale-chunk')

// Después de un deploy nuevo, una pestaña que ya tenía la app abierta
// sigue con el HTML/mapa de chunks viejo en memoria -- cualquier ruta que
// todavía no se pidió (los imports perezosos de Vue Router, ver
// router/index.js) explota al pedir un archivo que el build nuevo ya
// reemplazó. Vite dispara este evento en vez de dejar la promesa
// rechazada en silencio ("no me deja avanzar" al tocar un botón); una
// recarga completa trae el HTML y el mapa de chunks actuales. El flag en
// sessionStorage evita un loop si la recarga por algún motivo sigue
// sirviendo algo viejo (ej. un proxy/CDN cacheando el HTML): un solo
// intento por pestaña y listo.
window.addEventListener('vite:preloadError', () => {
  if (sessionStorage.getItem('capymeal-reloaded-after-stale-chunk')) return
  sessionStorage.setItem('capymeal-reloaded-after-stale-chunk', '1')
  window.location.reload()
})

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

app.use(router).use(vuetify).use(i18n).mount('#app')