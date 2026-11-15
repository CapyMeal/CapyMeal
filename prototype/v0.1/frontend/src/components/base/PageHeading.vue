<template>
  <!-- Encabezado de pantalla: ilustración + título. Antes estaba copiado
       en Diario, Exportar, Ajustes, páginas legales e Instalar, con dos
       tamaños distintos para lo mismo. La ilustración es decorativa: el
       título ya dice qué es la pantalla. -->
  <div class="page-heading">
    <img v-if="icon" :src="icon" alt="" class="page-heading__icon">
    <h1 class="page-heading__title">{{ title }}</h1>
  </div>
</template>

<script setup>
defineProps({
  icon:  { type: String, default: '' },
  title: { type: String, required: true },
})
</script>

<style scoped>
.page-heading {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-xl);
}

.page-heading__icon {
  width: 44px;
  height: 44px;
  flex-shrink: 0;
  object-fit: contain;
  filter: var(--illustration-shadow-sm);
}

/* "Headline" de DESIGN.md: el mismo tamaño que el título de las
   tarjetas de autenticación. */
.page-heading__title {
  font-family: var(--font-title);
  font-size: var(--font-size-title-lg);
  font-weight: 700;
  line-height: 1.2;
  color: var(--color-title);
  text-wrap: balance;
}
</style>
