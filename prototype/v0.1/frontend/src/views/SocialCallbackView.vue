<template>
  <AuthLayout>
    <AuthCard :title="t('socialCallback.title')" :subtitle="t('socialCallback.subtitle', { provider: providerLabel })">
      <CapyLoader v-if="!errorMessage" :message="t('socialCallback.loadingMessage')" />

      <template v-if="errorMessage">
        <v-alert type="error" variant="tonal" density="compact">
          {{ errorMessage }}
        </v-alert>

        <CapyButton class="auth-submit" @click="router.push('/login')">
          {{ t('socialCallback.retryButton') }}
        </CapyButton>
      </template>
    </AuthCard>
  </AuthLayout>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
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
const { t }  = useI18n()

const providerLabel = computed(() => props.provider === 'google' ? 'Google' : 'Microsoft')
const errorMessage  = ref('')

onMounted(async () => {
  const code = route.query.code

  if (!code) {
    errorMessage.value = t('socialCallback.connectionError', { provider: providerLabel.value })
    return
  }

  try {
    await exchangeSocialCode(props.provider, code)
    router.replace('/hoy')
  } catch (error) {
    errorMessage.value = error.message || t('socialCallback.connectionError', { provider: providerLabel.value })
  }
})
</script>

<style scoped>
.auth-submit {
  width: 100%;
  margin-top: var(--space-xs);
}
</style>
