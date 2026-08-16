<template>
  <div class="date-range-filter">
    <v-text-field
      :model-value="from"
      label="Desde"
      type="date"
      density="compact"
      @update:model-value="$emit('update:from', $event)"
    />
    <v-text-field
      :model-value="to"
      label="Hasta"
      type="date"
      density="compact"
      @update:model-value="$emit('update:to', $event)"
    />
  </div>
</template>

<script setup>
defineProps({
  from: { type: String, default: '' },
  to:   { type: String, default: '' },
})

defineEmits(['update:from', 'update:to'])
</script>

<style scoped>
.date-range-filter {
  display: grid;
  gap: var(--space-sm);
  grid-template-columns: 1fr 1fr;
  margin-bottom: var(--space-lg);
}
</style>
