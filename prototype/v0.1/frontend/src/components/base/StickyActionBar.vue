<template>
  <!-- El espaciador ocupa en el flujo la altura real de la barra fija.
       Antes cada vista reservaba un número a mano (90px) y, cuando la
       barra crecía (tres botones, un error), tapaba la última tarjeta. -->
  <div class="sticky-bar-spacer" :style="{ height: `${barHeight}px` }" aria-hidden="true" />
  <div ref="bar" class="sticky-bar">
    <div class="sticky-bar__inner">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const bar = ref(null)
const barHeight = ref(0)
let observer = null

onMounted(() => {
  if (typeof ResizeObserver === 'undefined') return
  observer = new ResizeObserver(([entry]) => {
    barHeight.value = Math.ceil(entry.borderBoxSize?.[0]?.blockSize ?? entry.target.offsetHeight)
  })
  observer.observe(bar.value)
})

onBeforeUnmount(() => observer?.disconnect())
</script>

<style scoped>
.sticky-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: calc(var(--nav-height) + var(--safe-bottom));
  z-index: 9;

  padding: var(--space-sm) var(--space-md);
  background: color-mix(in srgb, var(--color-surface) 92%, transparent);
  backdrop-filter: blur(8px);
  border-top: 1px solid var(--color-border);
}

.sticky-bar__inner {
  max-width: 600px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}

/* En PC el contenido tiene más margen lateral que en el celular: sin
   esto el botón de la barra quedaba más ancho que las tarjetas. */
@media (min-width: 768px) {
  .sticky-bar__inner {
    padding-inline: var(--space-lg);
  }
}
</style>
