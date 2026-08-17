<template>
  <div class="empty-state">
    <img src="../../assets/icons/capy2.png" alt="Capi" class="empty-state__capi">
    <p class="empty-state__message">{{ message }}</p>
    <CapyButton v-if="actionLabel" @click="$emit('action')">
      {{ actionLabel }}
    </CapyButton>
  </div>
</template>

<script setup>
import CapyButton from '../base/CapyButton.vue'

defineProps({
  message:     { type: String, required: true },
  actionLabel: { type: String, default: '' },
})

defineEmits(['action'])
</script>

<style scoped>
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: var(--space-lg);
  padding: var(--space-xl) 0;
}

.empty-state__capi {
  width: 120px;
  object-fit: contain;
  filter: drop-shadow(0 4px 12px rgba(105,73,49,.2));
}

.empty-state__message {
  font-size: 1.1rem;
  color: var(--color-text);
  line-height: 1.6;
  max-width: 280px;
}
</style>
