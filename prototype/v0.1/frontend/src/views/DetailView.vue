<template>
  <MainLayout>

    <!-- Confirmar eliminar -->
    <div v-if="confirmingDelete" class="detail-confirm">
      <p class="detail-confirm__emoji">🐹</p>
      <p class="detail-confirm__message">¿Segura que querés eliminar este recuerdo?</p>
      <div class="detail-confirm__actions">
        <CapyButton variant="danger" @click="deleteEntry">Sí, eliminar</CapyButton>
        <CapyButton variant="ghost" @click="confirmingDelete = false">Cancelar</CapyButton>
      </div>
    </div>

    <!-- No encontrado -->
    <EmptyState
      v-else-if="!entry"
      message="No encontré ese recuerdo."
      action-label="Volver al diario"
      @action="$router.push('/recuerdos')"
    />

    <!-- Detalle / Edición -->
    <template v-else>
      <div class="detail-header">
        <button class="detail-back" @click="$router.push('/recuerdos')">
          ← Volver
        </button>
        <p class="detail-date">{{ formattedDate }}</p>
      </div>

      <p v-if="errorMessage" class="detail-error">{{ errorMessage }}</p>

      <!-- Modo lectura -->
      <template v-if="!editing">
        <div class="detail-meals">
          <div v-for="meal in allMeals" :key="meal.key" class="detail-meal">
            <p class="detail-meal__label">
              <span>{{ meal.icon }}</span> {{ meal.title }}
            </p>
            <p class="detail-meal__value" :class="{ 'detail-meal__value--empty': !entry[meal.key] }">
              {{ entry[meal.key] || 'No registrado' }}
            </p>
            <div class="detail-meal__actions">
              <CapyButton
                variant="ghost"
                :disabled="savingMealKey === meal.key"
                @click="startMealEdit(meal.key)"
              >
                {{ editingMealKey === meal.key ? 'Editando...' : `Editar ${meal.title.toLowerCase()}` }}
              </CapyButton>
            </div>
            <div v-if="editingMealKey === meal.key" class="detail-meal-editor">
              <textarea
                v-model="mealDraftValue"
                class="detail-meal-editor__textarea"
                rows="3"
              />
              <div class="detail-meal-editor__actions">
                <CapyButton
                  :disabled="savingMealKey === meal.key"
                  @click="saveSingleMeal(meal.key, meal.title)"
                >
                  {{ savingMealKey === meal.key ? 'Guardando...' : 'Guardar' }}
                </CapyButton>
                <CapyButton variant="ghost" @click="cancelMealEdit">Cancelar</CapyButton>
              </div>
            </div>
          </div>
          <div class="detail-meal">
            <p class="detail-meal__label">📝 Recuerdo del día</p>
            <p class="detail-meal__value" :class="{ 'detail-meal__value--empty': !entry.notes }">
              {{ entry.notes || 'No registrado' }}
            </p>
            <div class="detail-meal__actions">
              <CapyButton
                variant="ghost"
                :disabled="savingMealKey === 'notes'"
                @click="startMealEdit('notes')"
              >
                {{ editingMealKey === 'notes' ? 'Editando...' : 'Editar recuerdo' }}
              </CapyButton>
            </div>
            <div v-if="editingMealKey === 'notes'" class="detail-meal-editor">
              <textarea
                v-model="mealDraftValue"
                class="detail-meal-editor__textarea"
                rows="3"
              />
              <div class="detail-meal-editor__actions">
                <CapyButton
                  :disabled="savingMealKey === 'notes'"
                  @click="saveSingleMeal('notes', 'recuerdo')"
                >
                  {{ savingMealKey === 'notes' ? 'Guardando...' : 'Guardar' }}
                </CapyButton>
                <CapyButton variant="ghost" @click="cancelMealEdit">Cancelar</CapyButton>
              </div>
            </div>
          </div>
        </div>

        <div class="detail-actions">
          <CapyButton @click="startEdit">✏️ Editar</CapyButton>
          <CapyButton :disabled="exportingPdf" variant="ghost" @click="exportDayPdf">
            {{ exportingPdf ? 'Preparando PDF...' : '📄 Exportar este día' }}
          </CapyButton>
          <CapyButton variant="ghost" @click="confirmingDelete = true">🗑 Eliminar</CapyButton>
        </div>
      </template>

      <!-- Modo edición -->
      <template v-else>
        <div class="detail-edit-meals">
          <MealCard icon="☀️" title="Desayuno"      placeholder="¿Qué desayunaste?"  v-model="form.breakfast" />
          <MealCard icon="🍝" title="Almuerzo"      placeholder="¿Qué almorzaste?"   v-model="form.lunch"      />
          <MealCard icon="🧁" title="Merienda"      placeholder="¿Merendaste algo?"  v-model="form.snack"     />
          <MealCard icon="🌙" title="Cena"          placeholder="¿Qué cenaste?"      v-model="form.dinner"    />
          <MealCard icon="📝" title="Recuerdo del día" placeholder="¿Hubo algo especial?" v-model="form.notes" />
        </div>

        <div class="detail-actions">
          <CapyButton @click="saveEdit">🩷 Guardar cambios</CapyButton>
          <CapyButton variant="ghost" @click="cancelEdit">Cancelar</CapyButton>
        </div>
      </template>
    </template>

  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MainLayout from '../layouts/MainLayout.vue'
