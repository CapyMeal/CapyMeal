<template>
  <!-- Decorativa: siempre va al lado del nombre o del texto de la comida,
       así que un alt descriptivo haría que el lector de pantalla lo diga
       dos veces. -->
  <img
    :src="MEAL_ILLUSTRATIONS[meal]"
    alt=""
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
  size: { type: Number, default: 24 },
})
</script>

<style scoped>
.meal-icon {
  flex-shrink: 0;
  object-fit: contain;
  filter: var(--illustration-shadow-sm);
}
</style>
