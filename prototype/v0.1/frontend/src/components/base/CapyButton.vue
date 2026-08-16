<template>
  <v-btn
    :class="['capy-button', `capy-button--${variant}`]"
    :color="vuetifyColor"
    :variant="vuetifyVariant"
    size="large"
    block
  >
    <slot />
  </v-btn>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: v => ['primary', 'ghost', 'danger'].includes(v),
  },
})

const vuetifyColor = computed(() => ({
  primary: 'primary',
  ghost:   undefined,
  danger:  'error',
}[props.variant]))

const vuetifyVariant = computed(() => ({
  primary: 'flat',
  ghost:   'outlined',
  danger:  'flat',
}[props.variant]))
</script>

<style scoped>
.capy-button {
  font-weight: 600;
  letter-spacing: .01em;
  text-transform: none;
  transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease;
}

.capy-button--primary,
.capy-button--danger {
  box-shadow: var(--shadow-sm);
}

.capy-button--ghost {
  border-color: var(--color-border) !important;
  color: var(--color-text) !important;
}

.capy-button:hover {
  transform: translateY(-2px);
}

.capy-button--primary:hover,
.capy-button--danger:hover {
  box-shadow: var(--shadow-md);
}

.capy-button--ghost:hover {
  border-color: var(--color-primary) !important;
  background: color-mix(in srgb, var(--color-primary) 8%, transparent) !important;
}

.capy-button:active {
  transform: translateY(0);
}

@media (prefers-reduced-motion: reduce) {
  .capy-button {
    transition: none;
  }
  .capy-button:hover {
    transform: none;
  }
}
</style>