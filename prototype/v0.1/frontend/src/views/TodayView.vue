<template>
  <MainLayout>

    <!-- Confirmación -->
    <div v-if="saved" class="confirmation">
      <p class="confirmation__emoji">🐹</p>
      <p class="confirmation__message">
        Listo 🌸<br>
        Este día ya forma parte de tu diario.
      </p>
      <CapyButton @click="resetForm">Registrar otro día</CapyButton>
    </div>

    <!-- Formulario -->
    <template v-else>
      <div class="today-header">
        <p class="today-header__greeting">
          <img src="../assets/icons/capy2.png" alt="Capi" class="today-header__avatar">
          <span>Hola, Mechi</span>
        </p>
        <p class="today-header__date">{{ formattedDate }}</p>
        <div class="today-header__picker-card">
          <label class="today-header__picker-label" for="entry-date">📅 Elegí el día a registrar</label>
          <input
            id="entry-date"
            v-model="selectedDate"
            class="today-header__picker"
            type="date"
            :max="todayISO"
          >
          <div class="today-header__quick-dates">
            <button type="button" class="today-header__quick-btn" @click="setQuickDate(0)">Hoy</button>
            <button type="button" class="today-header__quick-btn" @click="setQuickDate(1)">Ayer</button>
            <button type="button" class="today-header__quick-btn" @click="setQuickDate(7)">Hace 7 días</button>
          </div>
          <p class="today-header__picker-hint">Se carga hoy por defecto, pero podés registrar cualquier día pasado.</p>
        </div>
      </div>

      <p v-if="errorMessage" class="today-error">{{ errorMessage }}</p>
      <p v-if="successMessage" class="today-success">{{ successMessage }}</p>

      <div class="today-meals">
        <div
          v-for="meal in mealFields"
          :key="meal.key"
          class="today-meal-item"
        >
          <MealCard
            :icon="meal.icon"
            :icon-image="meal.iconImage"
            :title="meal.title"
            :placeholder="meal.placeholder"
            v-model="form[meal.key]"
          />
          <CapyButton
            class="today-meal-save"
            variant="ghost"
            :disabled="loading || !isFieldFilled(meal.key)"
            @click="saveSingleField(meal.key, meal.title)"
          >
            {{ loadingFieldKey === meal.key ? 'Guardando...' : `Guardar ${meal.title.toLowerCase()}` }}
          </CapyButton>
        </div>
      </div>

      <CapyButton class="today-save" :disabled="loading" @click="saveDay">
        🩷 Guardar mi día
      </CapyButton>
    </template>

  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import MainLayout from '../layouts/MainLayout.vue'
import MealCard   from '../components/meal/MealCard.vue'
import CapyButton from '../components/base/CapyButton.vue'
import { getMealEntry, upsertMealEntry } from '../services/mealEntriesApi'
import breakfastIcon from '../assets/icons/desayuno.png'
import lunchIcon from '../assets/icons/almuerzo.png'
import snackIcon from '../assets/icons/merienda.png'
import dinnerIcon from '../assets/icons/cena.png'

const today = new Date()
const todayISO = formatDateISO(today)
const selectedDate = ref(todayISO)

const form = reactive({
  breakfast: '',
  lunch:     '',
  snack:     '',
  dinner:    '',
  notes:     '',
})

const saved = ref(false)
const loading = ref(false)
const loadingFieldKey = ref('')
const errorMessage = ref('')
const successMessage = ref('')

const mealFields = [
  { key: 'breakfast', iconImage: breakfastIcon, title: 'Desayuno', placeholder: '¿Qué desayunaste?' },
  { key: 'lunch', iconImage: lunchIcon, title: 'Almuerzo', placeholder: '¿Qué almorzaste?' },
  { key: 'snack', iconImage: snackIcon, title: 'Merienda', placeholder: '¿Merendaste algo?' },
  { key: 'dinner', iconImage: dinnerIcon, title: 'Cena', placeholder: '¿Qué cenaste?' },
  { key: 'notes', icon: '📝', title: 'Recuerdo', placeholder: '¿Hubo algo especial hoy?' },
]

