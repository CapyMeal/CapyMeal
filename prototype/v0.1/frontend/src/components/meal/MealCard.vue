<template>
  <v-card class="meal-card" :class="{ 'meal-card--saved': justSaved }" elevation="1">
    <div class="meal-card__header">
      <MealIcon :meal="meal" :size="44" />
      <h2 :id="titleId" class="meal-card__title">{{ title }}</h2>
      <!-- Tres estados que se leen sin color: guardando (texto), recién
           guardado (tilde + "Guardado") y guardado de antes (tilde suave,
           que queda). Antes el tilde desaparecía a los 2,5 s y no quedaba
           rastro de que lo escrito estaba a salvo. -->
      <span v-if="saving" class="meal-card__status meal-card__status--muted">
        {{ t('common.saving') }}
      </span>
      <span v-else-if="justSaved" class="meal-card__status meal-card__status--fresh">
        <v-icon :icon="`svg:${mdiCheck}`" size="16" />
        {{ t('common.saved') }}
      </span>
      <span
        v-else-if="persisted"
        class="meal-card__status meal-card__status--muted"
        :title="t('common.saved')"
      >
        <v-icon :icon="`svg:${mdiCheck}`" size="16" />
      </span>
    </div>

    <textarea
      ref="textarea"
      class="meal-card__textarea"
      :aria-labelledby="titleId"
      :aria-describedby="error ? errorId : undefined"
      :placeholder="placeholder"
      :value="modelValue"
      rows="1"
      maxlength="2000"
      @input="onInput"
      @blur="$emit('save-field')"
    />

    <StatusAlert v-if="error" :id="errorId" :message="error" class="meal-card__error" />

    <!-- Para lectores de pantalla: el tilde visual no se anuncia solo. -->
    <span class="visually-hidden" aria-live="polite">
      {{ justSaved ? t('common.savedField', { field: title }) : '' }}
    </span>
  </v-card>
</template>

<script setup>
import { ref, watch, nextTick, onMounted, useId } from 'vue'
import { useI18n } from 'vue-i18n'
import { mdiCheck } from '@mdi/js'
import MealIcon from './MealIcon.vue'
import StatusAlert from '../base/StatusAlert.vue'

const props = defineProps({
  // Clave del campo (breakfast, lunch, snack, dinner, notes): la
  // ilustración sale de utils/meals.js, no la elige cada vista.
  meal:        { type: String, required: true },
  title:       { type: String, required: true },
  modelValue:  { type: String, default: '' },
  // Sin default en español: todos los usos actuales ya pasan su propio
  // placeholder traducido (ver TodayView/DetailView).
  placeholder: { type: String, required: true },
  saving:      { type: Boolean, default: false },
  justSaved:   { type: Boolean, default: false },
  // Hay texto y es el que tiene el servidor.
  persisted:   { type: Boolean, default: false },
  // Error de este campo: va en su tarjeta, al lado de lo que falló.
  error:       { type: String, default: '' },
})

// save-field se emite siempre al salir del campo: si hay algo que guardar
// (cambió, o se vació un campo que tenía texto) lo decide useDayEntry.
const emit = defineEmits(['update:modelValue', 'save-field'])

const { t } = useI18n()
const uid = useId()
const titleId = `meal-${uid}-title`
const errorId = `meal-${uid}-error`

const textarea = ref(null)

// El campo arranca de una línea y crece con el texto: cinco cajas de tres
// líneas vacías hacían de "Hoy" un formulario largo antes de escribir nada.
function fitHeight() {
  const el = textarea.value
  if (!el) return
  el.style.height = 'auto'
  el.style.height = `${el.scrollHeight}px`
}

function onInput(event) {
  emit('update:modelValue', event.target.value)
  fitHeight()
}

// El valor también cambia desde afuera (al cargar el día).
watch(() => props.modelValue, () => nextTick(fitHeight))
onMounted(fitHeight)
</script>

<style scoped>
.meal-card {
  border: 1.5px solid var(--color-border);
  padding: var(--space-md) var(--space-lg) var(--space-lg);
  transition: border-color .25s ease, box-shadow .25s ease;
}

.meal-card--saved {
  border-color: var(--color-primary);
  box-shadow: var(--ring-saved) !important;
}

.meal-card__header {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-sm);
}

.meal-card__title {
  font-family: var(--font-title);
  font-size: var(--font-size-title-sm);
  font-weight: 600;
  color: var(--color-title);
  flex: 1;
}

.meal-card__status {
  display: inline-flex;
  align-items: center;
  gap: .25rem;
  font-size: var(--font-size-caption);
  font-weight: 600;
  border-radius: 999px;
  padding: .2rem .5rem;
}

.meal-card__status--fresh {
  color: var(--color-primary-text);
  background: color-mix(in srgb, var(--color-primary) 14%, transparent);
}

.meal-card__status--muted {
  color: var(--color-muted);
}

.meal-card__textarea {
  display: block;
  width: 100%;
  /* Una línea y media de alto mínimo: con el placeholder se lee como un
     lugar para escribir, no como un campo de usuario y contraseña. */
  min-height: calc(1.6em + var(--space-sm) * 2 + 3px);
  overflow: hidden;
  background: var(--color-background);
  border: 1.5px solid transparent;
  /* Mismo radio que la tarjeta (8px): con 12px el campo quedaba más
     redondeado que su contenedor y se veía despegado. */
  border-radius: var(--radius-control);
  padding: var(--space-sm) var(--space-md);
  font-size: var(--font-size-body);
  color: var(--color-text);
  resize: none;
  outline: none;
  line-height: 1.6;
  transition: border-color .2s ease, background .2s ease;
}

.meal-card__textarea:focus {
  border-color: var(--color-primary);
  background: var(--color-surface);
}

/* Sin opacidad extra: tinta suave al 70% daba 2.8:1 sobre arena. */
.meal-card__textarea::placeholder {
  color: var(--color-muted);
  opacity: 1;
}

.meal-card__error {
  margin-top: var(--space-sm);
}
</style>
