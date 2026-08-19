<template>
  <v-text-field
    :model-value="modelValue"
    :label="label"
    :type="show ? 'text' : 'password'"
    :append-inner-icon="show ? 'mdi-eye-off' : 'mdi-eye'"
    :placeholder="placeholder"
    :autocomplete="autocomplete"
    :minlength="minlength"
    required
    @update:model-value="$emit('update:modelValue', $event)"
    @click:append-inner="show = !show"
  />
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  modelValue:   { type: String, default: '' },
  label:        { type: String, default: 'Contraseña' },
  placeholder:  { type: String, default: '' },
  autocomplete: { type: String, default: 'current-password' },
  minlength:    { type: [String, Number], default: undefined },
})

defineEmits(['update:modelValue'])

// Cada instancia lleva su propio estado -- dos <PasswordField> en el
// mismo formulario (ej. contraseña + repetir) se muestran/ocultan
// de forma independiente, como ya se comportaba antes de extraer esto.
const show = ref(false)
</script>
