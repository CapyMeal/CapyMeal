<template>
  <div class="layout">
    <header class="layout__header">
      <h1 class="layout__logo">🐹 CapyMeal</h1>
    </header>

    <main class="layout__content">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.layout {
  min-height: 100vh;
  background: var(--color-background);
}

.layout__header {
  height: 72px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 2rem;

  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
}

.layout__content {
  max-width: 1200px;
  margin: 2rem auto;
  padding: 1.5rem;

  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
}

.layout__logo {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--color-title);
}
</style>