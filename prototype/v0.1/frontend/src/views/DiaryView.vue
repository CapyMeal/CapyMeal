<template>
  <MainLayout>
    <PageHeading :icon="diaryIllustration" :title="t('diary.title')" />

    <!-- El filtro se despliega a pedido: como primer elemento de la
         pantalla, dos selectores de fecha le robaban protagonismo a los
         recuerdos. Abre solo si ya hay fechas elegidas. -->
    <div class="diary-filter">
      <button
        type="button"
        class="diary-filter__toggle"
        :aria-expanded="showFilter"
        aria-controls="diary-filter-panel"
        @click="showFilter = !showFilter"
      >
        <v-icon :icon="`svg:${mdiFilterVariant}`" size="18" />
        {{ showFilter ? t('diary.filterHide') : t('diary.filterToggle') }}
      </button>
      <button
        v-if="hasDateFilter"
        type="button"
        class="diary-filter__toggle"
        @click="clearFilter"
      >
        {{ t('diary.filterClear') }}
      </button>
    </div>
    <div v-if="showFilter" id="diary-filter-panel">
      <DateRangeFilter v-model:from="fromDate" v-model:to="toDate" />
    </div>

    <StatusAlert
      v-if="deletedNotice"
      type="success"
      :message="deletedNotice"
      class="diary-status"
    />

    <StatusAlert
      v-if="errorMessage"
      :message="errorMessage"
      retryable
      class="diary-status"
      @retry="loadEntries"
    />
    <CapyLoader v-if="loading" :message="t('diary.loading')" />

    <!-- Con error de carga no se muestra ningún estado vacío: le diría
         "no tenés recuerdos" a alguien que sí tiene, solo que no cargaron. -->
    <template v-else-if="errorMessage" />

    <EmptyState
      v-else-if="entries.length === 0 && !hasDateFilter"
      :message="t('diary.emptyMessage')"
      :action-label="t('diary.emptyAction')"
      @action="$router.push('/hoy')"
    />

    <EmptyState
      v-else-if="!loading && hasDateFilter && entries.length === 0"
      :message="t('diary.emptyFilteredMessage')"
    />

    <!-- Agrupado por mes: se lee como un diario y no como una tabla, y el
         encabezado resuelve el año (las tarjetas no lo repiten). -->
    <div v-else-if="!loading" class="diary-list">
      <section v-for="group in monthGroups" :key="group.key" class="diary-month">
        <h2 class="diary-month__title">{{ group.label }}</h2>
        <div class="diary-month__cards">
          <DiaryCard
            v-for="{ date, entry } in group.entries"
            :key="date"
            :date="date"
            :entry="entry"
          />
        </div>
      </section>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { mdiFilterVariant } from '@mdi/js'
import { formatDate } from '../utils/date'
import MainLayout       from '../layouts/MainLayout.vue'
import DiaryCard        from '../components/diary/DiaryCard.vue'
import EmptyState       from '../components/diary/EmptyState.vue'
import DateRangeFilter  from '../components/base/DateRangeFilter.vue'
import CapyLoader       from '../components/base/CapyLoader.vue'
import PageHeading      from '../components/base/PageHeading.vue'
import StatusAlert      from '../components/base/StatusAlert.vue'
import diaryIllustration from '../assets/icons/diario.png'
import { useMealEntriesByRange } from '../utils/useMealEntriesByRange'

const { t } = useI18n()
const route = useRoute()

// El Detalle vuelve acá con ?eliminado=<fecha> después de borrar un día,
// para que la acción no termine en silencio.
const deletedNotice = computed(() => {
  const date = route.query.eliminado
  if (typeof date !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(date)) return ''
  return t('diary.deletedNotice', {
    date: formatDate(date, { weekday: 'long', day: 'numeric', month: 'long' }),
  })
})

// Nota sobre el catch de useMealEntriesByRange: si el service worker ya
// tenía este pedido cacheado (visita previa con conexión), la respuesta se
// sirve desde el caché y ese catch ni se dispara. Solo se ejecuta si nunca
// se había cargado nada en este dispositivo.
const { entries, loading, errorMessage, fromDate, toDate, hasDateFilter, loadEntries } = useMealEntriesByRange({
  networkErrorMessage: computed(() => t('diary.networkError')),
  genericErrorMessage: computed(() => t('diary.genericError')),
})

const showFilter = ref(hasDateFilter.value)

function clearFilter() {
  fromDate.value = ''
  toDate.value = ''
}

// Las entradas llegan de la más nueva a la más vieja; se agrupan por mes
// conservando ese orden.
const monthGroups = computed(() => {
  const groups = []
  for (const item of entries.value) {
    const key = item.date.slice(0, 7)
    let group = groups.at(-1)
    if (!group || group.key !== key) {
      group = {
        key,
        label: formatDate(`${key}-01`, { month: 'long', year: 'numeric' }),
        entries: [],
      }
      groups.push(group)
    }
    group.entries.push(item)
  }
  return groups
})
</script>

<style scoped>
.diary-filter {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-md);
  margin-bottom: var(--space-md);
}

.diary-filter__toggle {
  display: inline-flex;
  align-items: center;
  gap: .375rem;
  min-height: 40px;
  padding: 0;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--color-primary-text);
  font-weight: 600;
  font-size: var(--font-size-label);
}

.diary-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-xl);
}

.diary-month__title {
  font-family: var(--font-title);
  font-size: var(--font-size-title-sm);
  font-weight: 600;
  color: var(--color-title);
  margin-bottom: var(--space-sm);
}

/* "noviembre de 2026" → "Noviembre de 2026" (solo la primera letra). */
.diary-month__title::first-letter {
  text-transform: uppercase;
}

.diary-month__cards {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

.diary-status {
  margin-bottom: var(--space-md);
}
</style>