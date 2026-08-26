<template>
  <div class="not-found">
    <div class="not-found__content">

      <img
        class="not-found__capi"
        src="../assets/illustrations/404.png"
        alt="Capi confundido, buscando"
      />

      <div class="not-found__brand">
        <h1 class="not-found__title">404</h1>
        <p class="not-found__tagline">
          Esta página se perdió, como un día sin anotar.<br>
          No la encontramos por ningún lado.
        </p>
      </div>

      <CapyButton class="not-found__btn" @click="goHome">
        🏠 Volver al diario
      </CapyButton>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import CapyButton from '../components/base/CapyButton.vue'
import { isAuthenticated } from '../stores/authStore'

const router = useRouter()

function goHome() {
  router.push(isAuthenticated.value ? '/hoy' : '/')
}
</script>

<style scoped>
.not-found {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(180deg, #FBF3E6 0%, var(--color-background) 100%);
  padding: var(--space-xl);
}

.not-found__content {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: var(--space-lg);
  max-width: 360px;
  width: 100%;
}

.not-found__capi {
  width: 220px;
  object-fit: contain;
  filter: drop-shadow(0 12px 32px rgba(169,130,116,.18));
}

.not-found__brand {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-xs);
}

.not-found__title {
  font-family: var(--font-title);
  font-size: 2.8rem;
  font-weight: 700;
  color: var(--color-title);
  letter-spacing: -.5px;
}

.not-found__tagline {
  font-size: 1rem;
  color: var(--color-text);
  font-weight: 500;
  line-height: 1.7;
}

.not-found__btn {
  width: 100%;
}
</style>
