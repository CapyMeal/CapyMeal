<template>
  <!-- Único lugar donde se dibuja un aviso de estado (error, aviso, éxito).
       Antes convivían avisos tonales en "Hoy" y párrafos rojos sueltos en
       Diario, Detalle, Exportar y Ajustes: los párrafos no se anunciaban a
       los lectores de pantalla y no ofrecían salida. VAlert ya trae
       role="alert"; para éxito se baja a role="status" (no interrumpe). -->
  <v-alert
    :type="type"
    variant="tonal"
    density="compact"
    class="status-alert"
    :role="type === 'success' ? 'status' : 'alert'"
  >
    <div class="status-alert__body">
      <span><slot>{{ message }}</slot></span>
      <CapyButton
        v-if="retryable"
        variant="ghost"
        compact
        class="status-alert__retry"
        @click="$emit('retry')"
      >
        {{ retryLabel || t('common.retry') }}
      </CapyButton>
    </div>
  </v-alert>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import CapyButton from './CapyButton.vue'

defineProps({
  type:       { type: String, default: 'error', validator: (v) => ['error', 'warning', 'success'].includes(v) },
  message:    { type: String, default: '' },
  // Muestra "Reintentar" (o retryLabel) y emite "retry". Solo cuando
  // reintentar tiene sentido: un error de carga o de guardado, no una
  // validación que la persona tiene que corregir.
  retryable:  { type: Boolean, default: false },
  retryLabel: { type: String, default: '' },
})

defineEmits(['retry'])

const { t } = useI18n()
</script>

<style scoped>
.status-alert {
  font-size: var(--font-size-label);
}

.status-alert__body {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-xs) var(--space-sm);
}

.status-alert__retry {
  flex-shrink: 0;
}
</style>
