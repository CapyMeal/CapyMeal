<template>
  <v-card :to="`/recuerdos/${date}`" class="diary-card" color="surface" elevation="1" hover>
    <v-card-text>
      <p class="diary-card__date">{{ formattedDate }}</p>

      <!-- El recuerdo va primero: es lo que distingue a CapyMeal de anotar
           en el celular ("Las comidas pasan. Los recuerdos quedan."). Antes
           iba último, en gris y cortado en una línea. -->
      <p v-if="entry.notes" class="diary-card__memory">
        <MealIcon meal="notes" :label="t('meals.notes')" />
        <span class="diary-card__text">{{ entry.notes }}</span>
      </p>

      <ul v-if="filledMeals.length" class="diary-card__meals">
        <li v-for="meal in filledMeals" :key="meal.key" class="diary-card__meal">
          <MealIcon :meal="meal.key" :label="t(`meals.${meal.key}`)" />
          <span class="diary-card__text">{{ meal.value }}</span>
        </li>
      </ul>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import MealIcon from '../meal/MealIcon.vue'
import { formatDate } from '../../utils/date'
import { MEAL_KEYS } from '../../utils/meals'

const props = defineProps({
  date:  { type: String, required: true },
  entry: { type: Object, required: true },
})

const { t } = useI18n()

const filledMeals = computed(() =>
  MEAL_KEYS
    .filter(key => props.entry[key])
    .map(key => ({ key, value: props.entry[key] }))
)

// Sin año: el Diario agrupa por mes y el encabezado del grupo ya lo dice.
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

.diary-card__memory,
.diary-card__meal {
  display: flex;
  gap: var(--space-sm);
  align-items: flex-start;
}

/* La ilustración alineada con la primera línea, no con el centro de un
   texto de dos líneas. */
.diary-card__memory .meal-icon,
.diary-card__meal .meal-icon {
  margin-top: .1em;
}

.diary-card__memory {
  padding-bottom: var(--space-md);
  margin-bottom: var(--space-md);
  border-bottom: 1px solid var(--color-border);
}

.diary-card__memory .diary-card__text {
  color: var(--color-title);
  font-style: italic;
}

.diary-card__meals {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

/* Hasta dos líneas: "Café con leche y tostadas con que…" en una sola
   línea no se terminaba de leer. min-width: 0 deja que el texto se
   achique dentro del flex. */
.diary-card__text {
  min-width: 0;
  font-size: var(--font-size-body);
  line-height: 1.45;
  color: var(--color-text);
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  overflow: hidden;
  overflow-wrap: anywhere;
}

/* Si el recuerdo es lo único que hay, no necesita separador. */
.diary-card__memory:last-child {
  padding-bottom: 0;
  margin-bottom: 0;
  border-bottom: none;
}

@media (prefers-reduced-motion: reduce) {
  .diary-card,
  .diary-card:hover {
    transition: none;
    transform: none;
  }
}
</style>
