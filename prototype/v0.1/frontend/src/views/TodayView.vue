<template>
  <MainLayout>

    <!-- Confirmación de Capi -->
    <div v-if="saved" class="confirmation">
      <img src="../assets/icons/diaGuardado.png" alt="Capi" class="confirmation__capi">
      <p class="confirmation__message">
        Listo 🍂<br>
        <span>Este día ya forma parte de tu diario.</span>
      </p>
      <div class="confirmation__actions">
        <CapyButton @click="goToDiary">Ver mi diario</CapyButton>
        <CapyButton variant="ghost" @click="resetForm">Registrar otro día</CapyButton>
      </div>
    </div>

    <!-- Formulario -->
    <template v-else>

      <!-- Encabezado -->
      <div class="today-header">
        <div class="today-header__capi-row">
          <UserAvatar :avatar="currentUser?.avatar" :email="currentUser?.email" :size="56" />
          <div>
            <p class="today-header__greeting">Hola, {{ currentUser?.name?.split(' ')[0] }} 🍂</p>
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
          📅 {{ showDatePicker ? 'Cerrar' : 'Cambiar día' }}
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
            <button type="button" class="today-header__quick-btn" @click="setQuickDate(0)">Hoy</button>
            <button type="button" class="today-header__quick-btn" @click="setQuickDate(1)">Ayer</button>
            <button type="button" class="today-header__quick-btn" @click="setQuickDate(7)">Hace 7 días</button>
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
          Tu último recuerdo fue el {{ gapDateFormatted }}. ¿Seguimos desde ahí?
        </div>
        <CapyButton variant="ghost" class="today-gap-banner__button" @click="goToGap">
          Seguir desde ahí
        </CapyButton>
      </v-alert>

      <!-- Feedback inline -->
      <CapyLoader v-if="loading" message="Cargando…" />
      <v-alert v-if="errorMessage" type="error" variant="tonal" density="compact" class="today-status">
        {{ errorMessage }}
      </v-alert>
      <v-alert v-if="successMessage" type="success" variant="tonal" density="compact" class="today-status">
        {{ successMessage }}
      </v-alert>

      <!-- Tarjetas de comida -->
      <div class="today-meals today-meals--with-bar">
        <MealCard
          v-for="meal in mealFields"
          :key="meal.key"
          v-model="form[meal.key]"
          :icon="meal.icon"
          :icon-image="meal.iconImage"
          :title="meal.title"
          :placeholder="meal.placeholder"
          :saving="loadingFieldKey === meal.key"
          :just-saved="savedFields.has(meal.key)"
          @save-field="saveSingleField(meal.key)"
        />
      </div>

      <StickyActionBar>
        <CapyButton :disabled="loading" @click="saveDay">
          🤎 Guardar mi día
        </CapyButton>
      </StickyActionBar>

    </template>

  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout      from '../layouts/MainLayout.vue'
import MealCard        from '../components/meal/MealCard.vue'
import StickyActionBar from '../components/base/StickyActionBar.vue'
import CapyButton      from '../components/base/CapyButton.vue'
import CapyLoader      from '../components/base/CapyLoader.vue'
import UserAvatar      from '../components/base/UserAvatar.vue'
import { getMealEntries, getMealEntry, upsertMealEntry, isNetworkError } from '../services/mealEntriesApi'
import { currentUser } from '../stores/authStore'
import breakfastIcon from '../assets/icons/desayuno.png'
import lunchIcon     from '../assets/icons/almuerzo.png'
import snackIcon     from '../assets/icons/merienda.png'
import dinnerIcon    from '../assets/icons/cena.png'

const router = useRouter()

const today    = new Date()
const todayISO = formatDateISO(today)

const selectedDate  = ref(todayISO)
const showDatePicker = ref(false)

const form = reactive({
  breakfast: '',
  lunch:     '',
  snack:     '',
  dinner:    '',
  notes:     '',
})

const saved          = ref(false)
const loading        = ref(false)
const loadingFieldKey = ref('')
const errorMessage   = ref('')
const successMessage = ref('')
const savedFields    = ref(new Set())
const lastRecordedDate = ref(null)

const mealFields = [
  { key: 'breakfast', iconImage: breakfastIcon, title: 'Desayuno',  placeholder: 'Ej: café con leche y tostadas' },
  { key: 'lunch',     iconImage: lunchIcon,     title: 'Almuerzo',  placeholder: 'Ej: fideos con tuco y ensalada' },
  { key: 'snack',     iconImage: snackIcon,     title: 'Merienda',  placeholder: 'Ej: mate con facturas' },
  { key: 'dinner',    iconImage: dinnerIcon,    title: 'Cena',      placeholder: 'Ej: pizza con familia' },
  { key: 'notes',     icon: '📝',              title: 'Recuerdo del día', placeholder: '¿Hubo algo especial hoy?' },
]

