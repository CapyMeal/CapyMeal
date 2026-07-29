<template>
  <article class="diary-card">
    <p class="diary-card__date">{{ formattedDate }}</p>

    <ul class="diary-card__meals">
      <li v-for="meal in filledMeals" :key="meal.key" class="diary-card__meal">
        <span class="diary-card__meal-icon">{{ meal.icon }}</span>
        <span class="diary-card__meal-text">{{ meal.value }}</span>
      </li>
    </ul>

    <p v-if="entry.notes" class="diary-card__notes">
      📝 {{ entry.notes }}
    </p>
  </article>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  date:  { type: String, required: true },
  entry: { type: Object, required: true },
})

const MEALS = [
  { key: 'breakfast', icon: '☀️' },
  { key: 'lunch',     icon: '🍝' },
  { key: 'snack',     icon: '🧁' },
  { key: 'dinner',    icon: '🌙' },
]

const filledMeals = computed(() =>
  MEALS
    .filter(m => props.entry[m.key])
    .map(m => ({ ...m, value: props.entry[m.key] }))
)

const formattedDate = computed(() => {
  const [year, month, day] = props.date.split('-').map(Number)
  return new Date(year, month - 1, day).toLocaleDateString('es-AR', {
    weekday: 'long',
    day:     'numeric',
    month:   'long',
  })
})
</script>

<style scoped>
.diary-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: var(--space-lg);
  box-shadow: var(--shadow-sm);
}

.diary-card__date {
  font-size: .85rem;
  font-weight: 700;
  color: var(--color-primary);
  text-transform: capitalize;
  margin-bottom: var(--space-md);
}

.diary-card__meals {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  margin-bottom: var(--space-sm);
}

.diary-card__meal {
  display: flex;
  gap: var(--space-sm);
  align-items: baseline;
}

.diary-card__meal-icon {
  font-size: .9rem;
  flex-shrink: 0;
}

.diary-card__meal-text {
  font-size: .9rem;
  color: var(--color-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}

.diary-card__notes {
  font-size: .85rem;
  color: var(--color-text);
  opacity: .7;
  margin-top: var(--space-sm);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
