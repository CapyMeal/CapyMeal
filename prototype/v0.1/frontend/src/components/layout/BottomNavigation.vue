<template>
  <nav class="bottom-nav">
    <RouterLink to="/hoy" class="bottom-nav__item" active-class="bottom-nav__item--active">
      <v-icon icon="mdi-calendar-today" size="26" />
      <span class="bottom-nav__label">Hoy</span>
    </RouterLink>
    <RouterLink to="/recuerdos" class="bottom-nav__item" active-class="bottom-nav__item--active">
      <v-icon icon="mdi-notebook" size="26" />
      <span class="bottom-nav__label">Diario</span>
    </RouterLink>
    <RouterLink to="/exportar" class="bottom-nav__item" active-class="bottom-nav__item--active">
      <v-icon icon="mdi-file-pdf-box" size="26" />
      <span class="bottom-nav__label">PDF</span>
    </RouterLink>
    <RouterLink to="/ajustes" class="bottom-nav__item" active-class="bottom-nav__item--active">
      <v-icon icon="mdi-cog" size="26" />
      <span class="bottom-nav__label">Ajustes</span>
    </RouterLink>
  </nav>
</template>

<script setup>
</script>

<style scoped>
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 70px;

  display: flex;
  align-items: stretch;

  background: color-mix(in srgb, var(--color-surface) 92%, transparent);
  backdrop-filter: blur(8px);
  border-top: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
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

.bottom-nav__label {
  font-size: .72rem;
  font-weight: 600;
  letter-spacing: .03em;
}
</style>
