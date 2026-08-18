<template>
  <img
    :src="src"
    :alt="alt"
    class="user-avatar"
    :style="{ width: size + 'px', height: size + 'px' }"
    @error="onError"
  >
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { gravatarUrl } from '../../utils/gravatar'
import capyFallback from '../../assets/icons/capy2.png'
import capy1 from '../../assets/icons/capyMate.png'
import capy2 from '../../assets/icons/capyFlower.png'
import capy3 from '../../assets/icons/capyNoFlower.png'

const CAPY_AVATARS = { capy1, capy2, capy3 }

const props = defineProps({
  avatar: { type: String, default: null },
  email:  { type: String, default: '' },
  size:   { type: [Number, String], default: 40 },
  alt:    { type: String, default: 'Capi' },
})

const failed = ref(false)

watch(() => [props.avatar, props.email], () => { failed.value = false })

const src = computed(() => {
  if (props.avatar && CAPY_AVATARS[props.avatar]) {
    return CAPY_AVATARS[props.avatar]
  }

  if (failed.value) {
    return capyFallback
  }

  return gravatarUrl(props.email, Number(props.size) * 2)
})

function onError() {
  failed.value = true
}
</script>

<style scoped>
.user-avatar {
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
  background: var(--color-border);
}
</style>
