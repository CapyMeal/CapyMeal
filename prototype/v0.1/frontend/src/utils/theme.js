// Antes esta lógica estaba duplicada en main.js y en plugins/vuetify.js,
// las dos con el mismo default hardcodeado a 'dark' -- eso hacía que la
// app siempre abriera en oscuro en un dispositivo nuevo (sin preferencia
// guardada todavía), incluso si el sistema operativo del usuario estaba
// en modo claro. Ahora ambos archivos usan esta única función.
export function getInitialTheme() {
  const saved = localStorage.getItem('capymeal-theme')
  if (saved) {
    return saved
  }

  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}
