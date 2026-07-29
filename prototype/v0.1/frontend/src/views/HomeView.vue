<template>
  <div class="splash">
    <div class="splash__content">
      <img
        class="splash__logo"
        src="../assets/logo/Alternativa.png"
        alt="CapyMeal"
      />

      <img
        class="splash__capi"
        src="../assets/illustrations/Chef.png"
        alt="Capi chef"
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
  background: linear-gradient(180deg, #f8f2fa 0%, var(--color-background) 100%);
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
  width: 190px;
  object-fit: contain;
}

.splash__capi {
  width: 220px;
  object-fit: contain;
  filter: drop-shadow(0 10px 30px rgba(0, 0, 0, .12));
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
