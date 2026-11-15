<template>
  <MainLayout>

    <!-- Confirmación de Capi -->
    <div v-if="saved" class="confirmation">
      <img src="../assets/icons/diaGuardado.webp" alt="Capi" class="confirmation__capi">
      <p class="confirmation__message">
        {{ t('today.confirmationTitle') }}<br>
        <span>{{ t('today.confirmationSubtitle') }}</span>
      </p>
      <div class="confirmation__actions">
        <CapyButton @click="goToDiary">{{ t('today.viewDiary') }}</CapyButton>
        <CapyButton variant="ghost" @click="resetForm">{{ t('today.registerAnotherDay') }}</CapyButton>
      </div>
    </div>

    <!-- Formulario -->
    <template v-else>

      <!-- Encabezado -->
      <div class="today-header">
        <div class="today-header__capi-row">
          <UserAvatar :avatar="currentUser?.avatar" :email="currentUser?.email" :size="56" />
          <div>
            <p class="today-header__greeting">{{ t('today.greeting', { name: currentUser?.name?.split(' ')[0] }) }}</p>
            <p class="today-header__date">{{ formattedDate }}</p>
          </div>
        </div>

        <!-- Selector de fecha discreto -->
        <button
          type="button"
          class="today-header__date-toggle"
          :aria-expanded="showDatePicker"
          aria-controls="today-date-panel"
          @click="showDatePicker = !showDatePicker"
        >
          <v-icon :icon="`svg:${mdiCalendarMonthOutline}`" size="18" />
          {{ showDatePicker ? t('today.closeDatePicker') : t('today.changeDayLabel') }}
        </button>

        <div v-if="showDatePicker" id="today-date-panel" class="today-header__date-panel">
          <v-text-field
            id="entry-date"
            v-model="selectedDate"
            type="date"
            density="compact"
            hide-details
            :max="todayISO"
          />
          <div class="today-header__quick-dates">
            <button type="button" class="today-header__quick-btn" @click="setQuickDate(0)">{{ t('today.today') }}</button>
            <button type="button" class="today-header__quick-btn" @click="setQuickDate(1)">{{ t('today.yesterday') }}</button>
            <button type="button" class="today-header__quick-btn" @click="setQuickDate(7)">{{ t('today.sevenDaysAgo') }}</button>
          </div>
        </div>
      </div>

      <!-- Aviso de dia salteado -->
      <v-alert
        v-if="showGapBanner"
        type="warning"
        variant="tonal"
        density="compact"
        class="today-status today-gap-banner"
      >
        <div class="today-gap-banner__text">
          {{ t('today.gapBanner', { date: gapDateFormatted }) }}
        </div>
        <CapyButton variant="ghost" class="today-gap-banner__button" @click="goToGap">
          {{ t('today.gapBannerButton') }}
        </CapyButton>
      </v-alert>

      <!-- Feedback inline -->
      <CapyLoader v-if="loading" :message="t('today.loading')" />
      <!-- Si no se pudo cargar el día, el aviso va arriba, con la causa y la
           salida. Los errores de guardado van en la barra de abajo, donde
           está la mirada cuando se aprieta el botón. -->
      <StatusAlert
        v-if="loadFailed && errorMessage"
        :message="errorMessage"
        retryable
        class="today-status"
        @retry="loadSelectedDay"
      />

      <!-- Tarjetas de comida -->
      <div class="today-meals">
        <MealCard
          v-for="meal in mealFields"
          :key="meal.key"
          v-model="form[meal.key]"
          :meal="meal.key"
          :title="meal.title"
          :placeholder="meal.placeholder"
          :saving="savingKey === meal.key"
          :just-saved="recentlySaved.has(meal.key)"
          @save-field="saveField(meal.key)"
        />
      </div>

      <StickyActionBar>
        <StatusAlert
          v-if="!loadFailed && errorMessage"
          :message="errorMessage"
          :retryable="saveDayFailed"
          @retry="saveDay"
        />
        <CapyButton :disabled="loading || savingDay" @click="saveDay">
          {{ t('today.saveDay') }}
        </CapyButton>
      </StickyActionBar>

    </template>

  </MainLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import MainLayout      from '../layouts/MainLayout.vue'
