import { config } from '@vue/test-utils'
import i18n, { initI18n } from '../src/plugins/i18n'

// Todos los componentes convertidos a i18n necesitan el plugin instalado
// para que useI18n()/t() funcionen al montarlos con Vue Test Utils -- se
// instala acá una sola vez en vez de en cada spec, así ningún mount() se
// olvida de incluirlo.
//
// Los catálogos ahora se cargan aparte (ver plugins/i18n.js) -- initI18n()
// carga el idioma inicial más "es" (el fallback) si son distintos. En este
// entorno jsdom, getInitialLocale() cae a navigator.language ("en-US" por
// default), así que esto ya deja los dos catálogos cargados. El locale
// activo se fuerza a 'es' después: sin este override, todos los specs
// existentes (que aseveran texto en español) romperían por un detalle del
// entorno de test, no por un cambio de comportamiento real. Los specs de
// SettingsView que prueban el toggle de idioma cambian el locale ellos
// mismos, así que no se ven afectados.
await initI18n()
i18n.global.locale.value = 'es'
config.global.plugins.push(i18n)

// jsdom no implementa matchMedia ni ResizeObserver, y Vuetify los toca al
// montar cualquier componente (temas, layout) -- sin estos stubs, el primer
// test que monte algo relacionado con Vuetify falla por el entorno, no por
// la aserción real.
if (!window.matchMedia) {
  window.matchMedia = (query) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: () => {},
    removeListener: () => {},
    addEventListener: () => {},
    removeEventListener: () => {},
    dispatchEvent: () => false,
  })
}

if (!window.ResizeObserver) {
  window.ResizeObserver = class ResizeObserver {
    observe() {}
    unobserve() {}
    disconnect() {}
  }
}
