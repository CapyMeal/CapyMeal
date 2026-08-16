<template>
  <AuthLayout>
    <AuthCard title="Bienvenida 🌸" subtitle="Guardemos juntos los pequeños momentos de hoy.">
      <form class="auth-form" @submit.prevent="submit">
        <v-text-field
          v-model="email"
          label="Email"
          type="email"
          placeholder="tu@email.com"
          autocomplete="email"
          required
        />

        <v-text-field
          v-model="password"
          label="Contraseña"
          type="password"
          placeholder="Tu contraseña"
          autocomplete="current-password"
          required
        />

        <v-alert v-if="errorMessage" type="error" variant="tonal" density="compact">
          {{ errorMessage }}
        </v-alert>

        <CapyButton class="auth-submit" :disabled="loading" type="submit">
          {{ loading ? 'Entrando…' : '🌸 Entrar' }}
        </CapyButton>
      </form>

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
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '../layouts/AuthLayout.vue'
import AuthCard   from '../components/auth/AuthCard.vue'
import CapyButton from '../components/base/CapyButton.vue'
import { login } from '../stores/authStore'

const router = useRouter()

const email        = ref('')
const password     = ref('')
const loading      = ref(false)
const errorMessage = ref('')

async function submit() {
  loading.value      = true
  errorMessage.value = ''

  try {
    await login({ email: email.value, password: password.value })
    router.push('/hoy')
  } catch (error) {
    errorMessage.value = error.message || 'No pude iniciar sesión. Intentá nuevamente.'
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
