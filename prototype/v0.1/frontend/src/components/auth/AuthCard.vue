<template>
  <v-card class="auth-card" elevation="2" max-width="400">
    <v-card-text class="auth-card__content">
      <img src="../../assets/illustrations/Chef.png" alt="Capi" class="auth-card__capi">

      <h1 class="auth-card__title">{{ title }}</h1>
      <p v-if="subtitle" class="auth-card__subtitle">{{ subtitle }}</p>

      <slot />

      <div v-if="$slots.footer" class="auth-card__footer">
        <slot name="footer" />
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    default: '',
  },
})
</script>

<style scoped>
.auth-card {
  width: 100%;
}

.auth-card__content {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: var(--space-md);
  padding: var(--space-2xl) var(--space-xl) !important;
}

.auth-card__capi {
  width: 100px;
  object-fit: contain;
  filter: drop-shadow(0 4px 12px rgba(169,130,116,.2));
}

.auth-card__title {
  font-family: var(--font-title);
  font-size: var(--font-size-title-lg);
  font-weight: 700;
  color: var(--color-title);
}

.auth-card__subtitle {
  font-size: var(--font-size-body);
  color: var(--color-muted);
  line-height: 1.6;
}

.auth-card :deep(.auth-form) {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  margin-top: var(--space-sm);
}

.auth-card__footer {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  font-size: var(--font-size-label);
  color: var(--color-muted);
}

.auth-card__footer :deep(a) {
  color: var(--color-primary);
  font-weight: 700;
}
</style>
