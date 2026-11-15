<template>
  <v-card class="meal-card" :class="{ 'meal-card--saving': saving, 'meal-card--saved': justSaved }" elevation="1">
    <div class="meal-card__header">
      <MealIcon :meal="meal" :size="52" />
      <h2 class="meal-card__title">{{ title }}</h2>
      <span v-if="justSaved" class="meal-card__saved-badge">
        <v-icon :icon="`svg:${mdiCheck}`" size="16" />
      </span>
      <span v-else-if="saving" class="meal-card__saved-badge meal-card__saved-badge--loading">…</span>
    </div>
    <textarea
      class="meal-card__textarea"
      :aria-label="title"
      :placeholder="placeholder"
      :value="modelValue"
      rows="3"
      maxlength="2000"
      @input="$emit('update:modelValue', $event.target.value)"
      @blur="onBlur"
    />
  </v-card>
</template>

<script setup>
import { mdiCheck } from '@mdi/js'
import MealIcon from './MealIcon.vue'

const props = defineProps({
  // Clave del campo (breakfast, lunch, snack, dinner, notes): la
  // ilustración sale de utils/meals.js, no la elige cada vista.
  meal:        { type: String, required: true },
  title:       { type: String, required: true },
  modelValue:  { type: String, default: '' },
  // Sin default en español: todos los usos actuales ya pasan su propio
  // placeholder traducido (ver TodayView/DetailView).
  placeholder: { type: String, required: true },
  saving:      { type: Boolean, default: false },
  justSaved:   { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'save-field'])

function onBlur() {
  if (props.modelValue && props.modelValue.trim().length > 0) {
    emit('save-field')
  }
}
</script>

<style scoped>
.meal-card {
  border: 1.5px solid var(--color-border);
  padding: var(--space-lg);
  transition: border-color .25s ease, box-shadow .25s ease;
}

.meal-card--saved {
  border-color: var(--color-primary);
  box-shadow: var(--ring-saved) !important;
}

.meal-card__header {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
}

.meal-card__title {
  font-family: var(--font-title);
  font-size: var(--font-size-title-sm);
  font-weight: 600;
  color: var(--color-title);
  flex: 1;
}

.meal-card__saved-badge {
  display: inline-flex;
  align-items: center;
  font-size: var(--font-size-caption);
  font-weight: 700;
  color: var(--color-primary-text);
  background: color-mix(in srgb, var(--color-primary) 14%, transparent);
  border-radius: 999px;
  padding: .2rem .5rem;
  transition: opacity .3s ease;
}

.meal-card__saved-badge--loading {
  color: var(--color-muted);
  background: transparent;
}

.meal-card__textarea {
  width: 100%;
  background: var(--color-background);
  border: 1.5px solid transparent;
  /* Mismo radio que la tarjeta (8px): con 12px el campo quedaba más
     redondeado que su contenedor y se veía despegado. */
  border-radius: var(--radius-control);
  padding: var(--space-sm) var(--space-md);
  font-size: var(--font-size-body);
  color: var(--color-text);
  resize: none;
  outline: none;
  line-height: 1.6;
  transition: border-color .2s ease, background .2s ease;
}

.meal-card__textarea:focus {
  border-color: var(--color-primary);
  background: var(--color-surface);
}

/* Sin opacidad extra: tinta suave al 70% daba 2.8:1 sobre arena. */
.meal-card__textarea::placeholder {
  color: var(--color-muted);
  opacity: 1;
}
</style>
