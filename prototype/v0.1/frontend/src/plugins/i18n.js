import { createI18n } from 'vue-i18n'
import { getInitialLocale } from '../utils/locale'

const initialLocale = getInitialLocale()

const i18n = createI18n({
  legacy: false,
  locale: initialLocale,
  fallbackLocale: 'es',
  messages: {},
})

const loadedLocales = new Set()

// Cada catálogo pesa lo suyo (~300 claves) y hasta ahora los dos se
// cargaban siempre juntos en el bundle inicial, aunque una sesión sólo
// necesite uno -- esto los separa en chunks propios que sólo se piden
// cuando hacen falta.
async function loadLocaleMessages(locale) {
  if (loadedLocales.has(locale)) return

  const messages = await import(`../locales/${locale}.json`)
  i18n.global.setLocaleMessage(locale, messages.default)
  loadedLocales.add(locale)
}

// Carga el idioma con el que arranca la sesión y, si es distinto, también
// "es" (el fallbackLocale): sin él ya cargado, una clave que faltara en el
// catálogo activo se vería en crudo en vez de caer al español. Hay que
// esperar esto antes de montar la app -- ver main.js.
export async function initI18n() {
  await loadLocaleMessages(initialLocale)
  if (initialLocale !== 'es') {
    await loadLocaleMessages('es')
  }
}

// Usado por SettingsView al togglear el idioma -- carga el catálogo
// nuevo (si todavía no se pidió) antes de cambiar el locale activo.
export async function switchLocale(locale) {
  await loadLocaleMessages(locale)
  i18n.global.locale.value = locale
}

export default i18n
