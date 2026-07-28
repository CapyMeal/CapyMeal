<template>
  <main class="today">
    <h1>🐹 Hoy</h1>

    <p>Guardemos los momentos de este día.</p>

    <BrandButton>
      Agregar comida
    </BrandButton>
  </main>
</template>

<script setup>
import BrandButton from '../components/base/BrandButton.vue'
</script>

<style scoped>
.today {
  padding: 2rem;
}

h1 {
  margin-bottom: 1rem;
}

p {
  margin-bottom: 2rem;
}
</style>