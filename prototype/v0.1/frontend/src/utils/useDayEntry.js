import { ref, reactive, unref } from 'vue'
import { getMealEntry, upsertMealEntry, isNetworkError } from '../services/mealEntriesApi'
import { MEAL_KEYS } from './meals'

const FIELD_KEYS = [...MEAL_KEYS, 'notes']
const SAVED_FLASH_MS = 2500

function emptyDay() {
  return Object.fromEntries(FIELD_KEYS.map((key) => [key, '']))
}

// Carga un día y guarda cada campo al salir de él. Lo comparten "Hoy" y el
// Detalle de un día, que antes tenían dos modelos de edición distintos (en
// el Detalle, un editor por tarjeta que no dejaba vaciar un campo y un modo
// "Editar" aparte). Ahora el Detalle es "Hoy" de otra fecha.
//
// `date` es un getter (la fecha de "Hoy" cambia con el selector; la del
// Detalle es fija). Los mensajes los resuelve cada vista, porque el tono y
// las claves de traducción son distintos.
export function useDayEntry({ date, messages }) {
  const form = reactive(emptyDay())
  // Último valor confirmado por el servidor, por campo. Sirve para guardar
  // solo lo que cambió, incluido vaciar un campo que tenía texto.
  const saved = reactive(emptyDay())

  const loading = ref(false)
  const errorMessage = ref('')
  const savingKey = ref('')
  const recentlySaved = ref(new Set())

  function setAll(target, values) {
    for (const key of FIELD_KEYS) target[key] = values?.[key] || ''
  }

  function reset() {
    setAll(form, null)
    setAll(saved, null)
    errorMessage.value = ''
    recentlySaved.value = new Set()
  }

  async function load() {
    // Se limpia antes de esperar la respuesta: si no, mientras carga el día
    // nuevo se seguirían viendo los datos del anterior.
    reset()
    loading.value = true

    try {
      // null cuando todavía no hay nada guardado para ese día.
      const entry = await getMealEntry(unref(date()))
      setAll(form, entry)
      setAll(saved, entry)
      return entry
    } catch (error) {
      errorMessage.value = messages.load(isNetworkError(error))
      return undefined
    } finally {
      loading.value = false
    }
  }

  function flashSaved(key) {
    recentlySaved.value = new Set([...recentlySaved.value, key])
    setTimeout(() => {
      const next = new Set(recentlySaved.value)
      next.delete(key)
      recentlySaved.value = next
    }, SAVED_FLASH_MS)
  }

  function wouldEmptyTheDay(key) {
    return FIELD_KEYS.every((k) => (k === key ? '' : saved[k]).trim() === '')
  }

  async function saveField(key) {
    const value = form[key] || ''

    // Sin cambios (incluye campo vacío que ya estaba vacío): nada que hacer.
    if (value.trim() === saved[key].trim()) return

    // El backend exige al menos un campo con contenido. Vaciar el último no
    // es "borrar el día" por accidente: se avisa y se deja el texto escrito.
    if (value.trim() === '' && wouldEmptyTheDay(key)) {
      errorMessage.value = messages.lastField()
      form[key] = saved[key]
      return
    }

    savingKey.value = key
    errorMessage.value = ''

    try {
      await upsertMealEntry({ date: unref(date()), [key]: value })
      saved[key] = value
      flashSaved(key)
    } catch (error) {
      errorMessage.value = messages.save(isNetworkError(error), key)
    } finally {
      savingKey.value = ''
    }
  }

  function hasAnyContent() {
    return FIELD_KEYS.some((key) => (form[key] || '').trim().length > 0)
  }

  // Después de guardar el día entero desde afuera ("Cerrar el día").
  function markAllSaved() {
    setAll(saved, form)
  }

  return {
    form,
    loading,
    errorMessage,
    savingKey,
    recentlySaved,
    load,
    reset,
    saveField,
    hasAnyContent,
    markAllSaved,
  }
}
