import { ref, watch, onMounted } from 'vue'
import { getMealEntries, isNetworkError } from '../services/mealEntriesApi'

// Compartido entre DiaryView y ExportView, que repetían casi verbatim el
// mismo estado (entries/loading/errorMessage/fromDate/toDate) y el mismo
// guard de rango inválido -- los mensajes de error quedan a cargo de cada
// vista porque el tono es distinto ("cargar el diario" vs "cargar los
// registros para exportar").
export function useMealEntriesByRange({ networkErrorMessage, genericErrorMessage }) {
  const entries = ref([])
  const loading = ref(false)
  const errorMessage = ref('')
  const fromDate = ref('')
  const toDate = ref('')

  async function loadEntries() {
    // Mientras se edita el rango con los selectores de fecha, puede haber un
    // instante con "desde" posterior a "hasta" -- el backend lo rechazaría
    // con un 422. Se espera a que el rango vuelva a ser válido en vez de
    // mostrar un error por algo que todavía se está terminando de elegir.
    if (fromDate.value && toDate.value && fromDate.value > toDate.value) {
      return
    }

    loading.value = true
    errorMessage.value = ''

    try {
      const data = await getMealEntries({ from: fromDate.value, to: toDate.value })
      entries.value = data.map((entry) => ({ date: entry.date, entry }))
    } catch (error) {
      errorMessage.value = isNetworkError(error) ? networkErrorMessage : genericErrorMessage
    } finally {
      loading.value = false
    }
  }

  onMounted(loadEntries)
  watch([fromDate, toDate], loadEntries)

  return { entries, loading, errorMessage, fromDate, toDate, loadEntries }
}
