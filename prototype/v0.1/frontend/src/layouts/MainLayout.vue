<template>
  <div class="layout">
    <header class="layout__header">
      <h1 class="layout__logo">🐹 CapyMeal</h1>
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
  background: var(--color-background);
}

.layout__header {
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 2rem;

  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
}

.layout__content {
  max-width: 600px;
  margin: 0 auto;
  padding: var(--space-xl) var(--space-lg);
  padding-bottom: calc(64px + var(--space-xl));
}

.layout__logo {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--color-title);
}
</style>