<template>
  <AuthLayout>
    <AuthCard title="Crear cuenta 🌱" subtitle="Tu diario de comidas te espera.">
      <form class="auth-form" @submit.prevent="submit">
        <v-text-field
          v-model="name"
          label="Nombre"
          type="text"
          placeholder="¿Cómo te llamás?"
          autocomplete="name"
          required
        />

        <v-text-field
          v-model="email"
          label="Email"
          type="email"
          placeholder="tu@email.com"
          autocomplete="email"
          required
        />

        <PasswordField
          v-model="password"
          label="Contraseña"
          placeholder="Mínimo 8 caracteres"
          autocomplete="new-password"
        />

        <PasswordField
          v-model="passwordConfirm"
          label="Repetir contraseña"
          placeholder="Repetí tu contraseña"
          autocomplete="new-password"
        />

        <v-alert v-if="errorMessage" type="error" variant="tonal" density="compact">
          {{ errorMessage }}
        </v-alert>

        <CapyButton class="auth-submit" :disabled="loading" type="submit">
          {{ loadingButtonLabel }}
        </CapyButton>
      </form>

      <p class="auth-divider">o</p>

      <!-- href real, no @click con router: ver la misma nota en LoginView.vue. -->
      <CapyButton variant="ghost" :href="googleRedirectUrl" class="social-button">
        <GoogleIcon />
        Registrarte con Google
      </CapyButton>

      <!-- href real, mismo motivo que el botón de Google de arriba. -->
      <CapyButton variant="ghost" :href="microsoftRedirectUrl" class="social-button">
        <MicrosoftIcon />
        Registrarte con Microsoft
      </CapyButton>

      <template #footer>
        <p>
          ¿Ya tenés cuenta?
          <RouterLink to="/login">Entrá</RouterLink>
        </p>
        <p class="auth-card__legal">
          Al registrarte, aceptás nuestros
          <RouterLink to="/terminos">Términos de servicio</RouterLink> y nuestra
          <RouterLink to="/privacidad">Política de privacidad</RouterLink>.
        </p>
      </template>
    </AuthCard>
  </AuthLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout    from '../layouts/AuthLayout.vue'
import AuthCard      from '../components/auth/AuthCard.vue'
import CapyButton    from '../components/base/CapyButton.vue'
import GoogleIcon    from '../components/base/GoogleIcon.vue'
import MicrosoftIcon from '../components/base/MicrosoftIcon.vue'
import PasswordField from '../components/base/PasswordField.vue'
import { register } from '../stores/authStore'

const router = useRouter()

// Mismo fallback que ya usan authStore.js/mealEntriesApi.js -- la
// constante está duplicada en varios lugares, aceptado y fuera de
// alcance tocarlo acá.
const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8080'
const googleRedirectUrl = `${apiBaseUrl}/api/auth/google/redirect`
const microsoftRedirectUrl = `${apiBaseUrl}/api/auth/microsoft/redirect`

const name             = ref('')
const email             = ref('')
const password           = ref('')
const passwordConfirm    = ref('')
const loading            = ref(false)
const errorMessage       = ref('')
const slowLogin          = ref(false)

const loadingButtonLabel = computed(() => {
  if (!loading.value) return '🍂 Crear mi cuenta'
  return slowLogin.value ? 'Despertando a Capi… 🦫' : 'Creando cuenta…'
})

async function submit() {
  if (password.value !== passwordConfirm.value) {
    errorMessage.value = 'Las contraseñas no coinciden.'
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
    errorMessage.value = error.message || 'No pude crear la cuenta. Intentá nuevamente.'
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

.auth-card__legal {
  font-size: .78rem;
  opacity: .8;
}

.auth-divider {
  width: 100%;
  text-align: center;
  color: var(--color-muted);
  font-size: var(--font-size-label);
  margin: var(--space-xs) 0;
}

.social-button :deep(.v-btn__content) {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}
</style>
