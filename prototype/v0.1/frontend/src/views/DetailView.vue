<template>
  <MainLayout>

    <!-- Antes que cualquier otra rama: un error de red/servidor puede pasar
         mientras se está confirmando el borrado o cuando todavía no hay
         `entry` (falla el load inicial) -- si este mensaje viviera adentro
         de la rama de lectura/edición, quedaría invisible en esos casos y
         el usuario vería el EmptyState de "no encontré ese recuerdo" (o el
         diálogo de confirmar borrado, sin más) en vez del error real. -->
    <p v-if="errorMessage" class="detail-error">{{ errorMessage }}</p>

    <!-- Confirmar eliminar -->
    <div v-if="confirmingDelete" class="detail-confirm">
      <img src="../assets/icons/eliminar.png" alt="Capi" class="detail-confirm__capi">
      <p class="detail-confirm__message">¿Segura que querés eliminar este recuerdo?</p>
      <div class="detail-confirm__actions">
        <CapyButton variant="danger" @click="deleteEntry">Sí, eliminar</CapyButton>
        <CapyButton variant="ghost" @click="confirmingDelete = false">Cancelar</CapyButton>
      </div>
    </div>

    <CapyLoader v-else-if="loading" message="Cargando el detalle del día..." />

    <!-- Sin entry: puede ser porque de verdad no hay registro, o porque el
         load falló (ver el mensaje de arriba). El resto del template asume
         `entry` no-null, así que este branch tiene que interceptar los dos
         casos -- si no, un load fallido con entry todavía null caería en el
         template de lectura/edición de más abajo e intentaría leer
         entry[meal.key] de null. -->
    <template v-else-if="!entry">
      <EmptyState
        v-if="!errorMessage"
        message="No encontré ese recuerdo."
        action-label="Volver al diario"
        @action="$router.push('/recuerdos')"
      />
    </template>

    <!-- Detalle / Edición -->
    <template v-else>
      <div class="detail-header">
        <button class="detail-back" @click="$router.push('/recuerdos')">
          ← Volver
        </button>
        <p class="detail-date">{{ formattedDate }}</p>
      </div>

      <!-- Modo lectura -->
      <template v-if="!editing">
        <div class="detail-meals detail-meals--with-bar">
          <MealDetailCard
            v-for="meal in allMealsWithNotes"
            :key="meal.key"
            :meal="meal"
            :value="entry[meal.key]"
            :editing="editingMealKey === meal.key"
            :saving="savingMealKey === meal.key"
            :draft="mealDraftValue"
            @update:draft="mealDraftValue = $event"
            @start-edit="startMealEdit(meal.key)"
            @save="saveSingleMeal(meal.key, meal.label)"
            @cancel="cancelMealEdit"
          />
        </div>

        <StickyActionBar>
          <CapyButton @click="startEdit">✏️ Editar</CapyButton>
          <CapyButton :disabled="exportingPdf" variant="ghost" @click="exportDayPdf">
            {{ exportingPdf ? 'Preparando PDF...' : '📄 Exportar este día' }}
          </CapyButton>
          <CapyButton variant="ghost" @click="confirmingDelete = true">🗑 Eliminar</CapyButton>
        </StickyActionBar>
      </template>

      <!-- Modo edición -->
      <template v-else>
        <div class="detail-edit-meals detail-edit-meals--with-bar">
          <MealCard v-model="form.breakfast" :icon-image="breakfastIcon"      title="Desayuno"  placeholder="¿Qué desayunaste?" />
          <MealCard v-model="form.lunch" :icon-image="lunchIcon"      title="Almuerzo"   placeholder="¿Qué almorzaste?"      />
          <MealCard v-model="form.snack" :icon-image="snackIcon"      title="Merienda"  placeholder="¿Merendaste algo?"     />
          <MealCard v-model="form.dinner" :icon-image="dinnerIcon"          title="Cena"      placeholder="¿Qué cenaste?"    />
          <MealCard v-model="form.notes" icon="📝" title="Recuerdo del día" placeholder="¿Hubo algo especial?" />
        </div>

        <StickyActionBar>
          <CapyButton @click="saveEdit">🤎 Guardar cambios</CapyButton>
          <CapyButton variant="ghost" @click="cancelEdit">Cancelar</CapyButton>
        </StickyActionBar>
      </template>
    </template>

  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MainLayout      from '../layouts/MainLayout.vue'
