<template>
  <AuthLayout>
    <AuthCard
      v-if="!hasLinkParams"
      :title="t('resetPassword.invalidLinkTitle')"
    >
      <p class="auth-note">
        {{ t('resetPassword.invalidLinkMessage') }}
      </p>
      <template #footer>
        <p><RouterLink to="/olvide-contrasena">{{ t('common.forgotPasswordLink') }}</RouterLink></p>
      </template>
    </AuthCard>

    <AuthCard
      v-else-if="!done"
      :title="t('resetPassword.title')"
      :subtitle="t('resetPassword.subtitle')"
    >
      <form class="auth-form" @submit.prevent="submit">
        <PasswordField
          v-model="password"
          :label="t('resetPassword.newPasswordLabel')"
          :placeholder="t('resetPassword.newPasswordPlaceholder')"
          autocomplete="new-password"
          minlength="8"
        />

        <PasswordField
          v-model="passwordConfirmation"
          :label="t('resetPassword.confirmPasswordLabel')"
          :placeholder="t('resetPassword.confirmPasswordPlaceholder')"
          autocomplete="new-password"
        />

        <v-alert v-if="errorMessage" type="error" variant="tonal" density="compact">
          {{ errorMessage }}
        </v-alert>

        <CapyButton class="auth-submit" :disabled="loading" type="submit">
          {{ loading ? t('resetPassword.saving') : t('resetPassword.submitButton') }}
        </CapyButton>
      </form>
    </AuthCard>

    <AuthCard
      v-else
      :title="t('resetPassword.doneTitle')"
      :subtitle="t('resetPassword.doneSubtitle')"
    >
      <template #footer>
        <p><RouterLink to="/login">{{ t('resetPassword.loginLink') }}</RouterLink></p>
      </template>
    </AuthCard>
  </AuthLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import AuthLayout    from '../layouts/AuthLayout.vue'
import AuthCard      from '../components/auth/AuthCard.vue'
import CapyButton    from '../components/base/CapyButton.vue'
import PasswordField from '../components/base/PasswordField.vue'
import { apiFetch } from '../services/mealEntriesApi'

const route = useRoute()
const { t } = useI18n()

const token                = ref('')
const email                = ref('')
const password             = ref('')
const passwordConfirmation = ref('')
const loading              = ref(false)
const errorMessage        = ref('')
const done                = ref(false)
const hasLinkParams       = ref(true)

onMounted(() => {
  token.value = route.query.token ?? ''
  email.value = route.query.email ?? ''
  hasLinkParams.value = Boolean(token.value && email.value)
})

async function submit() {
  errorMessage.value = ''

  if (password.value !== passwordConfirmation.value) {
    errorMessage.value = t('common.passwordMismatch')
    return
  }

  loading.value = true

  try {
    await apiFetch('/reset-password', {
      method: 'POST',
      body: JSON.stringify({
        token:                 token.value,
        email:                 email.value,
        password:              password.value,
        password_confirmation: passwordConfirmation.value,
      }),
    })
    done.value = true
  } catch (error) {
    errorMessage.value = error.message || t('resetPassword.genericError')
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

.auth-note {
  font-size: var(--font-size-body);
  color: var(--color-muted);
  line-height: 1.6;
}
</style>
