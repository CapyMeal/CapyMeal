<template>
  <AuthLayout>
    <AuthCard title="Crear cuenta 🐹" subtitle="Tu diario de comidas te espera.">
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

        <v-text-field
          v-model="password"
          label="Contraseña"
          type="password"
          placeholder="Mínimo 8 caracteres"
          autocomplete="new-password"
          required
        />

        <v-text-field
          v-model="passwordConfirm"
          label="Repetir contraseña"
          type="password"
          placeholder="Repetí tu contraseña"
          autocomplete="new-password"
          required
        />

        <v-alert v-if="errorMessage" type="error" variant="tonal" density="compact">
          {{ errorMessage }}
        </v-alert>

        <CapyButton class="auth-submit" :disabled="loading" type="submit">
          {{ loading ? 'Creando cuenta…' : '🌸 Crear mi cuenta' }}
        </CapyButton>
      </form>

      <template #footer>
        <p>
          ¿Ya tenés cuenta?
          <RouterLink to="/login">Entrá</RouterLink>
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
import { register } from '../stores/authStore'

const router = useRouter()

const name            = ref('')
const email           = ref('')
const password        = ref('')
const passwordConfirm = ref('')
const loading         = ref(false)
const errorMessage    = ref('')

async function submit() {
  if (password.value !== passwordConfirm.value) {
    errorMessage.value = 'Las contraseñas no coinciden.'
    return
  }

  loading.value      = true
  errorMessage.value = ''

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
