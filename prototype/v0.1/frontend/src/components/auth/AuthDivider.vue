<template>
  <!-- Separa el formulario de email de los botones de Google/Microsoft.
       Antes era una "o" suelta entre dos bloques; las líneas a los lados
       la leen como separador y no como texto perdido. -->
  <p class="auth-divider">
    <span>{{ t('common.or') }}</span>
  </p>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<style scoped>
.auth-divider {
  width: 100%;
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  color: var(--color-muted);
  font-size: var(--font-size-label);
  margin: var(--space-xs) 0;
}

.auth-divider::before,
.auth-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--color-border);
}
</style>
