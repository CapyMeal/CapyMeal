<template>
  <div class="privacy-page">
    <div class="privacy-card">
      <RouterLink :to="backTo" class="privacy-back">{{ backLabel }}</RouterLink>

      <div class="privacy-heading">
        <img src="../assets/icons/capy2.png" alt="Capi" class="privacy-heading__icon">
        <h1 class="privacy-heading__title">{{ title }}</h1>
      </div>
      <p class="privacy-updated">{{ updatedLabel }}</p>

      <slot />
    </div>
  </div>
</template>

<script setup>
defineProps({
  backTo: { type: String, required: true },
  backLabel: { type: String, required: true },
  title: { type: String, required: true },
  updatedLabel: { type: String, required: true },
})
</script>

<style scoped>
.privacy-page {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  padding: var(--space-xl) var(--space-md);
  background: var(--color-background);
}

.privacy-card {
  width: 100%;
  max-width: 600px;
}

.privacy-back {
  display: inline-block;
  margin-bottom: var(--space-lg);
  color: var(--color-primary);
  font-weight: 700;
  font-size: .9rem;
}

.privacy-heading {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-xs);
}

.privacy-heading__icon {
  width: 40px;
  height: 40px;
  object-fit: contain;
}

.privacy-heading__title {
  font-family: var(--font-title);
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-title);
}

.privacy-updated {
  font-size: .82rem;
  color: var(--color-muted);
  margin-bottom: var(--space-xl);
}

/* El contenido real de cada página (las <section class="privacy-section">)
   llega por el <slot /> de arriba, compilado con el scope-id de la vista
   que lo pasa (PrivacyPolicyView/TermsOfServiceView), no el de este
   layout -- por eso estos selectores necesitan :slotted() para alcanzarlo,
   en vez de las reglas normales de "scoped" que sólo miran el propio
   template de este componente. */
:slotted(.privacy-section) {
  margin-bottom: var(--space-xl);
}

:slotted(.privacy-section h2) {
  font-family: var(--font-title);
  font-size: 1.05rem;
  color: var(--color-title);
  margin-bottom: var(--space-sm);
}

:slotted(.privacy-section p),
:slotted(.privacy-section li) {
  font-size: .92rem;
  color: var(--color-text);
  line-height: 1.6;
}

:slotted(.privacy-section ul) {
  padding-left: 1.2rem;
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}

:slotted(.privacy-section a) {
  color: var(--color-primary);
  font-weight: 700;
}
</style>
