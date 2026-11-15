<template>
  <MainLayout>
    <PageHeading :icon="diaryIllustration" :title="t('diary.title')" />

    <DateRangeFilter v-model:from="fromDate" v-model:to="toDate" />

    <StatusAlert
      v-if="errorMessage"
      :message="errorMessage"
      retryable
      class="diary-status"
      @retry="loadEntries"
    />
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
import PageHeading      from '../components/base/PageHeading.vue'
import StatusAlert      from '../components/base/StatusAlert.vue'
import diaryIllustration from '../assets/icons/diario.png'
import { useMealEntriesByRange } from '../utils/useMealEntriesByRange'

const { t } = useI18n()

// Nota sobre el catch de useMealEntriesByRange: si el service worker ya
// tenía este pedido cacheado (visita previa con conexión), la respuesta se
// sirve desde el caché y ese catch ni se dispara. Solo se ejecuta si nunca
// se había cargado nada en este dispositivo.
const { entries, loading, errorMessage, fromDate, toDate, hasDateFilter, loadEntries } = useMealEntriesByRange({
  networkErrorMessage: computed(() => t('diary.networkError')),
  genericErrorMessage: computed(() => t('diary.genericError')),
})
</script>

<style scoped>
.diary-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

.diary-status {
  margin-bottom: var(--space-md);
}

</style>