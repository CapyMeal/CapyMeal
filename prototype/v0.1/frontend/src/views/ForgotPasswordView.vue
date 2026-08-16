<template>
  <AuthLayout>
    <AuthCard
      :title="sent ? '¡Listo! 🍂' : '¿Olvidaste tu contraseña? 🔑'"
      :subtitle="sent
        ? 'Si existe una cuenta con ese email, vas a recibir un enlace en los próximos minutos. Revisá también la carpeta de spam.'
        : 'Ingresá tu email y te mandamos un enlace para recuperarla.'"
    >
      <form v-if="!sent" class="auth-form" @submit.prevent="submit">
        <v-text-field
          v-model="email"
          label="Email"
          type="email"
          placeholder="tu@email.com"
          autocomplete="email"
          required
        />

        <v-alert v-if="errorMessage" type="error" variant="tonal" density="compact">
          {{ errorMessage }}
        </v-alert>

        <CapyButton class="auth-submit" :disabled="loading" type="submit">
          {{ loading ? 'Enviando…' : '✉️ Enviar enlace' }}
        </CapyButton>
      </form>

      <template #footer>
        <p>
          <RouterLink to="/login">← Volver al inicio de sesión</RouterLink>
        </p>
      </template>
    </AuthCard>
  </AuthLayout>
</template>

<script setup>
import { ref } from 'vue'
import AuthLayout from '../layouts/AuthLayout.vue'
import AuthCard   from '../components/auth/AuthCard.vue'
import CapyButton from '../components/base/CapyButton.vue'
import { apiFetch } from '../services/mealEntriesApi'

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
      errorMessage.value = error.message || 'Ya enviamos un enlace hace poco. Esperá un minuto antes de volver a intentarlo.'
    } else if (error.status) {
      errorMessage.value = error.message || 'Revisá el email ingresado e intentá de nuevo.'
    } else {
      errorMessage.value = 'No pudimos conectar. Revisá tu conexión e intentá de nuevo.'
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
