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
  padding: var(--space-xl);
}
</style>
