<template>
  <div class="auth-page">
    <div class="auth-card">

      <img src="../assets/illustrations/Chef.png" alt="Capi" class="auth-card__capi">

      <template v-if="!sent">
        <h1 class="auth-card__title">¿Olvidaste tu contraseña? 🔑</h1>
        <p class="auth-card__subtitle">Ingresá tu email y te mandamos un enlace para recuperarla.</p>

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

          <p v-if="errorMessage" class="auth-error">{{ errorMessage }}</p>

          <CapyButton class="auth-submit" :disabled="loading" type="submit">
            {{ loading ? 'Enviando…' : '✉️ Enviar enlace' }}
          </CapyButton>
        </form>
      </template>

      <template v-else>
        <h1 class="auth-card__title">¡Listo! 🌸</h1>
        <p class="auth-card__subtitle">
          Si existe una cuenta con ese email, vas a recibir un enlace en los próximos minutos.<br>
          Revisá también la carpeta de spam.
        </p>
      </template>

      <p class="auth-switch">
        <RouterLink to="/login" class="auth-switch__link">← Volver al inicio de sesión</RouterLink>
      </p>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
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
    errorMessage.value = error.message || 'No pude enviar el email de recuperación. Intentá nuevamente.'
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
  line-height: 1.6;
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
</style>
