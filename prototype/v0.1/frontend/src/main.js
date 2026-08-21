import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import { getInitialTheme } from './utils/theme'

import './styles/main.css'
import './styles/vuetify-overrides.css'

document.documentElement.setAttribute('data-theme', getInitialTheme())

// Vuetify anima el label flotante de los v-text-field con element.animate()
// (transform + resize) sobre un clon del label, prendiendo/apagando su
// visibility al terminar. En ciertas GPUs reales esa capa compuesta deja
// un resto pintado del borde superpuesto con el texto del label ("Email",
// "Contraseña" se ven cruzados por una línea) -- confirmado con la propia
// usuaria: en el punto exacto donde se ve la línea, ningún elemento del DOM
// tiene border/box-shadow/outline, así que no es un tema de CSS.
// Vuetify ya se salta esa animación cuando el SO pide "reducir movimiento"
// (usa window.matchMedia('(prefers-reduced-motion: reduce)') internamente),
// así que forzamos esa única consulta a "true" para desactivar justo esta
// animación puntual, sin tocar el resto de transiciones de la app.
const nativeMatchMedia = window.matchMedia.bind(window)
window.matchMedia = (query) => {
  if (typeof query === 'string' && query.includes('prefers-reduced-motion')) {
    const mql = nativeMatchMedia(query)
    return {
      media: mql.media,
      matches: true,
      onchange: null,
      addEventListener: mql.addEventListener.bind(mql),
      removeEventListener: mql.removeEventListener.bind(mql),
      addListener: mql.addListener?.bind(mql),
      removeListener: mql.removeListener?.bind(mql),
      dispatchEvent: mql.dispatchEvent.bind(mql),
    }
  }
  return nativeMatchMedia(query)
}

createApp(App)
  .use(router)
  .use(vuetify)
  .mount('#app')