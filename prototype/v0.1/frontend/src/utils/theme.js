// Única fuente de verdad del tema. Antes esta lógica estaba duplicada en
// main.js y en plugins/vuetify.js, las dos con el mismo default
// hardcodeado a 'dark' -- eso hacía que la app siempre abriera en oscuro
// en un dispositivo nuevo, incluso con el sistema en modo claro.
//
// Hay tres preferencias: 'light', 'dark' y 'system'. "Sistema" es no tener
// nada guardado y seguir al sistema operativo, también si cambia con la
// app abierta (el modo oscuro automático de noche en Android).

const STORAGE_KEY = 'capymeal-theme'
const VUETIFY_THEMES = { light: 'capymealLight', dark: 'capymealDark' }

const systemQuery = () => window.matchMedia('(prefers-color-scheme: dark)')

function readSaved() {
  try { return localStorage.getItem(STORAGE_KEY) } catch { return null }
}

export function getThemePreference() {
  const saved = readSaved()
  return saved === 'light' || saved === 'dark' ? saved : 'system'
}

function resolve(preference) {
  if (preference !== 'system') return preference
  return systemQuery().matches ? 'dark' : 'light'
}

export function getInitialTheme() {
  return resolve(getThemePreference())
}

export function vuetifyThemeName(theme) {
  return VUETIFY_THEMES[theme]
}

// `themeInstance` es el tema de Vuetify (useTheme() o vuetify.theme):
// data-theme manda en el CSS propio y Vuetify se mantiene sincronizado.
function apply(theme, themeInstance) {
  document.documentElement.setAttribute('data-theme', theme)
  themeInstance.change(vuetifyThemeName(theme))
}

export function setThemePreference(preference, themeInstance) {
  try {
    if (preference === 'system') localStorage.removeItem(STORAGE_KEY)
    else localStorage.setItem(STORAGE_KEY, preference)
  } catch { /* sin almacenamiento: vale para esta sesión */ }
  apply(resolve(preference), themeInstance)
}

export function followSystemTheme(themeInstance) {
  systemQuery().addEventListener('change', () => {
    if (getThemePreference() === 'system') apply(resolve('system'), themeInstance)
  })
}
