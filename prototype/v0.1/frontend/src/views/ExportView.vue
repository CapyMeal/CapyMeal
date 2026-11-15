<template>
  <MainLayout>
    <PageHeading :icon="pdfIllustration" :title="t('export.title')" />

    <DateRangeFilter v-model:from="fromDate" v-model:to="toDate" />

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

    <CapyButton
      v-if="!loading && entries.length > 0"
      class="export-button"
      :disabled="exporting"
      @click="printPdf"
    >
      {{ exporting ? t('common.preparingPdf') : t('export.downloadButton') }}
    </CapyButton>

    <!-- Sin fechas elegidas, vacío significa que todavía no hay nada en el
         diario: mismo mensaje y misma salida que el Diario, en vez de
         "No encontré registros para esas fechas" sin haber elegido fechas. -->
    <EmptyState
      v-if="!loading && entries.length === 0 && !hasDateFilter"
      :message="t('diary.emptyMessage')"
      :action-label="t('diary.emptyAction')"
      @action="$router.push('/hoy')"
    />

    <EmptyState
      v-else-if="!loading && entries.length === 0"
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
import { ref, computed } from 'vue'
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
import { formatDate as formatDateUtil } from '../utils/date'
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

.export-card h2 {
  margin-bottom: var(--space-sm);
  color: var(--color-title);
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

.export-button {
  margin-bottom: var(--space-lg);
}

.export-status {
  margin-bottom: var(--space-md);
}

</style>