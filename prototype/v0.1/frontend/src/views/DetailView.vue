<template>
  <MainLayout>

    <!-- Confirmar eliminar: dice qué día se borra y que no hay vuelta
         atrás. Su propio error vive acá adentro, al lado de la acción. -->
    <div v-if="confirmingDelete" class="detail-confirm">
      <img src="../assets/icons/eliminar.webp" alt="" class="detail-confirm__capi">
      <p class="detail-confirm__message">{{ t('detail.deleteConfirmMessage', { date: formattedDate }) }}</p>
      <StatusAlert
        v-if="deleteError"
        :message="deleteError"
        retryable
        class="detail-confirm__status"
        @retry="deleteEntry"
      />
      <div class="detail-confirm__actions">
        <CapyButton variant="danger" :disabled="deleting" @click="deleteEntry">
          {{ deleting ? t('detail.deleting') : t('detail.deleteConfirmYes') }}
        </CapyButton>
        <CapyButton variant="ghost" :disabled="deleting" @click="cancelDelete">{{ t('detail.cancel') }}</CapyButton>
      </div>
    </div>

    <template v-else>
      <div class="detail-header">
        <button type="button" class="detail-back" @click="goToDiary">
          <v-icon :icon="`svg:${mdiArrowLeft}`" size="18" />
          {{ t('detail.back') }}
        </button>
        <h1 class="detail-date">{{ formattedDate }}</h1>
        <!-- Mismo aviso que en "Hoy": sin él, editar un día pasado no
             decía si los cambios quedaban. -->
        <p v-if="hasEntry && !loading" class="detail-hint">{{ t('detail.autosaveHint') }}</p>
      </div>

      <CapyLoader v-if="loading" :message="t('detail.loading')" />

      <!-- Falló la carga: el error con "Reintentar", nunca el estado vacío
           de "no encontré ese recuerdo", que diría algo falso. -->
      <StatusAlert
        v-else-if="loadFailed"
        :message="errorMessage"
        retryable
        class="detail-status"
        @retry="loadDay"
      />

      <EmptyState
        v-else-if="!hasEntry"
        :message="t('detail.emptyMessage')"
        :action-label="t('detail.emptyAction')"
        @action="goToDiary"
      />

      <!-- Mismo modelo que "Hoy": cada tarjeta se edita en el lugar y se
           guarda sola al salir del campo, también al vaciarla. -->
      <template v-else>
        <StatusAlert v-if="errorMessage && !errorKey" :message="errorMessage" class="detail-status" />

        <div class="detail-meals">
          <MealCard
            v-for="field in fields"
            :key="field.key"
            v-model="form[field.key]"
            :meal="field.key"
            :title="field.title"
            :placeholder="field.placeholder"
            :saving="savingKeys.has(field.key)"
            :just-saved="recentlySaved.has(field.key)"
            :persisted="isFieldSaved(field.key)"
            :error="errorKey === field.key ? errorMessage : ''"
            @save-field="saveField(field.key)"
          />
        </div>

        <!-- Acciones del día al final del contenido: no tapan tarjetas, no
             quedan en la zona del pulgar y "Eliminar" no pesa lo mismo que
             "Exportar". -->
        <StatusAlert
          v-if="exportError"
          :message="exportError"
          retryable
          class="detail-status"
          @retry="exportDayPdf"
        />
        <div class="detail-actions">
          <CapyButton
            variant="ghost"
            :disabled="exportingPdf"
            :prepend-icon="`svg:${mdiFilePdfBox}`"
            @click="exportDayPdf"
          >
            {{ exportingPdf ? t('common.preparingPdf') : t('detail.exportDayButton') }}
          </CapyButton>
          <button type="button" class="detail-delete" @click="confirmingDelete = true">
            <v-icon :icon="`svg:${mdiDeleteOutline}`" size="20" />
            {{ t('detail.deleteButton') }}
          </button>
        </div>
      </template>
    </template>

  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { mdiArrowLeft, mdiDeleteOutline, mdiFilePdfBox } from '@mdi/js'
import MainLayout  from '../layouts/MainLayout.vue'
import MealCard    from '../components/meal/MealCard.vue'
import EmptyState  from '../components/diary/EmptyState.vue'
import CapyButton  from '../components/base/CapyButton.vue'
import CapyLoader  from '../components/base/CapyLoader.vue'
import StatusAlert from '../components/base/StatusAlert.vue'
import { deleteMealEntry, exportMealEntriesPdf, isNetworkError } from '../services/mealEntriesApi'
import { formatDate } from '../utils/date'
import { MEAL_KEYS } from '../utils/meals'
import { useDayEntry } from '../utils/useDayEntry'

const route  = useRoute()
const router = useRouter()
const { t }  = useI18n()

const dateKey = route.params.date

// Nombre del campo dentro de una frase ("No pude guardar desayuno"):
// el recuerdo usa su etiqueta corta, no "Recuerdo del día".
function fieldLabel(key) {
  return key === 'notes' ? t('meals.notesShortLabel') : t(`meals.${key}`).toLowerCase()
}

