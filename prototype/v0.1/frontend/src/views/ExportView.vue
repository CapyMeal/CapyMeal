<template>
  <MainLayout>
    <PageHeading :icon="pdfIllustration" :title="t('export.title')" />

    <!-- Atajos para el caso real: llevar "el último mes" a la consulta sin
         tener que escribir dos fechas a mano. -->
    <div class="export-ranges" role="group" :aria-label="t('export.rangesLabel')">
      <button
        v-for="range in quickRanges"
        :key="range.key"
        type="button"
        class="export-ranges__chip"
        :aria-pressed="selectedChip === range.key"
        @click="applyRange(range)"
      >
        {{ range.label }}
      </button>
      <button
        type="button"
        class="export-ranges__chip"
        :aria-pressed="selectedChip === 'custom'"
        aria-controls="export-dates"
        @click="customOpen = true"
      >
        {{ t('export.rangeCustom') }}
      </button>
    </div>

    <!-- Las fechas a mano solo cuando hacen falta: con "Todo" elegido, dos
         campos "dd/mm/aaaa" vacíos al lado parecían algo sin terminar. -->
    <div v-if="selectedChip !== 'all'" id="export-dates">
      <DateRangeFilter v-model:from="fromDate" v-model:to="toDate" />
    </div>

    <StatusAlert
      v-if="exportedNotice"
      type="success"
      :message="exportedNotice"
      class="export-status"
    />

    <StatusAlert
      v-if="errorMessage"
      :message="errorMessage"
      retryable
      class="export-status"
      @retry="loadEntries"
    />
    <StatusAlert
      v-if="exportError"
      :message="exportError"
      retryable
      class="export-status"
      @retry="printPdf"
    />
    <CapyLoader v-if="loading" :message="t('export.loading')" />

    <!-- Qué se va a exportar, dicho antes de apretar: con las fechas vacías
         baja todo el diario y nada lo decía. -->
    <template v-if="!loading && entries.length > 0">
      <p class="export-summary">{{ rangeSummary }}</p>
      <CapyButton
        class="export-button"
        :disabled="exporting"
        @click="printPdf"
      >
        {{ exporting ? t('common.preparingPdf') : t('export.downloadButton', entries.length) }}
      </CapyButton>
    </template>

    <!-- Sin fechas elegidas, vacío significa que todavía no hay nada en el
         diario: mismo mensaje y misma salida que el Diario, en vez de
         "No encontré registros para esas fechas" sin haber elegido fechas. -->
    <EmptyState
      v-if="!loading && !errorMessage && entries.length === 0 && !hasDateFilter"
      :message="t('diary.emptyMessage')"
      :action-label="t('diary.emptyAction')"
      @action="$router.push('/hoy')"
    />

    <EmptyState
      v-else-if="!loading && !errorMessage && entries.length === 0"
      :message="t('export.emptyMessage')"
    />

    <div v-if="!loading && entries.length > 0" id="print-section" class="export-preview">
      <v-card
        v-for="{ date, entry } in entries"
        :key="date"
        class="export-card"
        elevation="1"
      >
        <v-card-text>
          <h2>{{ formatDate(date) }}</h2>
          <p v-for="key in MEAL_KEYS" :key="key" class="export-card__row">
            <MealIcon :meal="key" :size="20" />
            <span><strong>{{ t(`meals.${key}`) }}:</strong> {{ entry[key] || t('common.notRecorded') }}</span>
          </p>
          <p v-if="entry.notes" class="export-card__row">
            <MealIcon meal="notes" :size="20" />
            <span><strong>{{ t('export.notesLabel') }}:</strong> {{ entry.notes }}</span>
          </p>
        </v-card-text>
      </v-card>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import MainLayout      from '../layouts/MainLayout.vue'
import EmptyState      from '../components/diary/EmptyState.vue'
import CapyButton      from '../components/base/CapyButton.vue'
import DateRangeFilter from '../components/base/DateRangeFilter.vue'
import CapyLoader      from '../components/base/CapyLoader.vue'
import MealIcon        from '../components/meal/MealIcon.vue'
import PageHeading     from '../components/base/PageHeading.vue'
import StatusAlert     from '../components/base/StatusAlert.vue'
import pdfIllustration from '../assets/icons/pdf.png'
import { exportMealEntriesPdf } from '../services/mealEntriesApi'
import { formatDate as formatDateUtil, formatDateISO, addDays } from '../utils/date'
import { useMealEntriesByRange } from '../utils/useMealEntriesByRange'
import { MEAL_KEYS } from '../utils/meals'

const { t } = useI18n()

const { entries, loading, errorMessage, fromDate, toDate, hasDateFilter, loadEntries } = useMealEntriesByRange({
  networkErrorMessage: computed(() => t('export.networkError')),
  genericErrorMessage: computed(() => t('export.genericError')),
})