const formattedDate = computed(() => {
  const [year, month, day] = selectedDate.value.split('-').map(Number)
  return new Date(year, month - 1, day).toLocaleDateString('es-AR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  })
})

async function loadEntryByDate(date) {
  if (!date) {
    resetFormFields()
    return
  }

  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''
  saved.value = false

  try {
    const entry = await getMealEntry(date)
    Object.assign(form, {
      breakfast: entry.breakfast || '',
      lunch: entry.lunch || '',
      snack: entry.snack || '',
      dinner: entry.dinner || '',
      notes: entry.notes || '',
    })
  } catch (error) {
    if (error.status === 404) {
      resetFormFields()
      return
    }

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

  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await upsertMealEntry({
      date: selectedDate.value,
      ...form,
    })
    saved.value = true
    successMessage.value = 'Guardé tu día completo.'
  } catch {
    errorMessage.value = 'No pude guardar este día. Intentá nuevamente.'
  } finally {
    loading.value = false
  }
}

async function saveSingleField(fieldKey, fieldTitle) {
  if (!isFieldFilled(fieldKey)) {
    errorMessage.value = `Completá ${fieldTitle.toLowerCase()} antes de guardar.`
    return
  }

  loadingFieldKey.value = fieldKey
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await upsertMealEntry({
      date: selectedDate.value,
      [fieldKey]: form[fieldKey],
    })

    successMessage.value = `Guardé ${fieldTitle.toLowerCase()}.`
  } catch {
    errorMessage.value = `No pude guardar ${fieldTitle.toLowerCase()}. Intentá nuevamente.`
  } finally {
    loadingFieldKey.value = ''
  }
}

function resetFormFields() {
  Object.assign(form, { breakfast: '', lunch: '', snack: '', dinner: '', notes: '' })
}

function resetForm() {
  resetFormFields()
  saved.value = false
}

function isFieldFilled(fieldKey) {
  const value = form[fieldKey]
  return typeof value === 'string' && value.trim().length > 0
}

function hasAnyContent() {
  return mealFields.some((meal) => isFieldFilled(meal.key))
}

function setQuickDate(daysAgo) {
  selectedDate.value = formatDateISO(shiftDays(new Date(), -daysAgo))
}

function shiftDays(baseDate, days) {
  const nextDate = new Date(baseDate)
  nextDate.setDate(nextDate.getDate() + days)
  return nextDate
}

function formatDateISO(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

watch(selectedDate, (newDate) => {
  loadEntryByDate(newDate)
})

onMounted(() => {
  loadEntryByDate(selectedDate.value)
})
</script>

<style scoped>
.today-header {
  margin-bottom: var(--space-xl);
}

.today-header__greeting {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-title);
  margin-bottom: var(--space-xs);
  display: flex;
  align-items: center;
  gap: var(--space-xs);
}

.today-header__avatar {
  width: 24px;
  height: 24px;
  object-fit: contain;
}

.today-header__date {
  font-size: 1rem;
  color: var(--color-text);
  text-transform: capitalize;
  margin-bottom: var(--space-sm);
}

.today-header__picker-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: var(--space-sm);
  box-shadow: var(--shadow-sm);
}

.today-header__picker-label {
  font-size: .85rem;
  font-weight: 700;
  color: var(--color-primary);
  display: block;
  margin-bottom: var(--space-xs);
}

.today-header__picker {
  width: 100%;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  color: var(--color-text);
  padding: var(--space-sm) var(--space-md);
}

.today-header__picker-hint {
  font-size: .78rem;
  margin-top: var(--space-xs);
  opacity: .75;
}

.today-header__quick-dates {
  display: flex;
  gap: var(--space-xs);
  margin-top: var(--space-xs);
}

.today-header__quick-btn {
  border: 1px solid var(--color-border);
  background: var(--color-background);
  color: var(--color-text);
  border-radius: 999px;
  padding: .3rem .65rem;
  font-size: .75rem;
  cursor: pointer;
}

.today-error {
  font-size: .9rem;
  margin-bottom: var(--space-md);
  color: var(--color-danger);
}

.today-success {
  font-size: .9rem;
  margin-bottom: var(--space-md);
  color: var(--color-success);
}

.today-meals {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  margin-bottom: var(--space-xl);
}

.today-meal-item {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}

.today-meal-save {
  width: 100%;
}

.today-save {
  width: 100%;
}

/* Confirmación */
.confirmation {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: var(--space-lg);
  padding: var(--space-xl) 0;
}

.confirmation__emoji {
  font-size: 4rem;
}

.confirmation__message {
  font-size: 1.2rem;
  line-height: 1.8;
  color: var(--color-title);
}
</style>