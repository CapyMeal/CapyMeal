<template>
  <MainLayout>
    <div class="export-heading">
      <img src="../assets/icons/pdf.png" alt="" class="export-heading__icon">
      <h1 class="export-heading__title">Exportar PDF</h1>
    </div>

    <div class="export-filters">
      <div class="export-field">
        <label for="export-from">Desde</label>
        <input id="export-from" v-model="fromDate" type="date">
      </div>
      <div class="export-field">
        <label for="export-to">Hasta</label>
        <input id="export-to" v-model="toDate" type="date">
      </div>
    </div>

    <p v-if="errorMessage" class="export-error">{{ errorMessage }}</p>
    <p v-if="loading" class="export-loading">Cargando registros para exportar...</p>

    <EmptyState
      v-else-if="filteredEntries.length === 0"
      message="No encontré registros para esas fechas."
    />

    <div v-else-if="!loading" class="export-preview" id="print-section">
      <article
        v-for="{ date, entry } in filteredEntries"
        :key="date"
        class="export-card"
      >
        <h2>{{ formatDate(date) }}</h2>
        <p><strong>☀️ Desayuno:</strong> {{ entry.breakfast || 'No registrado' }}</p>
        <p><strong>🍝 Almuerzo:</strong> {{ entry.lunch || 'No registrado' }}</p>
        <p><strong>🧁 Merienda:</strong> {{ entry.snack || 'No registrado' }}</p>
        <p><strong>🌙 Cena:</strong> {{ entry.dinner || 'No registrado' }}</p>
        <p v-if="entry.notes"><strong>📝 Recuerdo:</strong> {{ entry.notes }}</p>
      </article>
    </div>

    <CapyButton
      v-if="!loading && filteredEntries.length > 0"
      class="export-button"
      :disabled="exporting"
      @click="printPdf"
    >
      {{ exporting ? 'Preparando PDF...' : '🩷 Descargar PDF' }}
    </CapyButton>
  </MainLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import MainLayout from '../layouts/MainLayout.vue'
import EmptyState from '../components/diary/EmptyState.vue'
import CapyButton from '../components/base/CapyButton.vue'
import { exportMealEntriesPdf, getMealEntries } from '../services/mealEntriesApi'

const entries = ref([])
const loading = ref(false)
const exporting = ref(false)
const errorMessage = ref('')
const fromDate = ref('')
const toDate = ref('')

const filteredEntries = computed(() =>
  entries.value.filter(({ date }) => {
    if (fromDate.value && date < fromDate.value) {
      return false
    }

    if (toDate.value && date > toDate.value) {
      return false
    }

    return true
  })
)

onMounted(async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const data = await getMealEntries()
    entries.value = data.map((entry) => ({ date: entry.date, entry }))
  } catch {
    errorMessage.value = 'No pude cargar los registros para exportar.'
  } finally {
    loading.value = false
  }
})

function formatDate(date) {
  const [year, month, day] = date.split('-').map(Number)
  return new Date(year, month - 1, day).toLocaleDateString('es-AR', {
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

.export-filters {
  display: grid;
  gap: var(--space-sm);
  grid-template-columns: 1fr 1fr;
  margin-bottom: var(--space-lg);
}

.export-field label {
  display: block;
  font-size: .8rem;
  font-weight: 700;
  color: var(--color-primary);
  margin-bottom: 4px;
}

.export-field input {
  width: 100%;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  color: var(--color-text);
  padding: var(--space-xs) var(--space-sm);
}

.export-preview {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

.export-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
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
  margin-top: var(--space-lg);
}

.export-error {
  font-size: .9rem;
  margin-bottom: var(--space-md);
  color: var(--color-danger);
}

.export-loading {
  font-size: .88rem;
  margin-bottom: var(--space-md);
  opacity: .8;
}
</style>