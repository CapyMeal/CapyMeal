<template>
  <nav class="bottom-nav" :aria-label="t('nav.label')">
    <div class="bottom-nav__inner">
      <!-- Activa también en las subrutas: en el detalle de un día
           (/recuerdos/<fecha>) "Diario" tiene que seguir marcado. El
           active-class de RouterLink solo mira rutas anidadas, y estas
           son rutas hermanas. -->
      <RouterLink
        v-for="item in items"
        :key="item.to"
        :to="item.to"
        class="bottom-nav__item"
        :class="{ 'bottom-nav__item--active': isActive(item.to) }"
        :aria-current="isActive(item.to) ? 'page' : undefined"
      >
        <span class="bottom-nav__indicator">
          <v-icon :icon="`svg:${item.icon}`" size="24" />
        </span>
        <span class="bottom-nav__label">{{ t(item.label) }}</span>
      </RouterLink>
    </div>
  </nav>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
// Íconos de línea, como el resto de la app (regla de "una sola voz" en
// DESIGN.md). La píldora de fondo ya marca cuál está activo.
import { mdiCalendarTodayOutline, mdiCogOutline, mdiNotebookOutline, mdiTrayArrowDown } from '@mdi/js'

const { t } = useI18n()
const route = useRoute()

function isActive(path) {
  return route.path === path || route.path.startsWith(`${path}/`)
}

const items = [
  { to: '/hoy',       icon: mdiCalendarTodayOutline, label: 'nav.today' },
  { to: '/recuerdos', icon: mdiNotebookOutline,      label: 'nav.diary' },
  { to: '/exportar',  icon: mdiTrayArrowDown,        label: 'nav.pdf' },
  { to: '/ajustes',   icon: mdiCogOutline,           label: 'nav.settings' },
]
</script>

<style scoped>
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: calc(var(--nav-height) + var(--safe-bottom));
  padding-bottom: var(--safe-bottom);

  background: color-mix(in srgb, var(--color-surface) 92%, transparent);
  backdrop-filter: blur(8px);
  border-top: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
}

/* El fondo de la barra ocupa todo el ancho, pero los destinos se quedan
   alineados con la columna de contenido (600px). En una pantalla de PC
   quedaban a ~320px uno del otro y se leía como una app de celular
   estirada. */
.bottom-nav__inner {
  max-width: 600px;
  height: 100%;
  margin: 0 auto;
  display: flex;
  align-items: stretch;
}

.bottom-nav__item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;

  /* Tinta suave (5.6:1) en vez de tinta al 50% de opacidad (2.9:1). */
  color: var(--color-muted);
  transition: color .2s ease;
}

.bottom-nav__item:hover {
  color: var(--color-text);
}

/* La pestaña activa no se marca solo con color: la píldora detrás del
   ícono la distingue también para quien no percibe bien la diferencia
   de tono. */
.bottom-nav__indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 30px;
  border-radius: 999px;
  transition: background-color .2s ease;
}

.bottom-nav__item--active {
  color: var(--color-primary-text);
}

.bottom-nav__item--active .bottom-nav__indicator {
  background: color-mix(in srgb, var(--color-primary) 16%, transparent);
}

.bottom-nav__label {
  font-size: var(--font-size-caption);
  font-weight: 600;
  letter-spacing: .02em;
}

@media (prefers-reduced-motion: reduce) {
  .bottom-nav__item,
  .bottom-nav__indicator {
    transition: none;
  }
}
</style>
