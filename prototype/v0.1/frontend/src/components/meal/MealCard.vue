<template>
  <v-card class="meal-card" :class="{ 'meal-card--saving': saving, 'meal-card--saved': justSaved }" elevation="1">
    <div class="meal-card__header">
      <div class="meal-card__icon-wrap">
        <img
          v-if="iconImage"
          :src="iconImage"
          :alt="title"
          class="meal-card__icon-image"
        >
        <span v-else class="meal-card__icon">{{ icon }}</span>
      </div>
      <h2 class="meal-card__title">{{ title }}</h2>
      <span v-if="justSaved" class="meal-card__saved-badge">✓</span>
      <span v-else-if="saving" class="meal-card__saved-badge meal-card__saved-badge--loading">…</span>
    </div>
    <textarea
      class="meal-card__textarea"
      :aria-label="title"
      :placeholder="placeholder"
      :value="modelValue"
      rows="3"
      @input="$emit('update:modelValue', $event.target.value)"
      @blur="onBlur"
    />
  </v-card>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  icon:        { type: String, default: '' },
  iconImage:   { type: String, default: '' },
  title:       { type: String, required: true },
  modelValue:  { type: String, default: '' },
  placeholder: { type: String, default: '¿Qué comiste?' },
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
  box-shadow: 0 0 0 3px rgba(150,104,74,.22) !important;
}

.meal-card__header {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
}

.meal-card__icon-wrap {
  width: 56px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.meal-card__icon {
  font-size: 2.2rem;
  line-height: 1;
}

.meal-card__icon-image {
  width: 52px;
  height: 52px;
  object-fit: contain;
  filter: drop-shadow(0 2px 6px rgba(169,130,116,.2));
}

.meal-card__title {
  font-family: var(--font-title);
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--color-title);
  flex: 1;
}

.meal-card__saved-badge {
  font-size: .8rem;
  font-weight: 700;
  color: var(--color-primary);
  background: rgba(150,104,74,.18);
  border-radius: 999px;
  padding: .15rem .5rem;
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
  border-radius: var(--radius-sm);
  padding: var(--space-sm) var(--space-md);
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

.meal-card__textarea::placeholder {
  color: var(--color-muted);
  opacity: .7;
}
</style>