import MealCard        from '../components/meal/MealCard.vue'
import StickyActionBar from '../components/base/StickyActionBar.vue'
import CapyButton      from '../components/base/CapyButton.vue'
import CapyLoader      from '../components/base/CapyLoader.vue'
import UserAvatar      from '../components/base/UserAvatar.vue'
import StatusAlert     from '../components/base/StatusAlert.vue'
import { getMealEntries, upsertMealEntry, isNetworkError } from '../services/mealEntriesApi'
import { currentUser } from '../stores/authStore'
import { formatDate, formatDateISO, addDays } from '../utils/date'
import { MEAL_KEYS } from '../utils/meals'
import { useDayEntry } from '../utils/useDayEntry'
import { mdiCalendarMonthOutline } from '@mdi/js'

const router = useRouter()
const { t }  = useI18n()

const today    = new Date()
const todayISO = formatDateISO(today)

const selectedDate  = ref(todayISO)
const showDatePicker = ref(false)

const {
  form,
  loading,
  errorMessage,
  savingKey,
  recentlySaved,
  load,
  reset,
  saveField,
  hasAnyContent,
  markAllSaved,
} = useDayEntry({
  date: () => selectedDate.value,
  messages: {
    load:      () => t('today.loadError'),
    save:      (offline) => (offline ? t('today.saveFieldOfflineError') : t('today.saveFieldError')),
    lastField: () => t('common.lastFieldError'),
  },
})

const saved            = ref(false)
const savingDay        = ref(false)
const loadFailed       = ref(false)
// Solo el guardado del día entero ofrece "Reintentar": los errores de un
// campo se reintentan solos al volver a salir de él.
const saveDayFailed    = ref(false)
const lastRecordedDate = ref(null)

const mealFields = computed(() => [...MEAL_KEYS, 'notes'].map((key) => ({
  key,
  title:       t(`meals.${key}`),
  placeholder: t(`meals.${key}Placeholder`),
})))

const formattedDate = computed(() => formatDate(selectedDate.value, {
  weekday: 'long',
  day:     'numeric',
  month:   'long',
}))

const yesterdayISO = addDays(todayISO, -1)

// Solo tiene sentido sugerir "seguir desde ahi" mientras se esta mirando
// el dia de hoy: si el ultimo recuerdo es de hoy o ayer no hay hueco real,
// y si el usuario ya cambio de fecha, ya esta resolviendo el hueco solo.
const showGapBanner = computed(() =>
  lastRecordedDate.value
  && lastRecordedDate.value < yesterdayISO
  && selectedDate.value === todayISO
)

const gapDateFormatted = computed(() => {
  if (!lastRecordedDate.value) return ''
  return formatDate(lastRecordedDate.value, {
    day:   'numeric',
    month: 'long',
  })
})

async function loadSelectedDay() {
  saved.value         = false
  saveDayFailed.value = false
  loadFailed.value    = false
  if (!selectedDate.value) {
    reset()
    return
  }
  // load() devuelve undefined solo si falló.
  loadFailed.value = (await load()) === undefined
}

async function loadLastRecordedDate() {
  try {
    const entries = await getMealEntries()
    if (entries.length === 0) { lastRecordedDate.value = null; return }
    lastRecordedDate.value = entries.map((e) => e.date).sort().at(-1)
  } catch {
    lastRecordedDate.value = null
  }
}

async function saveDay() {
  if (!hasAnyContent()) {
    errorMessage.value = t('today.atLeastOneFieldRequired')
    return
  }

  savingDay.value     = true
  saveDayFailed.value = false
  loadFailed.value    = false
  errorMessage.value  = ''

  try {
    await upsertMealEntry({ date: selectedDate.value, ...form })
    markAllSaved()
    saved.value = true
  } catch (error) {
    saveDayFailed.value = true
    errorMessage.value = isNetworkError(error)
      ? t('today.saveOfflineError')
      : t('today.saveError')
  } finally {
    savingDay.value = false
  }
}

