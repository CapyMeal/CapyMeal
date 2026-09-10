<template>
  <AuthLayout>
    <AuthCard
      :title="t('twoFactor.challengeTitle')"
      :subtitle="usingRecoveryCode ? t('twoFactor.challengeRecoverySubtitle') : t('twoFactor.challengeSubtitle')"
    >
      <form class="auth-form" @submit.prevent="submit">
        <v-text-field
          v-model="code"
          :label="usingRecoveryCode ? t('twoFactor.recoveryCodeLabel') : t('twoFactor.codeLabel')"
          :placeholder="usingRecoveryCode ? '' : '123456'"
          autocomplete="one-time-code"
          autofocus
          required
        />

        <v-alert v-if="errorMessage" type="error" variant="tonal" density="compact">
          {{ errorMessage }}
        </v-alert>

        <CapyButton class="auth-submit" :disabled="loading" type="submit">
          {{ loading ? t('twoFactor.verifying') : t('twoFactor.verifyButton') }}
        </CapyButton>
      </form>

      <template #footer>
        <p>
          <button type="button" class="link-button" @click="toggleRecoveryCode">
            {{ usingRecoveryCode ? t('twoFactor.useAppCodeInstead') : t('twoFactor.useRecoveryCodeInstead') }}
          </button>
        </p>
      </template>
    </AuthCard>
  </AuthLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import AuthLayout from '../layouts/AuthLayout.vue'
import AuthCard   from '../components/auth/AuthCard.vue'
import CapyButton from '../components/base/CapyButton.vue'
import { verifyTwoFactorCode, twoFactorChallenge } from '../stores/authStore'

const router = useRouter()
const { t }  = useI18n()

const code             = ref('')
const usingRecoveryCode = ref(false)
const loading          = ref(false)
const errorMessage     = ref('')

// Si alguien entra directo a esta URL (sin haber pasado por login()
// primero) no hay ningún desafío guardado -- no tiene sentido mostrar el
// formulario, se manda de vuelta a loguearse desde el principio.
onMounted(() => {
  if (!twoFactorChallenge.value) {
    router.replace('/login')
  }
})

function toggleRecoveryCode() {
  usingRecoveryCode.value = !usingRecoveryCode.value
  code.value = ''
  errorMessage.value = ''
}

async function submit() {
  loading.value      = true
  errorMessage.value = ''

  try {
    await verifyTwoFactorCode(code.value)
    router.push('/hoy')
  } catch (error) {
    errorMessage.value = error.message || t('twoFactor.genericError')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-submit {
  width: 100%;
  margin-top: var(--space-xs);
}

.link-button {
  background: none;
  border: none;
  padding: 0;
  font: inherit;
  color: var(--color-primary);
  font-weight: 700;
  cursor: pointer;
}
</style>
