<template>
  <div class="auth-page">
    <slot />
  </div>
</template>

<script setup>
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  /* --color-surface-warm ya cambia con el tema: no hace falta una regla
     aparte para el oscuro. */
  background: linear-gradient(180deg, var(--color-surface-warm) 0%, var(--color-background) 100%);
  /* En el celular el margen de la página se suma al interno de la
     tarjeta: con 2rem de cada lado el formulario quedaba en ~260px y el
     botón de Microsoft tocaba los bordes. */
  padding: var(--space-lg) var(--space-md);
}

@media (min-width: 768px) {
  .auth-page {
    padding: var(--space-xl);
  }
}
</style>
