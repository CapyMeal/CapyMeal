import { ref, reactive, unref, watch, onMounted, onBeforeUnmount } from 'vue'
import { getMealEntry, upsertMealEntry, isNetworkError } from '../services/mealEntriesApi'
import { MEAL_KEYS } from './meals'

const FIELD_KEYS = [...MEAL_KEYS, 'notes']
const SAVED_FLASH_MS = 2500
// Pausa al escribir antes de guardar: lo bastante larga para no mandar un
// pedido por tecla, lo bastante corta para que cambiar de app a mitad de
// una frase no pierda casi nada.
const TYPING_SAVE_MS = 1200

function emptyDay() {
  return Object.fromEntries(FIELD_KEYS.map((key) => [key, '']))
}

// Carga un día y guarda cada campo solo. Lo comparten "Hoy" y el Detalle
// de un día, que es "Hoy" de otra fecha.
//
// Se guarda en tres momentos: tras una pausa al escribir, al salir del
// campo y cuando la app pasa a segundo plano o se deja la pantalla. Antes
// solo al salir del campo: escribir y saltar a otra app (o que Android
// cerrara la del .apk) perdía el texto.
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
  // Campo al que pertenece el error, para mostrarlo en su tarjeta. Vacío
  // cuando el error es del día entero (cargar, "Cerrar el día").
  const errorKey = ref('')
  const savingKeys = ref(new Set())
  const recentlySaved = ref(new Set())

  const typingTimers = new Map()
  // Un guardado por campo a la vez y en orden: si dos pedidos del mismo
  // campo viajaran juntos, el más viejo podría llegar último y pisar al
  // nuevo.
  const inFlight = new Map()

  function setAll(target, values) {
    for (const key of FIELD_KEYS) target[key] = values?.[key] || ''
  }

  function clearError() {
    errorMessage.value = ''
    errorKey.value = ''
  }

  function setError(message, key = '') {
    errorMessage.value = message
    errorKey.value = key
  }

  function cancelTypingSaves() {
    for (const { timer } of typingTimers.values()) clearTimeout(timer)
    typingTimers.clear()
  }

  function reset() {
    cancelTypingSaves()
    setAll(form, null)
    setAll(saved, null)
    clearError()
    recentlySaved.value = new Set()
  }

  async function load() {
    // Lo pendiente del día anterior se guarda antes de cambiar: reset()
    // borra el formulario.
    await flushPending()
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
      setError(messages.load(isNetworkError(error)))
      return undefined
    } finally {
      loading.value = false
    }
  }

  function withKey(set, key, present) {
    const next = new Set(set)
    if (present) next.add(key)
    else next.delete(key)
    return next
  }

  function flashSaved(key) {
    recentlySaved.value = withKey(recentlySaved.value, key, true)
    setTimeout(() => {
      recentlySaved.value = withKey(recentlySaved.value, key, false)
    }, SAVED_FLASH_MS)
  }

  function wouldEmptyTheDay(key) {
    return FIELD_KEYS.every((k) => (k === key ? '' : saved[k]).trim() === '')
  }

  // `fromTyping`: el guardado lo disparó una pausa al escribir, no el
  // salir del campo. Ahí borrar todo el texto puede ser un paso intermedio
  // (borrar para reescribir), así que no se avisa ni se restaura nada.
  //
  // `day`: la fecha en que se escribió. Al cambiar de día en "Hoy", lo
  // pendiente se guarda en el día anterior y no en el nuevo.
  async function saveField(key, { fromTyping = false, day: pendingDay } = {}) {
    const pending = typingTimers.get(key)
    clearTimeout(pending?.timer)
    typingTimers.delete(key)
    const day = pendingDay ?? pending?.day ?? unref(date())

    // Si ya hay un guardado de este campo en viaje, se espera y recién
    // entonces se compara: puede que ya no quede nada por guardar.
    await inFlight.get(key)

    const value = form[key] || ''

    // Sin cambios (incluye campo vacío que ya estaba vacío): nada que hacer.
    if (value.trim() === saved[key].trim()) return

    // El backend exige al menos un campo con contenido. Vaciar el último no
    // es "borrar el día" por accidente: se avisa y se deja el texto escrito.
    if (value.trim() === '' && wouldEmptyTheDay(key)) {
      if (fromTyping) return
      setError(messages.lastField(), key)
      form[key] = saved[key]
      return
    }

    savingKeys.value = withKey(savingKeys.value, key, true)
    if (errorKey.value === key || !errorKey.value) clearError()

    const request = upsertMealEntry({ date: day, [key]: value })
      .then(() => {
        saved[key] = value
        flashSaved(key)
      })
      .catch((error) => {
        setError(messages.save(isNetworkError(error), key), key)
      })
      .finally(() => {
        savingKeys.value = withKey(savingKeys.value, key, false)
        inFlight.delete(key)
      })

    inFlight.set(key, request)
    await request
  }

  function scheduleSave(key) {
    const day = typingTimers.get(key)?.day ?? unref(date())
    clearTimeout(typingTimers.get(key)?.timer)
    const timer = setTimeout(() => saveField(key, { fromTyping: true }), TYPING_SAVE_MS)
    typingTimers.set(key, { timer, day })
  }

  // Guarda ya lo que estaba esperando la pausa de escritura.
  function flushPending() {
    const keys = [...typingTimers.keys()]
    return Promise.all(keys.map((key) => saveField(key, { fromTyping: true })))
  }

  for (const key of FIELD_KEYS) {
    watch(() => form[key], (value) => {
      if ((value || '').trim() !== saved[key].trim()) scheduleSave(key)
    })
  }

  function onVisibilityChange() {
    if (document.visibilityState === 'hidden') flushPending()
  }

  onMounted(() => document.addEventListener('visibilitychange', onVisibilityChange))
  onBeforeUnmount(() => {
    document.removeEventListener('visibilitychange', onVisibilityChange)
    // Dejar la pantalla (ir al Diario, volver atrás) no pierde lo último.
    flushPending()
  })

  function hasAnyContent() {
    return FIELD_KEYS.some((key) => (form[key] || '').trim().length > 0)
  }

  // Campo con texto ya confirmado por el servidor y sin cambios encima.
  function isFieldSaved(key) {
    const value = (form[key] || '').trim()
    return value !== '' && value === saved[key].trim()
  }

  // Después de guardar el día entero desde afuera ("Cerrar el día").
  function markAllSaved() {
    cancelTypingSaves()
    setAll(saved, form)
  }

  return {
    form,
    loading,
    errorMessage,
    errorKey,
    savingKeys,
    recentlySaved,
    load,
    reset,
    saveField,
    flushPending,
    hasAnyContent,
    isFieldSaved,
    markAllSaved,
    setError,
    clearError,
  }
}
