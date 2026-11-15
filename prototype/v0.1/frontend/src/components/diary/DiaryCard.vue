<template>
  <v-card :to="`/recuerdos/${date}`" class="diary-card" color="surface" elevation="1" hover>
    <v-card-text>
      <p class="diary-card__date">{{ formattedDate }}</p>

      <ul class="diary-card__meals">
        <li v-for="meal in filledMeals" :key="meal.key" class="diary-card__meal">
          <MealIcon :meal="meal.key" />
          <span class="diary-card__meal-text">{{ meal.value }}</span>
        </li>
      </ul>

      <p v-if="entry.notes" class="diary-card__meal diary-card__notes">
        <MealIcon meal="notes" />
        <span class="diary-card__meal-text">{{ entry.notes }}</span>
      </p>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed } from 'vue'
import MealIcon from '../meal/MealIcon.vue'
import { formatDate } from '../../utils/date'
import { MEAL_KEYS } from '../../utils/meals'

const props = defineProps({
  date:  { type: String, required: true },
  entry: { type: Object, required: true },
})

const filledMeals = computed(() =>
  MEAL_KEYS
    .filter(key => props.entry[key])
    .map(key => ({ key, value: props.entry[key] }))
)

const formattedDate = computed(() => formatDate(props.date, {
  weekday: 'long',
  day:     'numeric',
  month:   'long',
}))
</script>

<style scoped>
.diary-card {
  display: block;
  border: 1px solid var(--color-border);
  transition: box-shadow .2s ease, transform .15s ease;
  text-decoration: none;
}

.diary-card:hover {
  box-shadow: var(--shadow-md) !important;
  transform: translateY(-2px);
}

.diary-card :deep(.v-card-text) {
  padding: var(--space-lg);
}

.diary-card__date {
  font-size: var(--font-size-label);
  font-weight: 700;
  color: var(--color-primary-text);
  margin-bottom: var(--space-md);
}

/* Solo la primera letra: capitalize ponía "12 De Noviembre". */
.diary-card__date::first-letter {
  text-transform: uppercase;
}

.diary-card__meals {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.diary-card__meal {
  display: flex;
  gap: var(--space-sm);
  align-items: center;
}

.diary-card__meal-text {
  /* min-width: 0 deja que el texto se achique dentro del flex y el
     ellipsis funcione; sin eso empuja la tarjeta más allá del ancho. */
  min-width: 0;
  font-size: var(--font-size-body);
  color: var(--color-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.diary-card__notes {
  margin-top: var(--space-sm);
  padding-top: var(--space-sm);
  border-top: 1px solid var(--color-border);
}

.diary-card__notes .diary-card__meal-text {
  color: var(--color-muted);
}

@media (prefers-reduced-motion: reduce) {
  .diary-card,
  .diary-card:hover {
    transition: none;
    transform: none;
  }
}
</style>
