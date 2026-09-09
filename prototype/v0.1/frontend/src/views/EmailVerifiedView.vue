<template>
  <AuthLayout>
    <AuthCard :title="title" :subtitle="subtitle">
      <template #footer>
        <p>
          <RouterLink to="/hoy">Ir a mi diario</RouterLink>
        </p>
      </template>
    </AuthCard>
  </AuthLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AuthLayout from '../layouts/AuthLayout.vue'
import AuthCard   from '../components/auth/AuthCard.vue'

const route = useRoute()

// El caso de link vencido/alterado nunca llega acá -- EmailVerificationController
// corta antes con un 403 del lado del backend, sin llegar a redirigir. "ok" y
// "already" son los dos únicos status que este redirect puede mandar; cualquier
// otro valor (o ninguno, si alguien entra directo a esta URL) cae al mensaje
// genérico.
const title = computed(() =>
  route.query.status === 'already' ? 'Ya estaba confirmado 🍂' : '¡Email confirmado! 🌱'
)

const subtitle = computed(() => {
  if (route.query.status === 'already') {
    return 'Este email ya estaba verificado, no había nada más que hacer.'
  }
  if (route.query.status === 'ok') {
    return 'Listo, Capi ya sabe que este email es tuyo de verdad.'
  }

  return 'Si venías de confirmar tu email, ya podés volver a CapyMeal tranquilo.'
})
</script>
