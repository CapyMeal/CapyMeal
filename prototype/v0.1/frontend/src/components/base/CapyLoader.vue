<template>
  <div class="capy-loader">
    <img src="../../assets/icons/capy2.png" alt="Capi" class="capy-loader__capi">
    <p v-if="message" class="capy-loader__message">{{ message }}</p>
  </div>
</template>

<script setup>
defineProps({
  message: { type: String, default: '' },
})
</script>

<style scoped>
.capy-loader {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-lg) 0;
}

.capy-loader__capi {
  width: 56px;
  object-fit: contain;
  filter: drop-shadow(0 6px 10px rgba(105,73,49,.25));
  animation: capy-float 1.4s ease-in-out infinite;
}

.capy-loader__message {
  font-size: .88rem;
  color: var(--color-muted);
}

@keyframes capy-float {
  0%, 100% { transform: translateY(0); }
  50%      { transform: translateY(-10px); }
}

@media (prefers-reduced-motion: reduce) {
  .capy-loader__capi {
    animation: none;
  }
}
</style>
