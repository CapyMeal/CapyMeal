<template>
  <v-text-field
    :model-value="modelValue"
    :label="label"
    :type="show ? 'text' : 'password'"
    :append-inner-icon="show ? `svg:${mdiEyeOff}` : `svg:${mdiEye}`"
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
import { mdiEye, mdiEyeOff } from '@mdi/js'

defineProps({
  modelValue:   { type: String, default: '' },
  // Sin default en español -- todos los usos actuales ya pasan su
  // propia etiqueta traducida (ver LoginView/RegisterView/etc.), un
  // default acá sería una trampa para un caller futuro que se olvide
  // de pasarla en un idioma que no sea el español.
  label:        { type: String, required: true },
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