import MealCard   from '../components/meal/MealCard.vue'
import EmptyState from '../components/diary/EmptyState.vue'
import CapyButton from '../components/base/CapyButton.vue'
import {
  deleteMealEntry,
  exportMealEntriesPdf,
  getMealEntry,
  upsertMealEntry,
} from '../services/mealEntriesApi'

const route  = useRoute()
const router = useRouter()

const dateKey = route.params.date

const entry           = ref(null)
const editing         = ref(false)
const confirmingDelete = ref(false)
const errorMessage = ref('')
const exportingPdf = ref(false)
const editingMealKey = ref('')
const mealDraftValue = ref('')
const savingMealKey = ref('')

const form = reactive({ breakfast: '', lunch: '', snack: '', dinner: '', notes: '' })

const allMeals = [
  { key: 'breakfast', icon: '☀️', title: 'Desayuno' },
  { key: 'lunch',     icon: '🍝', title: 'Almuerzo' },
  { key: 'snack',     icon: '🧁', title: 'Merienda' },
  { key: 'dinner',    icon: '🌙', title: 'Cena' },
]

const formattedDate = computed(() => {
  const [year, month, day] = dateKey.split('-').map(Number)
  return new Date(year, month - 1, day).toLocaleDateString('es-AR', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
  })
})

onMounted(() => {
  loadEntry()
})

function startEdit() {
  Object.assign(form, { ...entry.value })
  editing.value = true
  cancelMealEdit()
}

function cancelEdit() {
  editing.value = false
}

function startMealEdit(fieldKey) {
  editingMealKey.value = fieldKey
  mealDraftValue.value = entry.value?.[fieldKey] || ''
  errorMessage.value = ''
}

function cancelMealEdit() {
  editingMealKey.value = ''
  mealDraftValue.value = ''
}

async function saveEdit() {
  errorMessage.value = ''

  try {
    await upsertMealEntry({
      date: dateKey,
      ...form,
    })

    entry.value = { ...form }
    editing.value = false
  } catch {
    errorMessage.value = 'No pude guardar los cambios. Intentá nuevamente.'
  }
}

async function deleteEntry() {
  errorMessage.value = ''

  try {
    await deleteMealEntry(dateKey)
    router.push('/recuerdos')
  } catch {
    errorMessage.value = 'No pude eliminar este día. Intentá nuevamente.'
  }
}

async function loadEntry() {
  try {
    entry.value = await getMealEntry(dateKey)
  } catch (error) {
    if (error.status === 404) {
      entry.value = null
      return
    }
    errorMessage.value = 'No pude cargar este día. Intentá nuevamente.'
  }
}

async function exportDayPdf() {
  exportingPdf.value = true
  errorMessage.value = ''

  try {
    const blob = await exportMealEntriesPdf({
      from: dateKey,
      to: dateKey,
    })

    const fileURL = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = fileURL
    link.download = `capymeal-${dateKey}.pdf`
    link.click()
    window.URL.revokeObjectURL(fileURL)
  } catch {
    errorMessage.value = 'No pude exportar este día. Intentá nuevamente.'
  } finally {
    exportingPdf.value = false
  }
}

async function saveSingleMeal(fieldKey, fieldLabel) {
  const value = mealDraftValue.value.trim()
  if (!value) {
    errorMessage.value = `Completá ${fieldLabel.toLowerCase()} antes de guardar.`
    return
  }

  savingMealKey.value = fieldKey
  errorMessage.value = ''

  try {
    await upsertMealEntry({
      date: dateKey,
      [fieldKey]: value,
    })

    entry.value = {
      ...entry.value,
      [fieldKey]: value,
    }
    cancelMealEdit()
  } catch {
    errorMessage.value = `No pude guardar ${fieldLabel.toLowerCase()}. Intentá nuevamente.`
  } finally {
    savingMealKey.value = ''
  }
}
</script>

<style scoped>
.detail-header {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  margin-bottom: var(--space-xl);
}

.detail-back {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--color-primary);
  font-weight: 700;
  font-size: .9rem;
  padding: 0;
  flex-shrink: 0;
}

.detail-date {
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--color-title);
  text-transform: capitalize;
}

.detail-error {
  font-size: .9rem;
  margin-bottom: var(--space-md);
  color: var(--color-danger);
}

.detail-meals {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  margin-bottom: var(--space-xl);
}

.detail-meal {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: var(--space-md) var(--space-lg);
  box-shadow: var(--shadow-sm);
}

.detail-meal__label {
  font-size: .8rem;
  font-weight: 700;
  color: var(--color-primary);
  margin-bottom: var(--space-xs);
  display: flex;
  align-items: center;
  gap: var(--space-xs);
}

.detail-meal__value {
  font-size: .95rem;
  color: var(--color-text);
  line-height: 1.6;
}

.detail-meal__value--empty {
  opacity: .4;
  font-style: italic;
}

.detail-meal__actions {
  margin-top: var(--space-sm);
}

.detail-meal-editor {
  margin-top: var(--space-sm);
}

.detail-meal-editor__textarea {
  width: 100%;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  padding: var(--space-sm) var(--space-md);
  color: var(--color-text);
  resize: vertical;
}

.detail-meal-editor__actions {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  margin-top: var(--space-xs);
}

.detail-edit-meals {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  margin-bottom: var(--space-xl);
}

.detail-actions {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
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

.detail-confirm__emoji    { font-size: 3.5rem; }
.detail-confirm__message  { font-size: 1.1rem; color: var(--color-title); font-weight: 600; }
.detail-confirm__actions  { display: flex; flex-direction: column; gap: var(--space-sm); width: 100%; }
</style>
