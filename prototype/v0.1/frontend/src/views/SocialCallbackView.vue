<template>
  <AuthLayout>
    <AuthCard title="Un momento… 🍂" :subtitle="`Te estamos conectando con ${providerLabel}.`">
      <CapyLoader v-if="!errorMessage" message="Entrando…" />

      <template v-if="errorMessage">
        <v-alert type="error" variant="tonal" density="compact">
          {{ errorMessage }}
        </v-alert>

        <CapyButton class="auth-submit" @click="router.push('/login')">
          Volver a intentar
        </CapyButton>
      </template>
    </AuthCard>
  </AuthLayout>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AuthLayout    from '../layouts/AuthLayout.vue'
import AuthCard      from '../components/auth/AuthCard.vue'
import CapyButton    from '../components/base/CapyButton.vue'
import CapyLoader    from '../components/base/CapyLoader.vue'
import { exchangeSocialCode } from '../stores/authStore'

const props = defineProps({
  provider: { type: String, required: true, validator: v => ['google', 'microsoft'].includes(v) },
})

const route  = useRoute()
const router = useRouter()

const providerLabel = computed(() => props.provider === 'google' ? 'Google' : 'Microsoft')
const errorMessage  = ref('')

onMounted(async () => {
  const code = route.query.code

  if (!code) {
    errorMessage.value = `No pudimos completar el ingreso con ${providerLabel.value}.`
    return
  }

  try {
    await exchangeSocialCode(props.provider, code)
    router.replace('/hoy')
  } catch (error) {
    errorMessage.value = error.message || `No pudimos completar el ingreso con ${providerLabel.value}.`
  }
})
</script>

<style scoped>
.auth-submit {
  width: 100%;
  margin-top: var(--space-xs);
}
</style>
