// Mismo patrón que theme.js: localStorage manda si hay algo guardado: si
// no, la señal del sistema (acá, el idioma del navegador) decide el
// default -- la base de usuarios actual es hispanohablante, así que
// cualquier cosa que no sea explícitamente inglés cae a español.
export function getInitialLocale() {
  const saved = localStorage.getItem('capymeal-locale')
  if (saved) {
    return saved
  }

  return navigator.language.toLowerCase().startsWith('en') ? 'en' : 'es'
}
