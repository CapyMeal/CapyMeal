<template>
  <AuthLayout>
    <AuthCard :title="t('register.title')" :subtitle="t('register.subtitle')">
      <form class="auth-form" @submit.prevent="submit">
        <v-text-field
          v-model="name"
          :label="t('register.nameLabel')"
          type="text"
          :placeholder="t('register.namePlaceholder')"
          autocomplete="name"
          required
        />

        <v-text-field
          v-model="email"
          :label="t('common.email')"
          type="email"
          :placeholder="t('common.emailPlaceholder')"
          autocomplete="email"
          required
        />

        <PasswordField
          v-model="password"
          :label="t('register.passwordLabel')"
          :placeholder="t('register.passwordPlaceholder')"
          autocomplete="new-password"
        />

        <PasswordField
          v-model="passwordConfirm"
          :label="t('register.passwordConfirmLabel')"
          :placeholder="t('register.passwordConfirmPlaceholder')"
          autocomplete="new-password"
        />

        <v-alert v-if="errorMessage" type="error" variant="tonal" density="compact">
          {{ errorMessage }}
        </v-alert>

        <CapyButton class="auth-submit" :disabled="loading" type="submit">
          {{ loadingButtonLabel }}
        </CapyButton>
      </form>

      <AuthDivider />

      <!-- href real, no @click con router: ver la misma nota en LoginView.vue. -->
      <CapyButton variant="ghost" :href="googleRedirectUrl" class="social-button">
        <GoogleIcon />
        {{ t('register.googleButton') }}
      </CapyButton>

      <!-- href real, mismo motivo que el botón de Google de arriba. -->
      <CapyButton variant="ghost" :href="microsoftRedirectUrl" class="social-button">
        <MicrosoftIcon />
        {{ t('register.microsoftButton') }}
      </CapyButton>

      <template #footer>
        <p>
          {{ t('register.haveAccount') }}
          <RouterLink to="/login">{{ t('register.loginLink') }}</RouterLink>
        </p>
        <p class="auth-card__legal">
          {{ t('register.legalPrefix') }}
          <RouterLink to="/terminos">{{ t('register.termsLink') }}</RouterLink> {{ t('register.legalAnd') }}
          <RouterLink to="/privacidad">{{ t('register.privacyLink') }}</RouterLink>.
        </p>
      </template>
    </AuthCard>
  </AuthLayout>
</template>

<script setup>
import { API_BASE_URL, BACKEND_URL } from '../config/api'
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import AuthLayout    from '../layouts/AuthLayout.vue'
import AuthCard      from '../components/auth/AuthCard.vue'
import AuthDivider   from '../components/auth/AuthDivider.vue'
import CapyButton    from '../components/base/CapyButton.vue'
import GoogleIcon    from '../components/base/GoogleIcon.vue'
import MicrosoftIcon from '../components/base/MicrosoftIcon.vue'
import PasswordField from '../components/base/PasswordField.vue'
import { register } from '../stores/authStore'

const router = useRouter()
const { t }  = useI18n()

// Google va por el /api del mismo sitio (Vercel lo reenvía al backend) y
// vuelve por ahí: así la cookie que guarda el "state" del ingreso es de
// capy-meal.vercel.app. Pasando por capymeal.onrender.com, Samsung Internet
// y otros navegadores con protección contra rastreo la bloqueaban y el
// ingreso fallaba. Microsoft sigue directo al backend hasta registrar la
// URL de vuelta del frontend en Azure (ver SocialAuthController).
const googleRedirectUrl = `${API_BASE_URL}/api/auth/google/redirect`
const microsoftRedirectUrl = `${BACKEND_URL}/api/auth/microsoft/redirect`

const name             = ref('')
const email             = ref('')
const password           = ref('')
const passwordConfirm    = ref('')
const loading            = ref(false)
const errorMessage       = ref('')
const slowLogin          = ref(false)

const loadingButtonLabel = computed(() => {
  if (!loading.value) return t('register.submitButton')
  return slowLogin.value ? t('common.slowBackendMessage') : t('register.creatingAccount')
})

async function submit() {
  if (password.value !== passwordConfirm.value) {
    errorMessage.value = t('common.passwordMismatch')
    return
  }

  loading.value      = true
  errorMessage.value = ''
  slowLogin.value    = false

  // Ver LoginView.vue: el backend gratuito de Render tarda unos
  // segundos en despertar si estuvo inactivo.
  const slowTimer = setTimeout(() => { slowLogin.value = true }, 4000)

  try {
    await register({
      name:                  name.value,
      email:                 email.value,
      password:              password.value,
      password_confirmation: passwordConfirm.value,
    })
    router.push('/hoy')
  } catch (error) {
    errorMessage.value = error.message || t('register.genericError')
  } finally {
    clearTimeout(slowTimer)
    loading.value = false
  }
}
</script>

<style scoped>
.auth-submit {
  width: 100%;
  margin-top: var(--space-xs);
}

/* Sin opacidad extra: sobre la tinta suave del pie bajaba a ~4:1. */
.auth-card__legal {
  font-size: var(--font-size-caption);
}

.social-button :deep(.v-btn__content) {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}
</style>
