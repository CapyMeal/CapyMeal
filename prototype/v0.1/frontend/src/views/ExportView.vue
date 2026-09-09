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
          <p><strong>☀️ {{ t('meals.breakfast') }}:</strong> {{ entry.breakfast || t('common.notRecorded') }}</p>
          <p><strong>🍝 {{ t('meals.lunch') }}:</strong> {{ entry.lunch || t('common.notRecorded') }}</p>
          <p><strong>🧁 {{ t('meals.snack') }}:</strong> {{ entry.snack || t('common.notRecorded') }}</p>
          <p><strong>🌙 {{ t('meals.dinner') }}:</strong> {{ entry.dinner || t('common.notRecorded') }}</p>
          <p v-if="entry.notes"><strong>📝 {{ t('export.notesLabel') }}:</strong> {{ entry.notes }}</p>
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
import { exportMealEntriesPdf } from '../services/mealEntriesApi'
import { formatDate as formatDateUtil } from '../utils/date'
import { useMealEntriesByRange } from '../utils/useMealEntriesByRange'

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
    link.download = 'capymeal-diario.pdf'
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
  filter: drop-shadow(0 2px 6px rgba(169,130,116,.2));
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
  text-transform: capitalize;
  margin-bottom: var(--space-sm);
  color: var(--color-title);
}

.export-card p {
  line-height: 1.7;
  margin-bottom: 2px;
}

.export-button {
  margin-bottom: var(--space-lg);
}

.export-error {
  font-size: .9rem;
  margin-bottom: var(--space-md);
  color: var(--color-danger);
}

</style>