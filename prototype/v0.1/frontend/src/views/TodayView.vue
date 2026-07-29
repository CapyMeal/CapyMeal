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
        <p class="today-header__greeting">🐹 Hola, Mechi</p>
        <p class="today-header__date">{{ formattedDate }}</p>
        <label class="today-header__picker-label" for="entry-date">📅 Cambiar fecha</label>
        <input
          id="entry-date"
          v-model="selectedDate"
          class="today-header__picker"
          type="date"
        >
      </div>

      <p v-if="errorMessage" class="today-error">{{ errorMessage }}</p>

      <div class="today-meals">
        <MealCard
          icon="☀️"
          title="Desayuno"
          placeholder="¿Qué desayunaste?"
          v-model="form.breakfast"
        />
        <MealCard
          icon="🍝"
          title="Almuerzo"
          placeholder="¿Qué almorzaste?"
          v-model="form.lunch"
        />
        <MealCard
          icon="🧁"
          title="Merienda"
          placeholder="¿Merendaste algo?"
          v-model="form.snack"
        />
        <MealCard
          icon="🌙"
          title="Cena"
          placeholder="¿Qué cenaste?"
          v-model="form.dinner"
        />
        <MealCard
          icon="📝"
          title="Recuerdo del día"
          placeholder="¿Hubo algo especial hoy?"
          v-model="form.notes"
        />
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

const today = new Date()
const selectedDate = ref(today.toISOString().slice(0, 10))

const form = reactive({
  breakfast: '',
  lunch:     '',
  snack:     '',
  dinner:    '',
  notes:     '',
})

const saved = ref(false)
const loading = ref(false)
const errorMessage = ref('')

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
  loading.value = true
  errorMessage.value = ''

  try {
    await upsertMealEntry({
      date: selectedDate.value,
      ...form,
    })
    saved.value = true
  } catch {
    errorMessage.value = 'No pude guardar este día. Intentá nuevamente.'
  } finally {
    loading.value = false
  }
}

function resetFormFields() {
  Object.assign(form, { breakfast: '', lunch: '', snack: '', dinner: '', notes: '' })
}

function resetForm() {
  resetFormFields()
  saved.value = false
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
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--color-title);
  margin-bottom: var(--space-xs);
}

.today-header__date {
  font-size: 1rem;
  color: var(--color-text);
  text-transform: capitalize;
  margin-bottom: var(--space-sm);
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

.today-error {
  font-size: .9rem;
  margin-bottom: var(--space-md);
  color: var(--color-danger);
}

.today-meals {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  margin-bottom: var(--space-xl);
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