import MealCard        from '../components/meal/MealCard.vue'
import MealDetailCard  from '../components/meal/MealDetailCard.vue'
import EmptyState      from '../components/diary/EmptyState.vue'
import StickyActionBar from '../components/base/StickyActionBar.vue'
import CapyButton from '../components/base/CapyButton.vue'
import CapyLoader from '../components/base/CapyLoader.vue'
import breakfastIcon from '../assets/icons/desayuno.png'
import lunchIcon from '../assets/icons/almuerzo.png'
import snackIcon from '../assets/icons/merienda.png'
import dinnerIcon from '../assets/icons/cena.png'
import {
  deleteMealEntry,
  exportMealEntriesPdf,
  getMealEntry,
  upsertMealEntry,
  isNetworkError,
} from '../services/mealEntriesApi'
import { formatDateEs } from '../utils/date'

const route  = useRoute()
const router = useRouter()

const dateKey = route.params.date

const entry           = ref(null)
const loading = ref(true)
const editing         = ref(false)
const confirmingDelete = ref(false)
const errorMessage = ref('')
const exportingPdf = ref(false)
const editingMealKey = ref('')
const mealDraftValue = ref('')
const savingMealKey = ref('')

const form = reactive({ breakfast: '', lunch: '', snack: '', dinner: '', notes: '' })

const allMeals = [
  { key: 'breakfast', iconImage: breakfastIcon, title: 'Desayuno', label: 'desayuno' },
  { key: 'lunch',     iconImage: lunchIcon, title: 'Almuerzo', label: 'almuerzo' },
  { key: 'snack',     iconImage: snackIcon, title: 'Merienda', label: 'merienda' },
  { key: 'dinner',    iconImage: dinnerIcon, title: 'Cena', label: 'cena' },
]

// label es lo que va en el botón/mensajes ("Editar recuerdo", "Completá
// recuerdo antes de guardar") -- deliberadamente más corto que el title que
// se muestra como encabezado de la tarjeta ("Recuerdo del día").
const notesMeal = { key: 'notes', icon: '📝', title: 'Recuerdo del día', label: 'recuerdo' }
const allMealsWithNotes = [...allMeals, notesMeal]

const formattedDate = computed(() => formatDateEs(dateKey, {
  weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
}))

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
  } catch (error) {
    errorMessage.value = isNetworkError(error)
      ? 'No se pudo guardar: estás sin conexión.'
      : 'No pude guardar los cambios. Intentá nuevamente.'
  }
}

async function deleteEntry() {
  errorMessage.value = ''

  try {
    await deleteMealEntry(dateKey)
    router.push('/recuerdos')
  } catch (error) {
    errorMessage.value = isNetworkError(error)
      ? 'No se pudo eliminar: estás sin conexión.'
      : 'No pude eliminar este día. Intentá nuevamente.'
  }
}

async function loadEntry() {
  loading.value = true
  try {
    // `entry.value` queda null cuando no hay registro para ese dia -- no es
    // un error, la vista ya sabe mostrar el estado "sin registro" para eso.
    entry.value = await getMealEntry(dateKey)
  } catch (error) {
    errorMessage.value = isNetworkError(error)
      ? 'Estás sin conexión — no pude cargar este día.'
      : 'No pude cargar este día. Intentá nuevamente.'
  } finally {
    loading.value = false
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
  } catch (error) {
    errorMessage.value = isNetworkError(error)
      ? 'No se pudo guardar: estás sin conexión.'
      : `No pude guardar ${fieldLabel.toLowerCase()}. Intentá nuevamente.`
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

.detail-edit-meals {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  margin-bottom: var(--space-xl);
}

.detail-meals--with-bar,
.detail-edit-meals--with-bar {
  margin-bottom: 90px;
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

.detail-confirm__capi     { width: 150px; object-fit: contain; filter: drop-shadow(0 4px 12px rgba(105,73,49,.2)); }
.detail-confirm__message  { font-size: 1.1rem; color: var(--color-title); font-weight: 600; }
.detail-confirm__actions  { display: flex; flex-direction: column; gap: var(--space-sm); width: 100%; }
</style>
