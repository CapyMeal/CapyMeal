<template>
  <button :class="['capy-button', `capy-button--${variant}`]">
    <slot />
  </button>
</template>

<script setup>
defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: v => ['primary', 'ghost', 'danger'].includes(v),
  },
})
</script>

<style scoped>
.capy-button {
  width: 100%;
  padding: 0.85rem 1.5rem;
  border: none;
  border-radius: var(--radius-md);
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition:
    background .2s ease,
    transform .15s ease,
    box-shadow .2s ease;
  box-shadow: var(--shadow-sm);
}

.capy-button--primary {
  background: var(--color-primary);
  color: #2B1A2A;
}

.capy-button--primary:hover {
  background: var(--color-primary-hover);
  transform: translateY(-2px);
}

.capy-button--ghost {
  background: transparent;
  color: var(--color-text);
  border: 1px solid var(--color-border);
  box-shadow: none;
}

.capy-button--ghost:hover {
  background: var(--color-border);
}

.capy-button--danger {
  background: var(--color-danger);
  color: #2B1A2A;
}

.capy-button--danger:hover {
  filter: brightness(.95);
  transform: translateY(-2px);
}

.capy-button:active  { transform: translateY(0); }
.capy-button:disabled { opacity: .5; cursor: not-allowed; }
</style>