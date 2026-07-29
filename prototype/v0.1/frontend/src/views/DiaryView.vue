<template>
  <MainLayout>
    <h1 class="diary-title">📖 Mi Diario</h1>

    <EmptyState
      v-if="entries.length === 0"
      message="Todavía no guardamos ningún recuerdo."
      action-label="Registrar mi primer día"
      @action="$router.push('/hoy')"
    />

    <div v-else class="diary-list">
      <DiaryCard
        v-for="{ date, entry } in entries"
        :key="date"
        :date="date"
        :entry="entry"
      />
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import MainLayout from '../layouts/MainLayout.vue'
import DiaryCard  from '../components/diary/DiaryCard.vue'
import EmptyState from '../components/diary/EmptyState.vue'

const STORAGE_KEY = 'capymeal-entries'

const entries = ref([])

onMounted(() => {
  const raw = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}')
  entries.value = Object.entries(raw)
    .sort(([a], [b]) => b.localeCompare(a))
    .map(([date, entry]) => ({ date, entry }))
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
</style>