// Lee el locale actual del plugin de i18n en vez de recibirlo como
// parámetro en cada llamada -- así ningún llamador tiene que pasarlo a
// mano, y formatDate() sigue al toggle de idioma sin más cambios.
import i18n from '../plugins/i18n'

// Convierte una fecha ISO ("YYYY-MM-DD") a un Date local. `new Date(str)`
// a secas la interpreta como medianoche UTC, lo que corta al día anterior
// en cualquier huso horario negativo (todo Argentina) -- este parseo
// manual evita ese corrimiento.
export function parseISODate(dateStr) {
  const [year, month, day] = dateStr.split('-').map(Number)
  return new Date(year, month - 1, day)
}

// Formatea una fecha ISO en el idioma activo, con las opciones de
// Intl.DateTimeFormat que pida cada pantalla (algunas quieren día de la
// semana, otras no).
export function formatDate(dateStr, options) {
  const intlLocale = i18n.global.locale.value === 'en' ? 'en-US' : 'es-AR'
  return parseISODate(dateStr).toLocaleDateString(intlLocale, options)
}

// Serializa un Date a "YYYY-MM-DD" en hora local (Date#toISOString() usa
// UTC, que corta al día anterior en cualquier huso horario negativo --
// mismo problema que parseISODate resuelve en el sentido inverso).
export function formatDateISO(date) {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

// Suma (o resta, con `days` negativo) días a una fecha ISO, devolviendo
// otra fecha ISO.
export function addDays(dateStr, days) {
  const date = parseISODate(dateStr)
  date.setDate(date.getDate() + days)
  return formatDateISO(date)
}
