<template>
  <div class="auth-page">
    <div class="auth-card">

      <img src="../assets/illustrations/Chef.png" alt="Capi" class="auth-card__capi">

      <h1 class="auth-card__title">Bienvenida 🌸</h1>
      <p class="auth-card__subtitle">Guardemos juntos los pequeños momentos de hoy.</p>

      <form class="auth-form" @submit.prevent="submit">
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
          <input
            id="password"
            v-model="password"
            class="auth-field__input"
            type="password"
            placeholder="Tu contraseña"
            autocomplete="current-password"
            required
          >
        </div>

        <p v-if="errorMessage" class="auth-error">{{ errorMessage }}</p>

        <CapyButton class="auth-submit" :disabled="loading" type="submit">
          {{ loading ? 'Entrando…' : '🌸 Entrar' }}
        </CapyButton>
      </form>

      <p class="auth-switch">
        ¿No tenés cuenta?
        <RouterLink to="/registro" class="auth-switch__link">Registrate</RouterLink>
      </p>

      <p class="auth-switch">
        <RouterLink to="/olvide-contrasena" class="auth-switch__link auth-switch__link--soft">
          ¿Olvidaste tu contraseña?
        </RouterLink>
      </p>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
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

.auth-switch {
  font-size: .85rem;
  color: var(--color-muted);
}

.auth-switch__link {
  color: var(--color-primary);
  font-weight: 700;
}

.auth-switch__link--soft {
  font-weight: 400;
  color: var(--color-muted);
  font-size: .8rem;
}
</style>
