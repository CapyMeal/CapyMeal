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
          <img src="../assets/icons/capy2.png" alt="Capi" class="today-header__avatar">
          <div>
            <p class="today-header__greeting">Hola, {{ currentUser?.name?.split(' ')[0] }} 🍂</p>
            <p class="today-header__date">{{ formattedDate }}</p>
          </div>
        </div>

        <!-- Selector de fecha discreto -->
        <button
          type="button"
          class="today-header__date-toggle"
          @click="showDatePicker = !showDatePicker"
        >
          📅 {{ showDatePicker ? 'Cerrar' : 'Cambiar día' }}
        </button>

        <div v-if="showDatePicker" class="today-header__date-panel">
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

      <!-- Feedback inline -->
      <p v-if="loading" class="today-status today-status--loading">Cargando…</p>
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
          :icon="meal.icon"
          :icon-image="meal.iconImage"
          :title="meal.title"
          :placeholder="meal.placeholder"
          :saving="loadingFieldKey === meal.key"
          :just-saved="savedFields.has(meal.key)"
          v-model="form[meal.key]"
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
import { getMealEntry, upsertMealEntry } from '../services/mealEntriesApi'
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

async function loadEntryByDate(date) {
  if (!date) { resetFormFields(); return }

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
    if (error.status === 404) { resetFormFields(); return }
    errorMessage.value = 'No pude cargar ese día. Intentá nuevamente.'
  } finally {
    loading.value = false
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
  } catch {
    errorMessage.value = 'No pude guardar este día. Intentá nuevamente.'
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
  } catch {
    // auto-save silencioso: no mostrar error para no interrumpir
  } finally {
    loadingFieldKey.value = ''
  }
}

function resetFormFields() {
  Object.assign(form, { breakfast: '', lunch: '', snack: '', dinner: '', notes: '' })
}

function resetForm() {
  resetFormFields()
  saved.value       = false
  savedFields.value = new Set()
  selectedDate.value = todayISO
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
onMounted(() => loadEntryByDate(selectedDate.value))
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

.today-header__avatar {
  width: 56px;
  height: 56px;
  object-fit: contain;
  border-radius: 50%;
  background: linear-gradient(135deg, #F1DFC9, #E8D2B0);
  padding: 4px;
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

.today-status--loading { color: var(--color-muted); }

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
