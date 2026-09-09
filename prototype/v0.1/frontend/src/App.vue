<template>
  <v-app>
    <router-view />
  </v-app>

  <Analytics />
</template>

<script setup>
// No renderiza nada (ver su código fuente) -- solo inyecta el script de
// Vercel Web Analytics una vez y dispara un pageview por cada cambio de
// ruta (usa vue-router internamente). Sin cookies, dentro del plan free
// que ya usa el proyecto en Vercel -- no hace falta ninguna cuenta ni
// infraestructura nueva.
import { Analytics } from '@vercel/analytics/vue'
</script>
