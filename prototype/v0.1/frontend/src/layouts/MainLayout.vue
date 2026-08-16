<template>
  <div class="layout">
    <header class="layout__header">
      <img src="../assets/illustrations/Chef.png" alt="Capi" class="layout__header-icon">
      <span class="layout__brand">CapyMeal</span>
    </header>

    <main class="layout__content">
      <slot />
    </main>

    <BottomNavigation />
  </div>
</template>

<script setup>
import BottomNavigation from '../components/layout/BottomNavigation.vue'
</script>

<style scoped>
.layout {
  min-height: 100vh;
  background: linear-gradient(180deg, color-mix(in srgb, var(--color-lavender) 12%, var(--color-background)) 0%, var(--color-background) 160px);
}

.layout__header {
  position: sticky;
  top: 0;
  z-index: 10;
  height: 68px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 var(--space-lg);

  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
}

.layout__content {
  max-width: 600px;
  margin: 0 auto;
  padding: var(--space-lg) var(--space-md);
  padding-bottom: calc(70px + var(--space-xl));
}

.layout__header-icon {
  width: 40px;
  height: 40px;
  object-fit: contain;
  filter: drop-shadow(0 2px 6px rgba(169,130,116,.2));
}

.layout__brand {
  font-family: var(--font-title);
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-title);
  letter-spacing: -.3px;
}

@media (min-width: 768px) {
  .layout__content {
    padding: var(--space-xl) var(--space-lg);
    padding-bottom: calc(70px + var(--space-xl));
  }
}
</style>