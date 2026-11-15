<template>
  <div class="splash">
    <div class="splash__content">

      <img
        class="splash__capi"
        src="../assets/illustrations/Chef.png"
        alt="Capi chef"
      />

      <div class="splash__brand">
        <h1 class="splash__title">CapyMeal</h1>
        <p class="splash__tagline">
          {{ t('home.taglineLine1') }}<br>
          {{ t('home.taglineLine2') }}
        </p>
      </div>

      <CapyButton class="splash__btn" @click="$router.push('/hoy')">
        {{ t('home.startButton') }}
      </CapyButton>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import CapyButton from '../components/base/CapyButton.vue'

const { t } = useI18n()
</script>

<style scoped>
.splash {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  /* Antes un crema fijo (#FBF3E6) que en tema oscuro quedaba claro. */
  background: linear-gradient(180deg, var(--color-surface-warm) 0%, var(--color-background) 100%);
  padding: var(--space-xl);
}

.splash__content {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: var(--space-lg);
  max-width: 360px;
  width: 100%;
}

.splash__capi {
  width: 260px;
  object-fit: contain;
  filter: var(--illustration-shadow-lg);
}

.splash__brand {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-xs);
}

.splash__title {
  font-family: var(--font-title);
  font-size: 2.4rem;
  font-weight: 700;
  color: var(--color-title);
  letter-spacing: -.5px;
}

.splash__tagline {
  font-size: 1rem;
  color: var(--color-text);
  font-weight: 500;
  line-height: 1.7;
}

.splash__btn {
  width: 100%;
}
</style>
