<template>
  <MainLayout>
    <div class="diary-heading">
      <img src="../assets/icons/diario.png" alt="" class="diary-heading__icon">
      <h1 class="diary-heading__title">Mi Diario</h1>
    </div>

    <DateRangeFilter v-model:from="fromDate" v-model:to="toDate" />

    <p v-if="errorMessage" class="diary-error">{{ errorMessage }}</p>
    <CapyLoader v-if="loading" message="Cargando tu diario..." />

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
import MainLayout       from '../layouts/MainLayout.vue'
import DiaryCard        from '../components/diary/DiaryCard.vue'
import EmptyState       from '../components/diary/EmptyState.vue'
import DateRangeFilter  from '../components/base/DateRangeFilter.vue'
import CapyLoader       from '../components/base/CapyLoader.vue'
import { getMealEntries, isNetworkError } from '../services/mealEntriesApi'

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
  } catch (error) {
    // Nota: si el service worker ya tenía este pedido cacheado (visita
    // previa con conexión), la respuesta se sirve desde el caché y este
    // catch ni se dispara. Solo llega hasta acá si nunca se había cargado
    // nada en este dispositivo.
    errorMessage.value = isNetworkError(error)
      ? 'No pude cargar el diario: estás sin conexión.'
      : 'No pude cargar el diario. Intentá nuevamente.'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.diary-heading {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-xl);
}

.diary-heading__icon {
  width: 44px;
  height: 44px;
  object-fit: contain;
  filter: drop-shadow(0 2px 6px rgba(169,130,116,.2));
}

.diary-heading__title {
  font-family: var(--font-title);
  font-size: 1.6rem;
  font-weight: 700;
  color: var(--color-title);
}

.diary-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

.diary-error {
  font-size: .9rem;
  margin-bottom: var(--space-md);
  color: var(--color-danger);
}

</style>