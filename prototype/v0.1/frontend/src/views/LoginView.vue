<template>
  <AuthLayout>
    <AuthCard :title="t('login.title')" :subtitle="t('login.subtitle')">
      <form class="auth-form" @submit.prevent="submit">
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
          :label="t('login.passwordLabel')"
          :placeholder="t('login.passwordPlaceholder')"
          autocomplete="current-password"
        />

        <v-alert v-if="errorMessage" :type="errorType" variant="tonal" density="compact">
          {{ errorMessage }}
        </v-alert>

        <CapyButton class="auth-submit" :disabled="loading" type="submit">
          {{ loadingButtonLabel }}
        </CapyButton>
      </form>

      <AuthDivider />

      <!-- href real, no @click con router: el login con Google necesita una
           navegación real de nivel superior del navegador para completarse
           (Google no permite terminarlo dentro de un fetch/XHR). -->
      <CapyButton variant="ghost" :href="googleRedirectUrl" class="social-button">
        <GoogleIcon />
        {{ t('login.googleButton') }}
      </CapyButton>

      <!-- href real, mismo motivo que el botón de Google de arriba. -->
      <CapyButton variant="ghost" :href="microsoftRedirectUrl" class="social-button">
        <MicrosoftIcon />
        {{ t('login.microsoftButton') }}
      </CapyButton>

      <template #footer>
        <p>
          {{ t('login.noAccount') }}
          <RouterLink to="/registro">{{ t('login.registerLink') }}</RouterLink>
        </p>
        <p>
          <RouterLink to="/olvide-contrasena">{{ t('common.forgotPasswordLink') }}</RouterLink>
        </p>
      </template>
    </AuthCard>
  </AuthLayout>
</template>

<script setup>
import { API_BASE_URL } from '../config/api'
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import AuthLayout    from '../layouts/AuthLayout.vue'
import AuthCard      from '../components/auth/AuthCard.vue'
import AuthDivider   from '../components/auth/AuthDivider.vue'
import CapyButton    from '../components/base/CapyButton.vue'
import GoogleIcon    from '../components/base/GoogleIcon.vue'
import MicrosoftIcon from '../components/base/MicrosoftIcon.vue'
import PasswordField from '../components/base/PasswordField.vue'
import { login } from '../stores/authStore'

const router = useRouter()
const route  = useRoute()
const { t }  = useI18n()

// Google y Microsoft van por el /api del mismo sitio (Vercel lo reenvía al
// backend) y vuelven por ahí: así la cookie que guarda el "state" del
// ingreso es de capy-meal.vercel.app. Pasando por capymeal.onrender.com,
// Samsung Internet y otros navegadores con protección contra rastreo la
// bloqueaban y el ingreso fallaba (ver SocialAuthController).
const googleRedirectUrl = `${API_BASE_URL}/api/auth/google/redirect`
const microsoftRedirectUrl = `${API_BASE_URL}/api/auth/microsoft/redirect`

const email          = ref('')
const password       = ref('')
const loading        = ref(false)
const slowLogin      = ref(false)
// Si llegamos acá porque el token expiró (ver handleUnauthorized en
// authStore.js), avisamos por qué en vez de dejar que parezca que el
// login en sí falló.
const sessionExpired = ref(!!route.query.expired)

// El backend vuelve acá con ?social_error=google|microsoft si el ingreso
// con el proveedor no se pudo completar (canceló, o el enlace ya se usó o
// venció). Antes eso era una pantalla "500 Server Error" sin salida.
const SOCIAL_PROVIDERS = { google: 'Google', microsoft: 'Microsoft' }
const socialErrorProvider = SOCIAL_PROVIDERS[route.query.social_error]

function initialErrorMessage() {
  if (sessionExpired.value) return t('login.sessionExpired')
  if (socialErrorProvider) return t('login.socialError', { provider: socialErrorProvider })
  return ''
}

const errorMessage   = ref(initialErrorMessage())
const errorType      = computed(() => sessionExpired.value ? 'warning' : 'error')

const loadingButtonLabel = computed(() => {
  if (!loading.value) return t('login.submitButton')
  return slowLogin.value ? t('common.slowBackendMessage') : t('login.loggingIn')
})

async function submit() {
  loading.value        = true
  errorMessage.value   = ''
  sessionExpired.value = false
  slowLogin.value      = false

  // El backend gratuito de Render "duerme" tras un rato sin uso y tarda
  // unos segundos en despertar en el primer pedido -- este aviso evita
  // que parezca que la app se colgó mientras eso pasa.
  const slowTimer = setTimeout(() => { slowLogin.value = true }, 4000)

  try {
    const result = await login({ email: email.value, password: password.value })
    router.push(result?.twoFactorRequired ? '/verificacion-dos-pasos' : '/hoy')
  } catch (error) {
    errorMessage.value = error.message || t('login.genericError')
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

.social-button :deep(.v-btn__content) {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}
</style>
