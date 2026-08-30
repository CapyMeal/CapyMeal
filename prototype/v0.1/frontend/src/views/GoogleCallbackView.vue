<template>
  <AuthLayout>
    <AuthCard title="Un momento… 🍂" subtitle="Te estamos conectando con Google.">
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
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AuthLayout from '../layouts/AuthLayout.vue'
import AuthCard    from '../components/auth/AuthCard.vue'
import CapyButton  from '../components/base/CapyButton.vue'
import CapyLoader  from '../components/base/CapyLoader.vue'
import { exchangeGoogleCode } from '../stores/authStore'

const route  = useRoute()
const router = useRouter()

const errorMessage = ref('')

onMounted(async () => {
  const code = route.query.code

  if (!code) {
    errorMessage.value = 'No pudimos completar el ingreso con Google.'
    return
  }

  try {
    await exchangeGoogleCode(code)
    router.replace('/hoy')
  } catch (error) {
    errorMessage.value = error.message || 'No pudimos completar el ingreso con Google.'
  }
})
</script>

<style scoped>
.auth-submit {
  width: 100%;
  margin-top: var(--space-xs);
}
</style>
