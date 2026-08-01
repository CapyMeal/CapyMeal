<template>
  <div class="auth-page">
    <div class="auth-card">

      <img src="../assets/illustrations/Chef.png" alt="Capi" class="auth-card__capi">

      <h1 class="auth-card__title">Crear cuenta 🐹</h1>
      <p class="auth-card__subtitle">Tu diario de comidas te espera.</p>

      <form class="auth-form" @submit.prevent="submit">
        <div class="auth-field">
          <label class="auth-field__label" for="name">Nombre</label>
          <input
            id="name"
            v-model="name"
            class="auth-field__input"
            type="text"
            placeholder="¿Cómo te llamás?"
            autocomplete="name"
            required
          >
        </div>

        <div class="auth-field">
          <label class="auth-field__label" for="email">Email</label>
          <input
            id="email"
            v-model="email"
            class="auth-field__input"
            type="email"
            placeholder="tu@email.com"
            autocomplete="email"
            required
          >
        </div>

        <div class="auth-field">
          <label class="auth-field__label" for="password">Contraseña</label>
          <div class="auth-field__password-wrap">
            <input
              id="password"
              v-model="password"
              class="auth-field__input auth-field__input--password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Mínimo 8 caracteres"
              autocomplete="new-password"
              required
            >
            <button
              type="button"
              class="auth-field__toggle"
              @click="showPassword = !showPassword"
            >
              {{ showPassword ? '🙈' : '👁️' }}
            </button>
          </div>
        </div>

        <div class="auth-field">
          <label class="auth-field__label" for="password-confirm">Repetir contraseña</label>
          <div class="auth-field__password-wrap">
            <input
              id="password-confirm"
              v-model="passwordConfirm"
              class="auth-field__input auth-field__input--password"
              :type="showPasswordConfirm ? 'text' : 'password'"
              placeholder="Repetí tu contraseña"
              autocomplete="new-password"
              required
            >
            <button
              type="button"
              class="auth-field__toggle"
              @click="showPasswordConfirm = !showPasswordConfirm"
            >
              {{ showPasswordConfirm ? '🙈' : '👁️' }}
            </button>
          </div>
        </div>

        <p v-if="errorMessage" class="auth-error">{{ errorMessage }}</p>

        <CapyButton class="auth-submit" :disabled="loading" type="submit">
          {{ loading ? 'Creando cuenta…' : '🌸 Crear mi cuenta' }}
        </CapyButton>
      </form>

      <p class="auth-switch">
        ¿Ya tenés cuenta?
        <RouterLink to="/login" class="auth-switch__link">Entrá</RouterLink>
      </p>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import CapyButton from '../components/base/CapyButton.vue'
import { register } from '../stores/authStore'

const router = useRouter()

const name            = ref('')
const email           = ref('')
const password        = ref('')
const passwordConfirm = ref('')
const showPassword    = ref(false)
const showPasswordConfirm = ref(false)
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
  width: 90px;
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

.auth-field__password-wrap {
  position: relative;
}

.auth-field__input--password {
  padding-right: 2.5rem;
}

.auth-field__toggle {
  position: absolute;
  top: 50%;
  right: .65rem;
  transform: translateY(-50%);
  border: none;
  background: none;
  cursor: pointer;
  font-size: .95rem;
  line-height: 1;
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

.auth-switch {
  font-size: .85rem;
  color: var(--color-muted);
}

.auth-switch__link {
  color: var(--color-primary);
  font-weight: 700;
}
</style>
