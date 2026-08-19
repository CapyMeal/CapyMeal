<template>
  <AuthLayout>
    <AuthCard
      v-if="!hasLinkParams"
      title="Enlace inválido 🔒"
    >
      <p class="auth-note">
        Este enlace de recuperación no es válido. Pedí uno nuevo desde
      </p>
      <template #footer>
        <p><RouterLink to="/olvide-contrasena">¿Olvidaste tu contraseña?</RouterLink></p>
      </template>
    </AuthCard>

    <AuthCard
      v-else-if="!done"
      title="Nueva contraseña 🔒"
      subtitle="Elegí una contraseña nueva para tu cuenta."
    >
      <form class="auth-form" @submit.prevent="submit">
        <PasswordField
          v-model="password"
          label="Nueva contraseña"
          placeholder="Mínimo 8 caracteres"
          autocomplete="new-password"
          minlength="8"
        />

        <PasswordField
          v-model="passwordConfirmation"
          label="Confirmá la contraseña"
          placeholder="Repetí tu contraseña"
          autocomplete="new-password"
        />

        <v-alert v-if="errorMessage" type="error" variant="tonal" density="compact">
          {{ errorMessage }}
        </v-alert>

        <CapyButton class="auth-submit" :disabled="loading" type="submit">
          {{ loading ? 'Guardando…' : '✨ Guardar contraseña' }}
        </CapyButton>
      </form>
    </AuthCard>

    <AuthCard
      v-else
      title="¡Todo listo! 🍂"
      subtitle="Tu contraseña fue actualizada. Ya podés iniciar sesión."
    >
      <template #footer>
        <p><RouterLink to="/login">Ir al inicio de sesión →</RouterLink></p>
      </template>
    </AuthCard>
  </AuthLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AuthLayout    from '../layouts/AuthLayout.vue'
import AuthCard      from '../components/auth/AuthCard.vue'
import CapyButton    from '../components/base/CapyButton.vue'
import PasswordField from '../components/base/PasswordField.vue'
import { apiFetch } from '../services/mealEntriesApi'

const route = useRoute()

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
    errorMessage.value = 'Las contraseñas no coinciden.'
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
    errorMessage.value = error.message || 'El enlace es inválido o expiró. Pedí uno nuevo.'
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
