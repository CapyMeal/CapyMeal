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
  background: linear-gradient(180deg, #fdf6fb 0%, var(--color-background) 100%);
  padding: var(--space-xl);
}

:root[data-theme="dark"] .auth-page {
  background: linear-gradient(180deg, #352f3d 0%, var(--color-background) 100%);
}
</style>