const formattedDate = computed(() => {
  const [year, month, day] = selectedDate.value.split('-').map(Number)
  return new Date(year, month - 1, day).toLocaleDateString('es-AR', {
    weekday: 'long',
    day:     'numeric',
    month:   'long',
  })
})

const yesterdayISO = formatDateISO(shiftDays(today, -1))

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
  const [year, month, day] = lastRecordedDate.value.split('-').map(Number)
  return new Date(year, month - 1, day).toLocaleDateString('es-AR', {
    day:   'numeric',
    month: 'long',
  })
})

async function loadEntryByDate(date) {
  // Se limpia el formulario de entrada, antes de esperar la respuesta:
  // si no, mientras carga el dia nuevo se siguen viendo las comidas del
  // dia anterior debajo del "Cargando...", como si no hubiera cambiado
  // de dia todavia.
  resetFormFields()

  if (!date) { return }

  loading.value      = true
  errorMessage.value = ''
  successMessage.value = ''
  saved.value        = false
  savedFields.value  = new Set()

  try {
    const entry = await getMealEntry(date)
    Object.assign(form, {
      breakfast: entry.breakfast || '',
      lunch:     entry.lunch     || '',
      snack:     entry.snack     || '',
      dinner:    entry.dinner    || '',
      notes:     entry.notes     || '',
    })
  } catch (error) {
    if (error.status === 404) { return }
    errorMessage.value = 'No pude cargar ese día. Intentá nuevamente.'
  } finally {
    loading.value = false
  }
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
    errorMessage.value = 'Completá al menos una comida o recuerdo antes de guardar.'
    return
  }

  loading.value      = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await upsertMealEntry({ date: selectedDate.value, ...form })
    saved.value = true
  } catch (error) {
    errorMessage.value = isNetworkError(error)
      ? 'No se pudo guardar: estás sin conexión.'
      : 'No pude guardar este día. Intentá nuevamente.'
  } finally {
    loading.value = false
  }
}

async function saveSingleField(fieldKey) {
  const value = form[fieldKey]
  if (!value || !value.trim()) return

  loadingFieldKey.value = fieldKey
  errorMessage.value    = ''

  try {
    await upsertMealEntry({ date: selectedDate.value, [fieldKey]: form[fieldKey] })
    savedFields.value = new Set([...savedFields.value, fieldKey])
    setTimeout(() => {
      const next = new Set(savedFields.value)
      next.delete(fieldKey)
      savedFields.value = next
    }, 2500)
  } catch (error) {
    errorMessage.value = isNetworkError(error)
      ? 'No se pudo guardar automáticamente: estás sin conexión.'
      : 'No pude guardar ese cambio. Intentá nuevamente.'
  } finally {
    loadingFieldKey.value = ''
  }
}

function resetFormFields() {
  Object.assign(form, { breakfast: '', lunch: '', snack: '', dinner: '', notes: '' })
}

function resetForm() {
  // "Registrar otro día" avanza al día siguiente al que se acaba de
  // guardar (util para ponerse al dia con varias fechas atrasadas),
  // sin pasarse de hoy.
  const [year, month, day] = selectedDate.value.split('-').map(Number)
  const nextDate = formatDateISO(shiftDays(new Date(year, month - 1, day), 1))

  resetFormFields()
  saved.value        = false
  savedFields.value  = new Set()
  selectedDate.value = nextDate > todayISO ? todayISO : nextDate
}

function goToDiary() {
  router.push('/recuerdos')
}

function hasAnyContent() {
  return mealFields.some((meal) => {
    const v = form[meal.key]
    return typeof v === 'string' && v.trim().length > 0
  })
}

function setQuickDate(daysAgo) {
  selectedDate.value  = formatDateISO(shiftDays(new Date(), -daysAgo))
  showDatePicker.value = false
}

function goToGap() {
  if (!lastRecordedDate.value) return
  const [year, month, day] = lastRecordedDate.value.split('-').map(Number)
  selectedDate.value = formatDateISO(shiftDays(new Date(year, month - 1, day), 1))
  showDatePicker.value = false
}

function shiftDays(baseDate, days) {
  const d = new Date(baseDate)
  d.setDate(d.getDate() + days)
  return d
}

function formatDateISO(date) {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

watch(selectedDate, (newDate) => loadEntryByDate(newDate))
onMounted(() => {
  loadEntryByDate(selectedDate.value)
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
  text-transform: capitalize;
}

.today-header__date-toggle {
  background: none;
  border: none;
  cursor: pointer;
  font-size: .85rem;
  color: var(--color-primary);
  font-weight: 600;
  padding: .25rem 0;
  display: inline-flex;
  align-items: center;
  gap: .25rem;
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
  padding: .3rem .8rem;
  font-size: .8rem;
  cursor: pointer;
  transition: background .2s, border-color .2s;
}

.today-header__quick-btn:hover {
  border-color: var(--color-primary);
  background: rgba(150,104,74,.12);
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

.today-meals--with-bar {
  margin-bottom: 90px;
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
  filter: drop-shadow(0 4px 12px rgba(105,73,49,.2));
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