const {
  form,
  loading,
  errorMessage,
  errorKey,
  savingKeys,
  recentlySaved,
  load,
  saveField,
  isFieldSaved,
} = useDayEntry({
  date: () => dateKey,
  messages: {
    load: (offline) => (offline ? t('detail.loadOfflineError') : t('detail.loadGenericError')),
    save: (offline, key) => (offline
      ? t('detail.saveOfflineError')
      : t('detail.saveFieldGenericError', { field: fieldLabel(key) })),
    lastField: () => t('common.lastFieldError'),
  },
})

const hasEntry   = ref(false)
const loadFailed = ref(false)

const confirmingDelete = ref(false)
const deleting         = ref(false)
const deleteError      = ref('')

const exportingPdf = ref(false)
const exportError  = ref('')

const placeholderKeys = {
  breakfast: 'detail.editBreakfastPlaceholder',
  lunch:     'detail.editLunchPlaceholder',
  snack:     'detail.editSnackPlaceholder',
  dinner:    'detail.editDinnerPlaceholder',
  notes:     'detail.editNotesPlaceholder',
}

const fields = computed(() => [...MEAL_KEYS, 'notes'].map((key) => ({
  key,
  title:       t(`meals.${key}`),
  placeholder: t(placeholderKeys[key]),
})))

const formattedDate = computed(() => formatDate(dateKey, {
  weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
}))

async function loadDay() {
  const entry = await load()
  // load() devuelve undefined si falló y null si no hay registro.
  loadFailed.value = entry === undefined
  hasEntry.value   = !!entry
}

function goToDiary() {
  router.push('/recuerdos')
}

function cancelDelete() {
  confirmingDelete.value = false
  deleteError.value = ''
}

async function deleteEntry() {
  deleting.value    = true
  deleteError.value = ''

  try {
    await deleteMealEntry(dateKey)
    // El Diario muestra un aviso con la fecha: borrar no termina en silencio.
    router.push({ path: '/recuerdos', query: { eliminado: dateKey } })
  } catch (error) {
    deleteError.value = isNetworkError(error)
      ? t('detail.deleteOfflineError')
      : t('detail.deleteGenericError')
  } finally {
    deleting.value = false
  }
}

async function exportDayPdf() {
  exportingPdf.value = true
  exportError.value  = ''

  try {
    const blob = await exportMealEntriesPdf({ from: dateKey, to: dateKey })

    const fileURL = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = fileURL
    link.download = `capymeal-${dateKey}.pdf`
    link.click()
    window.URL.revokeObjectURL(fileURL)
  } catch {
    exportError.value = t('detail.exportError')
  } finally {
    exportingPdf.value = false
  }
}

onMounted(loadDay)
</script>

<style scoped>
.detail-header {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-xs);
  margin-bottom: var(--space-xl);
}

.detail-back {
  display: inline-flex;
  align-items: center;
  gap: .375rem;
  min-height: 40px;
  padding: 0;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--color-primary-text);
  font-weight: 700;
  font-size: var(--font-size-label);
}

/* Misma voz que el resto de los títulos de pantalla (Fredoka). Antes era
   Inter 800 al lado del botón de volver y en el celular se cortaba en
   "de / 2026". */
.detail-date {
  font-family: var(--font-title);
  font-size: var(--font-size-title-md);
  font-weight: 700;
  line-height: 1.25;
  color: var(--color-title);
  text-wrap: balance;
}

/* Solo la primera letra: capitalize ponía "Jueves, 12 De Noviembre De
   2026", que en español está mal. */
.detail-date::first-letter {
  text-transform: uppercase;
}

.detail-hint {
  font-size: var(--font-size-label);
  color: var(--color-muted);
}

.detail-status {
  margin-bottom: var(--space-md);
}

.detail-meals {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  margin-bottom: var(--space-xl);
}

.detail-actions {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-sm);
}

/* Más aire entre "Exportar" y "Eliminar": pegados, borrar el día quedaba
   al alcance de un toque errado. */
.detail-actions .detail-delete {
  margin-top: var(--space-lg);
}

.detail-delete {
  display: inline-flex;
  align-items: center;
  gap: .375rem;
  min-height: 44px;
  padding: 0 var(--space-md);
  background: none;
  border: none;
  border-radius: var(--radius-control);
  cursor: pointer;
  color: var(--color-danger-text);
  font-weight: 600;
  font-size: var(--font-size-label);
  transition: background-color .2s ease;
}

.detail-delete:hover {
  background: color-mix(in srgb, var(--color-danger) 15%, transparent);
}

/* Confirmar eliminar */
.detail-confirm {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: var(--space-lg);
  padding: var(--space-xl) 0;
}

.detail-confirm__capi {
  width: 150px;
  object-fit: contain;
  filter: var(--illustration-shadow-md);
}

.detail-confirm__message {
  font-size: var(--font-size-title-sm);
  font-weight: 600;
  color: var(--color-title);
  text-wrap: balance;
}

.detail-confirm__status {
  width: 100%;
}

.detail-confirm__actions {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  width: 100%;
}
</style>
