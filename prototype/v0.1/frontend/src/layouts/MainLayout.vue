<template>
  <div class="layout">
    <header class="layout__header">
      <img class="layout__logo" src="../assets/logo/Alternativa.png" alt="CapyMeal">
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
  width: 128px;
  object-fit: contain;
}
</style>