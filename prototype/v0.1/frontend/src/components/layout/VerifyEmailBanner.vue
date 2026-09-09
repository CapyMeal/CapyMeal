<template>
  <v-alert
    type="info"
    variant="tonal"
    density="compact"
    class="verify-email-banner"
  >
    <p class="verify-email-banner__text">
      {{ sent ? t('verifyEmail.resentMessage') : t('verifyEmail.pendingMessage') }}
    </p>

    <v-btn
      variant="text"
      size="small"
      density="compact"
      class="verify-email-banner__action"
      :disabled="sending || sent"
      @click="resend"
    >
      {{ sending ? t('verifyEmail.sendingButton') : t('verifyEmail.resendButton') }}
    </v-btn>
  </v-alert>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { apiFetch } from '../../services/mealEntriesApi'

const { t } = useI18n()

const sending = ref(false)
const sent    = ref(false)

// El botón queda deshabilitado ~60s después de un envío exitoso, mismo
// margen que el throttle del backend (3 por minuto) -- evita que alguien
// lo clickee varias veces seguidas y se coma un 429 innecesario.
async function resend() {
  sending.value = true

  try {
    await apiFetch('/email/verification-notification', { method: 'POST' })
    sent.value = true
    setTimeout(() => { sent.value = false }, 60_000)
  } catch {
    // Un error puntual (red, 429, 500) no amerita más que dejar reintentar
    // -- el botón se reactiva solo, sin cartel de error aparte.
  } finally {
    sending.value = false
  }
}
</script>

<style scoped>
.verify-email-banner {
  max-width: 600px;
  margin: var(--space-md) auto 0;
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.verify-email-banner :deep(.v-alert__content) {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  flex-wrap: wrap;
}

.verify-email-banner__text {
  margin: 0;
  flex: 1 1 220px;
}

.verify-email-banner__action {
  flex-shrink: 0;
}
</style>
