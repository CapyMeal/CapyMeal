<template>
  <MainLayout>
    <h1 class="diary-title">📖 Mi Diario</h1>

    <div class="diary-filters">
      <div class="diary-filters__field">
        <label for="from-date">Desde</label>
        <input id="from-date" v-model="fromDate" type="date">
      </div>
      <div class="diary-filters__field">
        <label for="to-date">Hasta</label>
        <input id="to-date" v-model="toDate" type="date">
      </div>
    </div>

    <p v-if="errorMessage" class="diary-error">{{ errorMessage }}</p>
    <p v-if="loading" class="diary-loading">Cargando tu diario...</p>

    <EmptyState
      v-else-if="entries.length === 0"
      message="Todavía no guardamos ningún recuerdo."
      action-label="Registrar mi primer día"
      @action="$router.push('/hoy')"
    />

    <EmptyState
      v-else-if="!loading && filteredEntries.length === 0"
      message="No encontré registros para esas fechas."
    />

    <div v-else-if="!loading" class="diary-list">
      <DiaryCard
        v-for="{ date, entry } in filteredEntries"
        :key="date"
        :date="date"
        :entry="entry"
      />
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import MainLayout from '../layouts/MainLayout.vue'
import DiaryCard  from '../components/diary/DiaryCard.vue'
import EmptyState from '../components/diary/EmptyState.vue'
import { getMealEntries } from '../services/mealEntriesApi'

const entries = ref([])
const loading = ref(false)
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
    entries.value = data.map((entry) => ({
      date: entry.date,
      entry,
    }))
  } catch {
    errorMessage.value = 'No pude cargar el diario. Intentá nuevamente.'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.diary-title {
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--color-title);
  margin-bottom: var(--space-xl);
}

.diary-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

.diary-filters {
  display: grid;
  gap: var(--space-sm);
  grid-template-columns: 1fr 1fr;
  margin-bottom: var(--space-lg);
}

.diary-filters__field label {
  display: block;
  font-size: .8rem;
  font-weight: 700;
  color: var(--color-primary);
  margin-bottom: 4px;
}

.diary-filters__field input {
  width: 100%;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  color: var(--color-text);
  padding: var(--space-xs) var(--space-sm);
}

.diary-error {
  font-size: .9rem;
  margin-bottom: var(--space-md);
  color: var(--color-danger);
}

.diary-loading {
  font-size: .88rem;
  margin-bottom: var(--space-md);
  opacity: .8;
}
</style>