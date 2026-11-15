<template>
  <!-- Sin label es decorativa (alt vacío): va al lado del nombre de la
       comida y un alt descriptivo haría que el lector de pantalla lo diga
       dos veces. Con label es la única pista de qué comida es (por
       ejemplo, en las tarjetas del Diario, donde solo se ve el texto). -->
  <img
    :src="MEAL_ILLUSTRATIONS[meal]"
    :alt="label"
    :width="size"
    :height="size"
    class="meal-icon"
  >
</template>

<script setup>
import { MEAL_ILLUSTRATIONS } from '../../utils/meals'

defineProps({
  meal: {
    type: String,
    required: true,
    validator: (value) => value in MEAL_ILLUSTRATIONS,
  },
  size:  { type: Number, default: 24 },
  label: { type: String, default: '' },
})
</script>

<style scoped>
.meal-icon {
  flex-shrink: 0;
  object-fit: contain;
  filter: var(--illustration-shadow-sm);
}
</style>
