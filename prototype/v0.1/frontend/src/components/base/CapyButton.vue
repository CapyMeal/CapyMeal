<template>
  <button class="brand-button">
    <slot />
  </button>
</template>

<script setup>
</script>

<style scoped>
.brand-button {
  padding: 0.85rem 1.5rem;
  border: none;
  border-radius: var(--radius-md);

  background: var(--color-primary);
  color: black;

  font-size: 1rem;
  font-weight: 500;

  cursor: pointer;

  transition:
    background .2s ease,
    transform .15s ease,
    box-shadow .2s ease;

  box-shadow: var(--shadow-sm);
}

.brand-button:hover {
  background: var(--color-primary-hover);
  transform: translateY(-2px);
}

.brand-button:active {
  transform: translateY(0);
}

.brand-button:disabled {
  opacity: .5;
  cursor: not-allowed;
}
</style>