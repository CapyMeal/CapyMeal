<template>
  <MainLayout>
    <div class="export-heading">
      <img src="../assets/icons/pdf.png" alt="" class="export-heading__icon">
      <h1 class="export-heading__title">Exportar PDF</h1>
    </div>

    <DateRangeFilter v-model:from="fromDate" v-model:to="toDate" />

    <p v-if="errorMessage" class="export-error">{{ errorMessage }}</p>
    <CapyLoader v-if="loading" message="Cargando registros para exportar..." />

    <CapyButton
      v-if="!loading && entries.length > 0"
      class="export-button"
      :disabled="exporting"
      @click="printPdf"
    >
      {{ exporting ? 'Preparando PDF...' : '🤎 Descargar PDF' }}
    </CapyButton>

    <EmptyState
      v-else-if="entries.length === 0"
      message="No encontré registros para esas fechas."
    />

    <div v-else-if="!loading" id="print-section" class="export-preview">
      <v-card
        v-for="{ date, entry } in entries"
        :key="date"
        class="export-card"
        elevation="1"
      >
        <v-card-text>
          <h2>{{ formatDate(date) }}</h2>
          <p><strong>☀️ Desayuno:</strong> {{ entry.breakfast || 'No registrado' }}</p>
          <p><strong>🍝 Almuerzo:</strong> {{ entry.lunch || 'No registrado' }}</p>
          <p><strong>🧁 Merienda:</strong> {{ entry.snack || 'No registrado' }}</p>
          <p><strong>🌙 Cena:</strong> {{ entry.dinner || 'No registrado' }}</p>
          <p v-if="entry.notes"><strong>📝 Recuerdo:</strong> {{ entry.notes }}</p>
        </v-card-text>
      </v-card>
    </div>
  </MainLayout>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import MainLayout      from '../layouts/MainLayout.vue'
import EmptyState      from '../components/diary/EmptyState.vue'
import CapyButton      from '../components/base/CapyButton.vue'
import DateRangeFilter from '../components/base/DateRangeFilter.vue'
import CapyLoader      from '../components/base/CapyLoader.vue'
import { exportMealEntriesPdf, getMealEntries } from '../services/mealEntriesApi'
import { formatDateEs } from '../utils/date'

const entries = ref([])
const loading = ref(false)
const exporting = ref(false)
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
  } catch {
    errorMessage.value = 'No pude cargar los registros para exportar.'
  } finally {
    loading.value = false
  }
}

onMounted(loadEntries)
watch([fromDate, toDate], loadEntries)

function formatDate(date) {
  return formatDateEs(date, {
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
    errorMessage.value = 'No pude generar el PDF. Intentá nuevamente.'
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