<template>
  <AuthLayout>
    <AuthCard title="Bienvenida 🍂" subtitle="Guardemos juntos los pequeños momentos de hoy.">
      <form class="auth-form" @submit.prevent="submit">
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
          placeholder="Tu contraseña"
          autocomplete="current-password"
        />

        <v-alert v-if="errorMessage" :type="errorType" variant="tonal" density="compact">
          {{ errorMessage }}
        </v-alert>

        <CapyButton class="auth-submit" :disabled="loading" type="submit">
          {{ loadingButtonLabel }}
        </CapyButton>
      </form>

      <p class="auth-divider">o</p>

      <!-- href real, no @click con router: el login con Google necesita una
           navegación real de nivel superior del navegador para completarse
           (Google no permite terminarlo dentro de un fetch/XHR). -->
      <CapyButton variant="ghost" :href="googleRedirectUrl">
        Iniciar sesión con Google
      </CapyButton>

      <template #footer>
        <p>
          ¿No tenés cuenta?
          <RouterLink to="/registro">Registrate</RouterLink>
        </p>
        <p>
          <RouterLink to="/olvide-contrasena">¿Olvidaste tu contraseña?</RouterLink>
        </p>
      </template>
    </AuthCard>
  </AuthLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import AuthLayout    from '../layouts/AuthLayout.vue'
import AuthCard      from '../components/auth/AuthCard.vue'
import CapyButton    from '../components/base/CapyButton.vue'
import PasswordField from '../components/base/PasswordField.vue'
import { login } from '../stores/authStore'

const router = useRouter()
const route  = useRoute()

// Mismo fallback que ya usan authStore.js/mealEntriesApi.js -- la
// constante está duplicada en varios lugares, aceptado y fuera de
// alcance tocarlo acá.
const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8080'
const googleRedirectUrl = `${apiBaseUrl}/api/auth/google/redirect`

const email          = ref('')
const password       = ref('')
const loading        = ref(false)
const slowLogin      = ref(false)
// Si llegamos acá porque el token expiró (ver handleUnauthorized en
// authStore.js), avisamos por qué en vez de dejar que parezca que el
// login en sí falló.
const sessionExpired = ref(!!route.query.expired)
const errorMessage   = ref(sessionExpired.value ? 'Tu sesión expiró. Iniciá sesión de nuevo.' : '')
const errorType      = computed(() => sessionExpired.value ? 'warning' : 'error')

const loadingButtonLabel = computed(() => {
  if (!loading.value) return '🍂 Entrar'
  return slowLogin.value ? 'Despertando a Capi… 🦫' : 'Entrando…'
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
    await login({ email: email.value, password: password.value })
    router.push('/hoy')
  } catch (error) {
    errorMessage.value = error.message || 'No pude iniciar sesión. Intentá nuevamente.'
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

.auth-divider {
  width: 100%;
  text-align: center;
  color: var(--color-muted);
  font-size: var(--font-size-label);
  margin: var(--space-xs) 0;
}
</style>
