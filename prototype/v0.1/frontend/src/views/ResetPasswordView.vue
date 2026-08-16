<template>
  <div class="auth-page">
    <div class="auth-card">

      <img src="../assets/illustrations/Chef.png" alt="Capi" class="auth-card__capi">

      <template v-if="!hasLinkParams">
        <h1 class="auth-card__title">Enlace inválido 🔒</h1>
        <p class="auth-card__subtitle">
          Este enlace de recuperación no es válido. Pedí uno nuevo desde
          <RouterLink to="/olvide-contrasena" class="auth-switch__link">¿Olvidaste tu contraseña?</RouterLink>
        </p>
      </template>

      <template v-else-if="!done">
        <h1 class="auth-card__title">Nueva contraseña 🔒</h1>
        <p class="auth-card__subtitle">Elegí una contraseña nueva para tu cuenta.</p>

        <form class="auth-form" @submit.prevent="submit">
          <div class="auth-field">
            <label class="auth-field__label" for="password">Nueva contraseña</label>
            <input
              id="password"
              v-model="password"
              class="auth-field__input"
              type="password"
              placeholder="Mínimo 8 caracteres"
              autocomplete="new-password"
              required
              minlength="8"
            >
          </div>

          <div class="auth-field">
            <label class="auth-field__label" for="confirm">Confirmá la contraseña</label>
            <input
              id="confirm"
              v-model="passwordConfirmation"
              class="auth-field__input"
              type="password"
              placeholder="Repetí tu contraseña"
              autocomplete="new-password"
              required
            >
          </div>

          <p v-if="errorMessage" class="auth-error">{{ errorMessage }}</p>

          <CapyButton class="auth-submit" :disabled="loading" type="submit">
            {{ loading ? 'Guardando…' : '✨ Guardar contraseña' }}
          </CapyButton>
        </form>
      </template>

      <template v-else>
        <h1 class="auth-card__title">¡Todo listo! 🌸</h1>
        <p class="auth-card__subtitle">Tu contraseña fue actualizada. Ya podés iniciar sesión.</p>
        <RouterLink to="/login" class="auth-switch__link">Ir al inicio de sesión →</RouterLink>
      </template>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import CapyButton from '../components/base/CapyButton.vue'
import { apiFetch } from '../services/mealEntriesApi'

const route = useRoute()

const token               = ref('')
const email               = ref('')
const password            = ref('')
const passwordConfirmation = ref('')
const loading             = ref(false)
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
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(180deg, #fdf6fb 0%, var(--color-background) 100%);
  padding: var(--space-xl);
}

.auth-card {
  background: var(--color-surface);
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--space-2xl) var(--space-xl);
  box-shadow: var(--shadow-md);
  width: 100%;
  max-width: 400px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: var(--space-md);
}

.auth-card__capi {
  width: 100px;
  object-fit: contain;
  filter: drop-shadow(0 4px 12px rgba(169,130,116,.2));
}

.auth-card__title {
  font-family: var(--font-title);
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--color-title);
}

.auth-card__subtitle {
  font-size: .9rem;
  color: var(--color-muted);
  line-height: 1.5;
}

.auth-form {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  margin-top: var(--space-sm);
}

.auth-field {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  text-align: left;
}

.auth-field__label {
  font-size: .85rem;
  font-weight: 600;
  color: var(--color-title);
}

.auth-field__input {
  width: 100%;
  background: var(--color-background);
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-sm);
  padding: var(--space-sm) var(--space-md);
  color: var(--color-text);
  outline: none;
  transition: border-color .2s;
}

.auth-field__input:focus {
  border-color: var(--color-primary);
}

.auth-error {
  font-size: .85rem;
  color: #b94040;
  background: rgba(242,168,168,.15);
  border-radius: var(--radius-sm);
  padding: .5rem var(--space-md);
  text-align: left;
}

.auth-submit {
  width: 100%;
  margin-top: var(--space-xs);
}

.auth-switch__link {
  font-size: .9rem;
  color: var(--color-primary);
  font-weight: 700;
}
</style>
