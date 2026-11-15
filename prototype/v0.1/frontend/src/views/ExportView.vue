<template>
  <MainLayout>
    <div class="export-heading">
      <img src="../assets/icons/pdf.png" alt="" class="export-heading__icon">
      <h1 class="export-heading__title">{{ t('export.title') }}</h1>
    </div>

    <DateRangeFilter v-model:from="fromDate" v-model:to="toDate" />

    <p v-if="errorMessage" class="export-error">{{ errorMessage }}</p>
    <CapyLoader v-if="loading" :message="t('export.loading')" />

    <CapyButton
      v-if="!loading && entries.length > 0"
      class="export-button"
      :disabled="exporting"
      @click="printPdf"
    >
      {{ exporting ? t('common.preparingPdf') : t('export.downloadButton') }}
    </CapyButton>

    <EmptyState
      v-if="!loading && entries.length === 0"
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
import { exportMealEntriesPdf } from '../services/mealEntriesApi'
import { formatDate as formatDateUtil } from '../utils/date'
import { useMealEntriesByRange } from '../utils/useMealEntriesByRange'
import { MEAL_KEYS } from '../utils/meals'

const { t } = useI18n()

const { entries, loading, errorMessage, fromDate, toDate } = useMealEntriesByRange({
  networkErrorMessage: computed(() => t('export.networkError')),
  genericErrorMessage: computed(() => t('export.genericError')),
})

const exporting = ref(false)

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
  errorMessage.value = ''

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
    errorMessage.value = t('export.genericExportError')
  } finally {
    exporting.value = false
  }
}
</script>

<style scoped>
.export-heading {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-lg);
}

.export-heading__icon {
  width: 44px;
  height: 44px;
  object-fit: contain;
  filter: var(--illustration-shadow-sm);
}

.export-heading__title {
  font-family: var(--font-title);
  font-size: 1.6rem;
  font-weight: 700;
  color: var(--color-title);
}

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

.export-error {
  font-size: .9rem;
  margin-bottom: var(--space-md);
  color: var(--color-danger-text);
}

</style>