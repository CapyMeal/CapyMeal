<template>
  <div class="meal-card">
    <div class="meal-card__header">
      <span class="meal-card__icon">{{ icon }}</span>
      <h2 class="meal-card__title">{{ title }}</h2>
    </div>
    <textarea
      class="meal-card__textarea"
      :placeholder="placeholder"
      :value="modelValue"
      rows="3"
      @input="$emit('update:modelValue', $event.target.value)"
    />
  </div>
</template>

<script setup>
defineProps({
  icon:       { type: String, required: true },
  title:      { type: String, required: true },
  modelValue: { type: String, default: '' },
  placeholder:{ type: String, default: '¿Qué comiste?' },
})

defineEmits(['update:modelValue'])
</script>

<style scoped>
.meal-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: var(--space-lg);
  box-shadow: var(--shadow-sm);
}

.meal-card__header {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
}

.meal-card__icon {
  font-size: 1.4rem;
  line-height: 1;
}

.meal-card__title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-title);
}

.meal-card__textarea {
  width: 100%;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  padding: var(--space-sm) var(--space-md);
  color: var(--color-text);
  resize: none;
  outline: none;
  line-height: 1.6;
  transition: border-color .2s ease;
}

.meal-card__textarea:focus {
  border-color: var(--color-primary);
}

.meal-card__textarea::placeholder {
  color: var(--color-text);
  opacity: .45;
}
</style>
