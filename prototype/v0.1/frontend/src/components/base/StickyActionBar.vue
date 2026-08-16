<template>
  <div class="sticky-bar">
    <div class="sticky-bar__inner">
      <slot />
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.sticky-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 70px;
  z-index: 9;

  padding: var(--space-sm) var(--space-md);
  background: color-mix(in srgb, var(--color-surface) 92%, transparent);
  backdrop-filter: blur(8px);
  border-top: 1px solid var(--color-border);
}

.sticky-bar__inner {
  max-width: 600px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}
</style>
