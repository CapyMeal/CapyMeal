<template>
  <div class="splash">
    <div class="splash__content">
      <img
        class="splash__logo"
        src="../assets/logo/LogoSinFondo.png"
        alt="CapyMeal"
      />

      <img
        class="splash__capi"
        src="../assets/illustrations/CapyDiaryComfy.png"
        alt="Capi con su libreta"
      />

      <p class="splash__tagline">
        Las comidas pasan.<br>
        Los recuerdos quedan.
      </p>

      <CapyButton class="splash__btn" @click="$router.push('/hoy')">
        🌸 Comenzar
      </CapyButton>
    </div>
  </div>
</template>

<script setup>
import CapyButton from '../components/base/CapyButton.vue'
</script>

<style scoped>
.splash {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-background);
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

.splash__logo {
  width: 160px;
  object-fit: contain;
}

.splash__capi {
  width: 200px;
  object-fit: contain;
  filter: drop-shadow(0 8px 24px rgba(0, 0, 0, .1));
}

.splash__tagline {
  font-size: 1.15rem;
  font-weight: 600;
  color: var(--color-title);
  line-height: 1.7;
}

.splash__btn {
  width: 100%;
}
</style>
