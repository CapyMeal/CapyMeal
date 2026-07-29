<template>
  <MainLayout>
    <h1>🐹 Hoy</h1>

    <p>Guardemos los momentos de este día.</p>

    <CapyButton>
      Agregar comida
    </CapyButton>
  </MainLayout>
</template>

<script setup>
import MainLayout from '../layouts/MainLayout.vue'
import CapyButton from '../components/base/CapyButton.vue'
</script>

<style scoped>
h1 {
  margin-bottom: 1rem;
}

p {
  margin-bottom: 2rem;
}
</style>