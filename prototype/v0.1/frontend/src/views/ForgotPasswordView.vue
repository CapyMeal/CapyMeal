<template>
  <AuthLayout>
    <AuthCard
      :title="sent ? t('forgotPassword.titleSent') : t('forgotPassword.titleNotSent')"
      :subtitle="sent ? t('forgotPassword.subtitleSent') : t('forgotPassword.subtitleNotSent')"
    >
      <form v-if="!sent" class="auth-form" @submit.prevent="submit">
        <v-text-field
          v-model="email"
          :label="t('common.email')"
          type="email"
          :placeholder="t('common.emailPlaceholder')"
          autocomplete="email"
          required
        />

        <v-alert v-if="errorMessage" type="error" variant="tonal" density="compact">
          {{ errorMessage }}
        </v-alert>

        <CapyButton class="auth-submit" :disabled="loading" type="submit">
          {{ loading ? t('forgotPassword.sending') : t('forgotPassword.submitButton') }}
        </CapyButton>
      </form>

      <template #footer>
        <p>
          <RouterLink to="/login">{{ t('forgotPassword.backToLogin') }}</RouterLink>
        </p>
      </template>
    </AuthCard>
  </AuthLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AuthLayout from '../layouts/AuthLayout.vue'
import AuthCard   from '../components/auth/AuthCard.vue'
import CapyButton from '../components/base/CapyButton.vue'
import { apiFetch } from '../services/mealEntriesApi'

const { t } = useI18n()

const email        = ref('')
const loading      = ref(false)
const errorMessage = ref('')
const sent         = ref(false)

async function submit() {
  loading.value      = true
  errorMessage.value = ''

  try {
    await apiFetch('/forgot-password', {
      method: 'POST',
      body: JSON.stringify({ email: email.value }),
    })
    sent.value = true
  } catch (error) {
    if (error.status === 429) {
      errorMessage.value = error.message || t('forgotPassword.rateLimitError')
    } else if (error.status) {
      errorMessage.value = error.message || t('forgotPassword.genericError')
    } else {
      errorMessage.value = t('forgotPassword.networkError')
    }
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
</style>