const exporting = ref(false)
// Separado del error de carga: cada uno reintenta una cosa distinta
// (volver a pedir los días o volver a generar el PDF).
const exportError = ref('')
// En el .apk de Android la descarga puede no verse: sin este aviso, el
// recorrido que termina en la consulta médica terminaba en silencio.
const exportedNotice = ref('')

const todayISO = formatDateISO(new Date())

const quickRanges = computed(() => [
  { key: 'month',  label: t('export.rangeLastMonth'),       from: addDays(todayISO, -30) },
  { key: 'months', label: t('export.rangeLastThreeMonths'), from: addDays(todayISO, -90) },
  { key: 'all',    label: t('export.rangeAll'),             from: '' },
])

const activeRange = computed(() => {
  if (!fromDate.value && !toDate.value) return 'all'
  const match = quickRanges.value.find((r) => r.from && r.from === fromDate.value && toDate.value === todayISO)
  return match?.key ?? ''
})

// "Elegir fechas" abierto a mano, o un rango que no coincide con ningún
// atajo (por ejemplo, fechas que ya venían elegidas).
const customOpen = ref(activeRange.value === '')

const selectedChip = computed(() =>
  customOpen.value || activeRange.value === '' ? 'custom' : activeRange.value
)

function applyRange(range) {
  customOpen.value = false
  fromDate.value = range.from
  toDate.value   = range.from ? todayISO : ''
}

function shortDate(date, withYear) {
  return formatDateUtil(date, { day: 'numeric', month: 'long', ...(withYear && { year: 'numeric' }) })
}

const rangeSummary = computed(() => {
  const dates = entries.value.map((e) => e.date).sort()
  if (dates.length === 0) return ''

  const first = dates[0]
  const last  = dates.at(-1)
  // El año solo cuando el rango cruza años; si no, es ruido.
  const withYear = first.slice(0, 4) !== last.slice(0, 4)

  if (dates.length === 1) return t('export.summaryOne', { from: shortDate(first, true) })

  const params = {
    days: t('export.daysCount', dates.length),
    from: shortDate(first, withYear),
    to:   shortDate(last, withYear),
  }
  return hasDateFilter.value ? t('export.summaryRange', params) : t('export.summaryAll', params)
})

// Cambiar el rango invalida el aviso del PDF anterior.
watch([fromDate, toDate], () => { exportedNotice.value = '' })

function formatDate(date) {
  return formatDateUtil(date, {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

async function printPdf() {
  exporting.value = true
  exportError.value = ''
  exportedNotice.value = ''

  try {
    const blob = await exportMealEntriesPdf({
      from: fromDate.value,
      to: toDate.value,
    })

    const fileURL = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = fileURL
    link.download = t('export.downloadFilename')
    link.click()
    window.URL.revokeObjectURL(fileURL)
    exportedNotice.value = t('export.downloaded')
  } catch {
    exportError.value = t('export.genericExportError')
  } finally {
    exporting.value = false
  }
}
</script>

<style scoped>
.export-preview {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

.export-card :deep(.v-card-text) {
  padding: var(--space-md);
}

/* Mismo tamaño que los títulos de tarjeta del resto de la app: con el h2
   por defecto la fecha completa ocupaba dos renglones en el celular. */
.export-card h2 {
  font-size: var(--font-size-title-sm);
  margin-bottom: var(--space-sm);
  color: var(--color-title);
  text-wrap: balance;
}

/* Solo la primera letra: capitalize ponía "12 De Noviembre". */
.export-card h2::first-letter {
  text-transform: uppercase;
}

.export-card__row {
  display: flex;
  align-items: flex-start;
  gap: var(--space-xs);
  line-height: 1.7;
  margin-bottom: 2px;
}

/* Alinea la ilustración con la primera línea de texto, no con el centro
   de un párrafo que puede ocupar varias. */
.export-card__row .meal-icon {
  margin-top: .2em;
}

.export-ranges {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-xs);
  margin-bottom: var(--space-md);
}

/* Mismo chip que las fechas rápidas de "Cambiar día"; el elegido se
   marca con fondo y borde, no solo con color. */
.export-ranges__chip {
  min-height: 40px;
  padding: 0 var(--space-md);
  border: 1.5px solid var(--color-border);
  border-radius: 999px;
  background: var(--color-background);
  color: var(--color-text);
  font-size: var(--font-size-label);
  cursor: pointer;
  transition: background .2s, border-color .2s;
}

.export-ranges__chip:hover {
  border-color: var(--color-primary);
}

.export-ranges__chip[aria-pressed="true"] {
  border-color: var(--color-primary);
  background: color-mix(in srgb, var(--color-primary) 14%, transparent);
  color: var(--color-primary-text);
  font-weight: 600;
}

.export-summary {
  font-size: var(--font-size-label);
  color: var(--color-muted);
  margin-bottom: var(--space-sm);
  text-wrap: pretty;
}

.export-button {
  margin-bottom: var(--space-lg);
}

.export-status {
  margin-bottom: var(--space-md);
}

</style>