// Convierte una fecha ISO ("YYYY-MM-DD") a un Date local. `new Date(str)`
// a secas la interpreta como medianoche UTC, lo que corta al día anterior
// en cualquier huso horario negativo (todo Argentina) -- este parseo
// manual evita ese corrimiento.
export function parseISODate(dateStr) {
  const [year, month, day] = dateStr.split('-').map(Number)
  return new Date(year, month - 1, day)
}

// Formatea una fecha ISO en español, con las opciones de
// Intl.DateTimeFormat que pida cada pantalla (algunas quieren día de la
// semana, otras no).
export function formatDateEs(dateStr, options) {
  return parseISODate(dateStr).toLocaleDateString('es-AR', options)
}
