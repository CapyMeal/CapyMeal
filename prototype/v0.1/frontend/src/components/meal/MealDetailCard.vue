<template>
  <v-card class="detail-meal" elevation="1">
    <v-card-text>
      <p class="detail-meal__label">
        <MealIcon :meal="meal.key" />
        {{ meal.title }}
      </p>
      <p class="detail-meal__value" :class="{ 'detail-meal__value--empty': !value }">
        {{ value || t('common.notRecorded') }}
      </p>
      <div class="detail-meal__actions">
        <CapyButton
          variant="ghost"
          :disabled="saving"
          @click="$emit('start-edit')"
        >
          {{ editing ? t('detail.editingLabel') : t('detail.editLabel', { label: meal.label }) }}
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
            {{ saving ? t('detail.savingLabel') : t('detail.saveLabel') }}
          </CapyButton>
          <CapyButton variant="ghost" @click="$emit('cancel')">{{ t('detail.cancel') }}</CapyButton>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import CapyButton from '../base/CapyButton.vue'
import MealIcon from './MealIcon.vue'

const { t } = useI18n()

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
  font-size: var(--font-size-label);
  font-weight: 700;
  color: var(--color-primary-text);
  margin-bottom: var(--space-xs);
  display: flex;
  align-items: center;
  gap: var(--space-xs);
}

.detail-meal__value {
  font-size: var(--font-size-body);
  color: var(--color-text);
  line-height: 1.6;
}

/* Tinta suave en vez de opacidad .4: "No registrado" tiene que leerse
   (antes quedaba por debajo de 2:1). */
.detail-meal__value--empty {
  color: var(--color-muted);
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
