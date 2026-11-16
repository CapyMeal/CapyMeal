<template>
  <!-- Elegir una opción entre pocas (tema, idioma). Reemplaza a los
       interruptores de Ajustes: "idioma: apagado" no significaba nada, y
       un interruptor no deja una tercera opción como "Sistema". Patrón de
       radiogroup: Tab entra al grupo y las flechas cambian la opción. -->
  <div class="segmented" role="radiogroup" :aria-labelledby="labelledby">
    <button
      v-for="(option, index) in options"
      :key="option.value"
      ref="buttons"
      type="button"
      role="radio"
      class="segmented__option"
      :aria-checked="option.value === modelValue"
      :tabindex="option.value === modelValue ? 0 : -1"
      @click="select(option.value)"
      @keydown="onKeydown($event, index)"
    >
      {{ option.label }}
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  modelValue: { type: String, required: true },
  // [{ value, label }]
  options:    { type: Array, required: true },
  // id del texto visible que nombra al grupo ("Tema", "Idioma").
  labelledby: { type: String, required: true },
})

const emit = defineEmits(['update:modelValue'])
const buttons = ref([])

function select(value) {
  if (value !== props.modelValue) emit('update:modelValue', value)
}

const STEP = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }

function onKeydown(event, index) {
  const step = STEP[event.key]
  if (!step) return
  event.preventDefault()
  const next = (index + step + props.options.length) % props.options.length
  select(props.options[next].value)
  buttons.value[next]?.focus()
}
</script>

<style scoped>
.segmented {
  display: inline-flex;
  padding: 3px;
  gap: 2px;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 999px;
}

.segmented__option {
  min-height: 36px;
  padding: 0 var(--space-md);
  border: none;
  border-radius: 999px;
  background: none;
  color: var(--color-muted);
  font-size: var(--font-size-label);
  font-weight: 600;
  cursor: pointer;
  transition: background-color .2s ease, color .2s ease;
}

.segmented__option:hover {
  color: var(--color-text);
}

/* La opción elegida se marca con superficie y sombra, no solo con color. */
.segmented__option[aria-checked="true"] {
  background: var(--color-surface);
  color: var(--color-primary-text);
  box-shadow: var(--shadow-sm);
}
</style>