function resetForm() {
  // "Registrar otro día" avanza al día siguiente al que se acaba de
  // guardar (util para ponerse al dia con varias fechas atrasadas),
  // sin pasarse de hoy. Si el día no cambia (ya se estaba en hoy), el
  // watch no se dispara y hay que recargar a mano.
  const nextDate = addDays(selectedDate.value, 1)
  const target   = nextDate > todayISO ? todayISO : nextDate

  saved.value = false
  if (target === selectedDate.value) {
    loadSelectedDay()
  } else {
    selectedDate.value = target
  }
}

function goToDiary() {
  router.push('/recuerdos')
}

function setQuickDate(daysAgo) {
  selectedDate.value  = addDays(todayISO, -daysAgo)
  showDatePicker.value = false
}

function goToGap() {
  if (!lastRecordedDate.value) return
  selectedDate.value = addDays(lastRecordedDate.value, 1)
  showDatePicker.value = false
}

watch(selectedDate, loadSelectedDay)
onMounted(() => {
  loadSelectedDay()
  loadLastRecordedDate()
})
</script>

<style scoped>
/* ── Encabezado ─────────────────────────────── */
.today-header {
  margin-bottom: var(--space-xl);
}

.today-header__capi-row {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  margin-bottom: var(--space-sm);
}

.today-header__greeting {
  font-family: var(--font-title);
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--color-title);
  line-height: 1.2;
}

.today-header__date {
  font-size: .9rem;
  color: var(--color-muted);
}

/* Solo la primera letra: capitalize ponía "15 De Noviembre". */
.today-header__date::first-letter {
  text-transform: uppercase;
}

.today-header__date-toggle {
  background: none;
  border: none;
  cursor: pointer;
  font-size: var(--font-size-label);
  /* Terracota base sobre arena daba 4.03:1; la variante de texto pasa
     el 4.5:1 en los dos temas. */
  color: var(--color-primary-text);
  font-weight: 600;
  /* 40px de alto: antes eran ~28px, chico para tocar con el pulgar. */
  min-height: 40px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  gap: .375rem;
}

.today-header__date-panel {
  margin-top: var(--space-sm);
  background: var(--color-surface);
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: var(--space-md);
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.today-header__quick-dates {
  display: flex;
  gap: var(--space-xs);
}

.today-header__quick-btn {
  border: 1.5px solid var(--color-border);
  background: var(--color-background);
  color: var(--color-text);
  border-radius: 999px;
  min-height: 40px;
  padding: 0 var(--space-md);
  font-size: var(--font-size-label);
  cursor: pointer;
  transition: background .2s, border-color .2s;
}

.today-header__quick-btn:hover {
  border-color: var(--color-primary);
  background: color-mix(in srgb, var(--color-primary) 12%, transparent);
}

/* ── Estado inline ──────────────────────────── */
.today-status {
  font-size: .85rem;
  margin-bottom: var(--space-md);
  border-radius: var(--radius-sm);
  padding: .5rem var(--space-md);
}

.today-gap-banner :deep(.v-alert__content) {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.today-gap-banner__text {
  font-size: .9rem;
}

.today-gap-banner__button {
  align-self: flex-start;
  width: auto !important;
  min-width: 0;
}

/* ── Tarjetas ───────────────────────────────── */
.today-meals {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  margin-bottom: var(--space-xl);
}


/* ── Botón principal ────────────────────────── */
.today-save {
  width: 100%;
}

/* ── Confirmación ───────────────────────────── */
.confirmation {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: var(--space-lg);
  padding: var(--space-2xl) 0;
}

.confirmation__capi {
  width: 160px;
  object-fit: contain;
  filter: var(--illustration-shadow-md);
}

.confirmation__message {
  font-family: var(--font-title);
  font-size: 1.35rem;
  line-height: 1.8;
  color: var(--color-title);
}

.confirmation__message span {
  font-family: var(--font-main);
  font-size: 1rem;
  color: var(--color-muted);
}

.confirmation__actions {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  width: 100%;
}
</style>
