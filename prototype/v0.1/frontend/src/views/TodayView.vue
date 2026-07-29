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
      </div>

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

      <CapyButton class="today-save" @click="saveDay">
        🩷 Guardar mi día
      </CapyButton>
    </template>

  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import MainLayout from '../layouts/MainLayout.vue'
import MealCard   from '../components/meal/MealCard.vue'
import CapyButton from '../components/base/CapyButton.vue'

const STORAGE_KEY = 'capymeal-entries'
const today = new Date()

const formattedDate = computed(() =>
  today.toLocaleDateString('es-AR', { weekday: 'long', day: 'numeric', month: 'long' })
)

const todayKey = today.toISOString().slice(0, 10)

const form = reactive({
  breakfast: '',
  lunch:     '',
  snack:     '',
  dinner:    '',
  notes:     '',
})

const saved = ref(false)

onMounted(() => {
  const entries = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}')
  if (entries[todayKey]) Object.assign(form, entries[todayKey])
})

function saveDay() {
  const entries = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}')
  entries[todayKey] = { ...form }
  localStorage.setItem(STORAGE_KEY, JSON.stringify(entries))
  saved.value = true
}

function resetForm() {
  Object.assign(form, { breakfast: '', lunch: '', snack: '', dinner: '', notes: '' })
  saved.value = false
}
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