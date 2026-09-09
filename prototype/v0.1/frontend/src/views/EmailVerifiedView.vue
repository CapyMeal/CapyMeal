<template>
  <AuthLayout>
    <AuthCard :title="title" :subtitle="subtitle">
      <template #footer>
        <p>
          <RouterLink to="/hoy">{{ t('emailVerified.goToDiary') }}</RouterLink>
        </p>
      </template>
    </AuthCard>
  </AuthLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import AuthLayout from '../layouts/AuthLayout.vue'
import AuthCard   from '../components/auth/AuthCard.vue'

const route = useRoute()
const { t } = useI18n()

// El caso de link vencido/alterado nunca llega acá -- EmailVerificationController
// corta antes con un 403 del lado del backend, sin llegar a redirigir. "ok" y
// "already" son los dos únicos status que este redirect puede mandar; cualquier
// otro valor (o ninguno, si alguien entra directo a esta URL) cae al mensaje
// genérico.
const title = computed(() =>
  route.query.status === 'already' ? t('emailVerified.alreadyTitle') : t('emailVerified.okTitle')
)

const subtitle = computed(() => {
  if (route.query.status === 'already') {
    return t('emailVerified.alreadySubtitle')
  }
  if (route.query.status === 'ok') {
    return t('emailVerified.okSubtitle')
  }

  return t('emailVerified.defaultSubtitle')
})
</script>
