<template>
  <nav class="bottom-nav">
    <RouterLink to="/hoy" class="bottom-nav__item" active-class="bottom-nav__item--active">
      <span class="bottom-nav__icon">🏠</span>
      <span class="bottom-nav__label">Hoy</span>
    </RouterLink>
    <RouterLink to="/recuerdos" class="bottom-nav__item" active-class="bottom-nav__item--active">
      <span class="bottom-nav__icon">📖</span>
      <span class="bottom-nav__label">Diario</span>
    </RouterLink>
    <RouterLink to="/ajustes" class="bottom-nav__item" active-class="bottom-nav__item--active">
      <span class="bottom-nav__icon">⚙️</span>
      <span class="bottom-nav__label">Ajustes</span>
    </RouterLink>
  </nav>
</template>

<style scoped>
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 64px;

  display: flex;
  align-items: stretch;

  background: var(--color-surface);
  border-top: 1px solid var(--color-border);
  box-shadow: 0 -4px 16px rgba(0, 0, 0, .06);
}

.bottom-nav__item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3px;

  color: var(--color-text);
  opacity: .5;
  transition: opacity .2s ease;
}

.bottom-nav__item--active {
  opacity: 1;
  color: var(--color-primary);
}

.bottom-nav__icon {
  font-size: 1.25rem;
  line-height: 1;
}

.bottom-nav__label {
  font-size: .7rem;
  font-weight: 600;
  letter-spacing: .03em;
}
</style>
