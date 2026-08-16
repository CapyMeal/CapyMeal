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
          Las comidas pasan.<br>
          Los recuerdos quedan.
        </p>
      </div>

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
  background: linear-gradient(180deg, #FBF3E6 0%, var(--color-background) 100%);
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
  filter: drop-shadow(0 12px 32px rgba(169,130,116,.18));
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
