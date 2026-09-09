<template>
  <MainLayout>
    <div class="diary-heading">
      <img src="../assets/icons/diario.png" alt="" class="diary-heading__icon">
      <h1 class="diary-heading__title">{{ t('diary.title') }}</h1>
    </div>

    <DateRangeFilter v-model:from="fromDate" v-model:to="toDate" />

    <p v-if="errorMessage" class="diary-error">{{ errorMessage }}</p>
    <CapyLoader v-if="loading" :message="t('diary.loading')" />

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

    <div v-else-if="!loading" class="diary-list">
      <DiaryCard
        v-for="{ date, entry } in entries"
        :key="date"
        :date="date"
        :entry="entry"
      />
    </div>
  </MainLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import MainLayout       from '../layouts/MainLayout.vue'
import DiaryCard        from '../components/diary/DiaryCard.vue'
import EmptyState       from '../components/diary/EmptyState.vue'
import DateRangeFilter  from '../components/base/DateRangeFilter.vue'
import CapyLoader       from '../components/base/CapyLoader.vue'
import { useMealEntriesByRange } from '../utils/useMealEntriesByRange'

const { t } = useI18n()

// Nota sobre el catch de useMealEntriesByRange: si el service worker ya
// tenía este pedido cacheado (visita previa con conexión), la respuesta se
// sirve desde el caché y ese catch ni se dispara. Solo se ejecuta si nunca
// se había cargado nada en este dispositivo.
const { entries, loading, errorMessage, fromDate, toDate } = useMealEntriesByRange({
  networkErrorMessage: computed(() => t('diary.networkError')),
  genericErrorMessage: computed(() => t('diary.genericError')),
})

const hasDateFilter = computed(() => !!fromDate.value || !!toDate.value)
</script>

<style scoped>
.diary-heading {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-xl);
}

.diary-heading__icon {
  width: 44px;
  height: 44px;
  object-fit: contain;
  filter: drop-shadow(0 2px 6px rgba(169,130,116,.2));
}

.diary-heading__title {
  font-family: var(--font-title);
  font-size: 1.6rem;
  font-weight: 700;
  color: var(--color-title);
}

.diary-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

.diary-error {
  font-size: .9rem;
  margin-bottom: var(--space-md);
  color: var(--color-danger);
}

</style>