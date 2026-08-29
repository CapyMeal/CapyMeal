<template>
  <v-card class="detail-meal" elevation="1">
    <v-card-text>
      <p class="detail-meal__label">
        <img
          v-if="meal.iconImage"
          :src="meal.iconImage"
          :alt="meal.title"
          class="detail-meal__icon-image"
        >
        <span v-else>{{ meal.icon }}</span> {{ meal.title }}
      </p>
      <p class="detail-meal__value" :class="{ 'detail-meal__value--empty': !value }">
        {{ value || 'No registrado' }}
      </p>
      <div class="detail-meal__actions">
        <CapyButton
          variant="ghost"
          :disabled="saving"
          @click="$emit('start-edit')"
        >
          {{ editing ? 'Editando...' : `Editar ${meal.label}` }}
        </CapyButton>
      </div>
      <div v-if="editing" class="detail-meal-editor">
        <v-textarea
          :model-value="draft"
          rows="3"
          auto-grow
          density="compact"
          maxlength="2000"
          counter
          @update:model-value="$emit('update:draft', $event)"
        />
        <div class="detail-meal-editor__actions">
          <CapyButton :disabled="saving" @click="$emit('save')">
            {{ saving ? 'Guardando...' : 'Guardar' }}
          </CapyButton>
          <CapyButton variant="ghost" @click="$emit('cancel')">Cancelar</CapyButton>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import CapyButton from '../base/CapyButton.vue'

defineProps({
  meal: { type: Object, required: true },
  value: { type: String, default: '' },
  editing: { type: Boolean, default: false },
  saving: { type: Boolean, default: false },
  draft: { type: String, default: '' },
})

defineEmits(['start-edit', 'save', 'cancel', 'update:draft'])
</script>

<style scoped>
.detail-meal {
  border: 1px solid var(--color-border);
}

.detail-meal :deep(.v-card-text) {
  padding: var(--space-md) var(--space-lg);
}

.detail-meal__label {
  font-size: .8rem;
  font-weight: 700;
  color: var(--color-primary);
  margin-bottom: var(--space-xs);
  display: flex;
  align-items: center;
  gap: var(--space-xs);
}

.detail-meal__icon-image {
  width: 18px;
  height: 18px;
  object-fit: contain;
}

.detail-meal__value {
  font-size: .95rem;
  color: var(--color-text);
  line-height: 1.6;
}

.detail-meal__value--empty {
  opacity: .4;
  font-style: italic;
}

.detail-meal__actions {
  margin-top: var(--space-sm);
}

.detail-meal-editor {
  margin-top: var(--space-sm);
}

.detail-meal-editor__actions {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  margin-top: var(--space-xs);
}
</style>
