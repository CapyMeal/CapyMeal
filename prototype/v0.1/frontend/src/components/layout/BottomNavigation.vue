<template>
  <nav class="bottom-nav">
    <RouterLink to="/hoy" class="bottom-nav__item" active-class="bottom-nav__item--active">
      <img :src="dayIcon" alt="Hoy" class="bottom-nav__icon-image">
      <span class="bottom-nav__label">Hoy</span>
    </RouterLink>
    <RouterLink to="/recuerdos" class="bottom-nav__item" active-class="bottom-nav__item--active">
      <img :src="diaryIcon" alt="Diario" class="bottom-nav__icon-image">
      <span class="bottom-nav__label">Diario</span>
    </RouterLink>
    <RouterLink to="/exportar" class="bottom-nav__item" active-class="bottom-nav__item--active">
      <img :src="pdfIcon" alt="PDF" class="bottom-nav__icon-image">
      <span class="bottom-nav__label">PDF</span>
    </RouterLink>
    <RouterLink to="/ajustes" class="bottom-nav__item" active-class="bottom-nav__item--active">
      <span class="bottom-nav__icon">⚙️</span>
      <span class="bottom-nav__label">Ajustes</span>
    </RouterLink>
  </nav>
</template>

<script setup>
import dayIcon from '../../assets/icons/miDia.png'
import diaryIcon from '../../assets/icons/miDiario.png'
import pdfIcon from '../../assets/icons/miPdf.png'
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

.bottom-nav__icon-image {
  width: 20px;
  height: 20px;
  object-fit: contain;
}

.bottom-nav__label {
  font-size: .72rem;
  font-weight: 600;
  letter-spacing: .03em;
}
</style>
