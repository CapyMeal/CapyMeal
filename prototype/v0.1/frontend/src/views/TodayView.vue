<template>
  <MainLayout>

    <!-- Confirmación de Capi -->
    <div v-if="saved" class="confirmation">
      <img src="../assets/icons/diaGuardado.webp" alt="" class="confirmation__capi">
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
            <h1 class="today-header__greeting">{{ t('today.greeting', { name: currentUser?.name?.split(' ')[0] }) }}</h1>
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

      <!-- Días sin anotar: una invitación de Capi, no una advertencia. Antes
           era un aviso amarillo con "!" justo el día en que alguien vuelve,
           y "Nunca generar culpa" es el primer principio. Se puede dejar
           para otro momento y no vuelve a aparecer hasta mañana. -->
      <section
        v-if="showGapBanner"
        class="today-gap"
        :aria-label="t('today.gapBannerLabel')"
      >
        <img :src="capyMate" alt="" class="today-gap__capi">
        <div class="today-gap__body">
          <p class="today-gap__text">
            {{ t('today.gapBanner', { date: gapDateFormatted }) }}
          </p>
          <div class="today-gap__actions">
            <button type="button" class="today-gap__action today-gap__action--primary" @click="goToGap">
              {{ t('today.gapBannerButton', { date: gapDateFormatted }) }}
            </button>
            <button type="button" class="today-gap__action" @click="dismissGap">
              {{ t('today.gapBannerDismiss') }}
            </button>
          </div>
        </div>
      </section>

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

      <!-- Tarjetas de comida. El error de un campo va en su tarjeta. -->
      <div class="today-meals">
        <MealCard
          v-for="meal in mealFields"
          :key="meal.key"
          v-model="form[meal.key]"
          :meal="meal.key"
          :title="meal.title"
          :placeholder="meal.placeholder"
          :saving="savingKeys.has(meal.key)"
          :just-saved="recentlySaved.has(meal.key)"
          :persisted="isFieldSaved(meal.key)"
          :error="errorKey === meal.key ? errorMessage : ''"
          @save-field="saveField(meal.key)"
        />
      </div>

      <!-- "Cerrar el día" al final, después del Recuerdo: como todo se
           guarda solo, no hace falta tenerlo fijo en pantalla (la barra
           fija ocupaba ~105px del celular). Es el cierre del ritual que
           lleva al "Listo 🍂", y llega justo cuando se termina de anotar. -->
      <div class="today-close">
        <StatusAlert
          v-if="!loadFailed && !errorKey && errorMessage"
          :message="errorMessage"
          :retryable="saveDayFailed"
          @retry="saveDay"
        />
        <CapyButton :disabled="loading || savingDay" @click="saveDay">
          {{ t('today.saveDay') }}
        </CapyButton>
        <p class="today-autosave-hint">{{ t('today.autosaveHint') }}</p>
      </div>

    </template>

  </MainLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import MainLayout      from '../layouts/MainLayout.vue'
import MealCard        from '../components/meal/MealCard.vue'
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
import capyMate from '../assets/icons/capyMate.png'

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
  errorKey,
  savingKeys,
  recentlySaved,
  load,
  reset,
  saveField,
  flushPending,
  hasAnyContent,
  isFieldSaved,
  markAllSaved,
  setError,
  clearError,
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
  && !gapDismissed.value
)

// "Ahora no" vale por el día: mañana se vuelve a ofrecer, sin insistir hoy.
// Es una comodidad de este dispositivo; si el almacenamiento no está
// disponible (navegación privada), el aviso simplemente vuelve a aparecer.
const GAP_DISMISS_KEY = 'capymeal.gapDismissedOn'

function readGapDismissed() {
  try { return localStorage.getItem(GAP_DISMISS_KEY) === todayISO } catch { return false }
}

const gapDismissed = ref(readGapDismissed())

function dismissGap() {
  gapDismissed.value = true
  try { localStorage.setItem(GAP_DISMISS_KEY, todayISO) } catch { /* sin almacenamiento */ }
}

// Se nombra el primer día sin anotar (el siguiente al último guardado),
// que es justo adónde lleva el botón.
const gapStartDate = computed(() =>
  lastRecordedDate.value ? addDays(lastRecordedDate.value, 1) : ''
)

const gapDateFormatted = computed(() => {
  if (!gapStartDate.value) return ''
  return formatDate(gapStartDate.value, {
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
    setError(t('today.atLeastOneFieldRequired'))
    return
  }

  savingDay.value     = true
  saveDayFailed.value = false
  loadFailed.value    = false
  clearError()

  try {
    // Lo que esperaba la pausa de escritura se manda antes: si no, ese
    // guardado podría llegar después y quedar como si nada.
    await flushPending()
    await upsertMealEntry({ date: selectedDate.value, ...form })
    markAllSaved()
    saved.value = true
  } catch (error) {
    saveDayFailed.value = true
    setError(isNetworkError(error) ? t('today.saveOfflineError') : t('today.saveError'))
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
  if (!gapStartDate.value) return
  selectedDate.value = gapStartDate.value
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
  margin-bottom: var(--space-lg);
}

.today-header__capi-row {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  margin-bottom: var(--space-sm);
}

.today-header__greeting {
  font-family: var(--font-title);
  font-size: var(--font-size-title-md);
  font-weight: 700;
  color: var(--color-title);
  line-height: 1.2;
}

.today-header__date {
  font-size: var(--font-size-body);
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
  margin-bottom: var(--space-md);
}

/* ── Días sin anotar ────────────────────────── */
/* Superficie cálida y sin color de estado: es una sugerencia de Capi. */
.today-gap {
  display: flex;
  align-items: flex-start;
  gap: var(--space-md);
  margin-bottom: var(--space-lg);
  padding: var(--space-md);
  background: var(--color-surface-warm);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
}

.today-gap__capi {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  object-fit: contain;
  filter: var(--illustration-shadow-sm);
}

.today-gap__body {
  min-width: 0;
}

.today-gap__text {
  font-size: var(--font-size-body);
  line-height: 1.5;
  color: var(--color-text);
  text-wrap: pretty;
}

.today-gap__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-md);
  margin-top: var(--space-xs);
}

/* Botones de texto, como "Cambiar día": la invitación no compite con las
   tarjetas, que son la tarea. */
.today-gap__action {
  min-height: 40px;
  padding: 0;
  background: none;
  border: none;
  cursor: pointer;
  font-size: var(--font-size-label);
  font-weight: 600;
  color: var(--color-muted);
}

.today-gap__action--primary {
  color: var(--color-primary-text);
}

.today-gap__action:hover {
  text-decoration: underline;
  text-underline-offset: 3px;
}

/* ── Tarjetas ───────────────────────────────── */
.today-meals {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  margin-bottom: var(--space-xl);
}

/* ── Cerrar el día ──────────────────────────── */
.today-close {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.today-autosave-hint {
  font-size: var(--font-size-caption);
  color: var(--color-muted);
  text-align: center;
  text-wrap: balance;
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
  font-size: var(--font-size-title-md);
  line-height: 1.8;
  color: var(--color-title);
}

.confirmation__message span {
  font-family: var(--font-main);
  font-size: var(--font-size-body);
  color: var(--color-muted);
}

.confirmation__actions {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  width: 100%;
}
</style>
