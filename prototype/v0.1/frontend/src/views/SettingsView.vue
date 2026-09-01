<template>
  <MainLayout>
    <div class="settings-heading">
      <img src="../assets/icons/ajustes.png" alt="" class="settings-heading__icon">
      <h1 class="settings-heading__title">Ajustes</h1>
    </div>

    <div class="settings-list">

      <!-- Tema -->
      <div class="settings-item">
        <div class="settings-item__info">
          <span class="settings-item__icon">🎨</span>
          <div>
            <p class="settings-item__label">Tema</p>
            <p class="settings-item__desc">{{ isDark ? 'Oscuro' : 'Claro' }}</p>
          </div>
        </div>
        <v-switch
          :model-value="isDark"
          color="primary"
          hide-details
          density="compact"
          @update:model-value="toggleTheme"
        />
      </div>

      <hr class="settings-divider" />

      <!-- Avatar -->
      <div class="settings-item settings-item--static">
        <div class="settings-avatar-picker">
          <p class="settings-item__label">Avatar</p>
          <p v-if="avatarError" class="settings-avatar-picker__error">{{ avatarError }}</p>
          <div class="settings-avatar-picker__options">
            <button
              type="button"
              class="settings-avatar-picker__option"
              :class="{ 'settings-avatar-picker__option--active': !currentUser?.avatar }"
              :disabled="savingAvatar"
              title="Gravatar"
              @click="selectAvatar(null)"
            >
              <UserAvatar :avatar="null" :email="currentUser?.email" :size="44" />
            </button>
            <button
              v-for="option in avatarOptions"
              :key="option.value"
              type="button"
              class="settings-avatar-picker__option"
              :class="{ 'settings-avatar-picker__option--active': currentUser?.avatar === option.value }"
              :disabled="savingAvatar"
              :title="option.label"
              @click="selectAvatar(option.value)"
            >
              <UserAvatar :avatar="option.value" :size="44" />
            </button>
          </div>
        </div>
      </div>

      <hr class="settings-divider" />

      <!-- Sobre CapyMeal -->
      <div class="settings-item settings-item--static">
        <img src="../assets/icons/capy2.png" alt="Capi" class="settings-item__capi">
        <div>
          <p class="settings-item__label">Sobre CapyMeal</p>
          <p class="settings-item__desc">Un lugar tranquilo para guardar los pequeños momentos alrededor de la comida.</p>
          <div class="settings-item__links">
            <router-link to="/privacidad" class="settings-item__link">Política de privacidad</router-link>
            <router-link to="/terminos" class="settings-item__link">Términos de servicio</router-link>
          </div>
        </div>
      </div>

      <div class="settings-item settings-item--static">
        <span class="settings-item__icon">❤️</span>
        <div>
          <p class="settings-item__label">Versión</p>
          <p class="settings-item__desc">v0.1 — prototipo</p>
        </div>
      </div>

      <hr class="settings-divider" />

      <!-- Cuenta -->
      <div class="settings-item">
        <div class="settings-item__info">
          <UserAvatar :avatar="currentUser?.avatar" :email="currentUser?.email" :size="32" />
          <div>
            <p class="settings-item__label">{{ currentUser?.name }}</p>
            <p class="settings-item__desc">{{ currentUser?.email }}</p>
          </div>
        </div>
      </div>

      <hr class="settings-divider" />

      <button type="button" class="settings-item settings-item--danger" @click="handleLogout">
        <span class="settings-item__icon">🚪</span>
        <p class="settings-item__label">Cerrar sesión</p>
      </button>

      <hr class="settings-divider" />

      <button
        v-if="!confirmingDeleteAccount"
        type="button"
        class="settings-item settings-item--danger"
        @click="confirmingDeleteAccount = true"
      >
        <span class="settings-item__icon">🗑</span>
        <p class="settings-item__label">Eliminar mi cuenta</p>
      </button>

      <div v-else class="settings-delete-account">
        <p class="settings-delete-account__warning">
          Esto borra tu cuenta y todo tu diario para siempre. No se puede deshacer.
        </p>
        <PasswordField
          v-model="deletePassword"
          label="Confirmá tu contraseña"
          autocomplete="current-password"
        />
        <p v-if="deleteError" class="settings-delete-account__error">{{ deleteError }}</p>
        <div class="settings-delete-account__actions">
          <CapyButton variant="danger" :disabled="deletingAccount" @click="handleDeleteAccount">
            {{ deletingAccount ? 'Eliminando...' : 'Sí, eliminar mi cuenta' }}
          </CapyButton>
          <CapyButton variant="ghost" :disabled="deletingAccount" @click="cancelDeleteAccount">Cancelar</CapyButton>
        </div>
      </div>

    </div>
  </MainLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useTheme } from 'vuetify'
import MainLayout from '../layouts/MainLayout.vue'
import UserAvatar  from '../components/base/UserAvatar.vue'
import PasswordField from '../components/base/PasswordField.vue'
import CapyButton  from '../components/base/CapyButton.vue'
import { logout, currentUser, updateAvatar, deleteAccount } from '../stores/authStore'
import { isNetworkError } from '../services/mealEntriesApi'

const router      = useRouter()
const vuetifyTheme = useTheme()
const isDark      = ref(document.documentElement.getAttribute('data-theme') === 'dark')

const avatarOptions = [
  { value: 'capy1', label: 'Capi con mate' },
  { value: 'capy2', label: 'Capi con flor' },
  { value: 'capy3', label: 'Capi' },
]
const savingAvatar  = ref(false)
const avatarError   = ref('')

async function selectAvatar(value) {
  if (savingAvatar.value || currentUser.value?.avatar === value || (!value && !currentUser.value?.avatar)) {
    return
  }

  savingAvatar.value = true
  avatarError.value  = ''

  try {
    await updateAvatar(value)
  } catch {
    avatarError.value = 'No pude guardar el avatar. Intentá nuevamente.'
  } finally {
    savingAvatar.value = false
  }
}

function toggleTheme(value) {
  isDark.value = value
  const theme = isDark.value ? 'dark' : 'light'
  document.documentElement.setAttribute('data-theme', theme)
  localStorage.setItem('capymeal-theme', theme)
  // data-theme/localStorage siguen siendo la fuente de verdad; Vuetify
  // solo se mantiene sincronizado con eso.
  vuetifyTheme.change(isDark.value ? 'capymealDark' : 'capymealLight')
}

async function handleLogout() {
  await logout()
  router.push('/login')
}

const confirmingDeleteAccount = ref(false)
const deletePassword = ref('')
const deletingAccount = ref(false)
const deleteError = ref('')

function cancelDeleteAccount() {
  confirmingDeleteAccount.value = false
  deletePassword.value = ''
  deleteError.value = ''
}

async function handleDeleteAccount() {
  if (!deletePassword.value) {
    deleteError.value = 'Ingresá tu contraseña para confirmar.'
    return
  }

  deletingAccount.value = true
  deleteError.value = ''

  try {
    await deleteAccount(deletePassword.value)
    router.push('/login')
  } catch (error) {
    deleteError.value = isNetworkError(error)
      ? 'No se pudo eliminar: estás sin conexión.'
      : error.message
  } finally {
    deletingAccount.value = false
  }
}
</script>

<style scoped>
.settings-heading {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-xl);
}

.settings-heading__icon {
  width: 44px;
  height: 44px;
  object-fit: contain;
  filter: drop-shadow(0 2px 6px rgba(169,130,116,.2));
}

.settings-heading__title {
  font-family: var(--font-title);
  font-size: 1.6rem;
  font-weight: 700;
  color: var(--color-title);
}

.settings-list {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}

.settings-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  padding: var(--space-lg);
}

.settings-item--static {
  /* Estas filas son icono + texto nomas, sin control a la derecha
     (a diferencia de "Tema"), pero heredaban el mismo
     justify-content: space-between de .settings-item -- con texto
     corto (ej. "Versión") quedaba un hueco grande en el medio en vez
     de agrupar todo a la izquierda. */
  justify-content: flex-start;
  align-items: flex-start;
}

.settings-item__info {
  display: flex;
  align-items: center;
  gap: var(--space-md);
}

.settings-item__icon {
  font-size: 1.3rem;
  flex-shrink: 0;
}

.settings-item--static .settings-item__icon {
  margin-top: 2px;
}

.settings-item__capi {
  width: 28px;
  height: 28px;
  object-fit: contain;
  flex-shrink: 0;
  margin-top: 2px;
}

.settings-item--danger {
  /* Solo tiene icono + texto (sin control a la derecha, a diferencia
     de las otras filas), así que se centran juntos en vez de heredar
     el justify-content: space-between que los separa a los extremos. */
  justify-content: center;
  cursor: pointer;
  color: #B5453C;
  /* Es un <button> real (antes un <div> con @click -- sin foco de
     teclado ni rol semántico). Se resetea el estilo nativo del botón
     para que siga viendo igual que antes; font:inherit ya lo cubre el
     reset global de main.css. */
  width: 100%;
  border: none;
  background: none;
}

.settings-item--danger .settings-item__label {
  color: #B5453C;
}

.settings-item--danger:hover {
  background: rgba(227,150,140,.15);
}

.settings-item__label {
  font-size: .95rem;
  font-weight: 700;
  color: var(--color-title);
  margin-bottom: 2px;
}

.settings-item__desc {
  font-size: .82rem;
  color: var(--color-text);
  opacity: .7;
  line-height: 1.5;
}

.settings-item__links {
  display: flex;
  gap: var(--space-md);
  margin-top: var(--space-xs);
}

.settings-item__link {
  font-size: .82rem;
  font-weight: 700;
  color: var(--color-primary);
}

.settings-delete-account {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  padding: var(--space-lg);
}

.settings-delete-account__warning {
  font-size: .85rem;
  color: #B5453C;
  line-height: 1.5;
}

.settings-delete-account__error {
  font-size: .82rem;
  color: var(--color-danger);
}

.settings-delete-account__actions {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}

.settings-divider {
  border: none;
  border-top: 1px solid var(--color-border);
  margin: 0;
}

.settings-avatar-picker {
  width: 100%;
}

.settings-avatar-picker__error {
  font-size: .8rem;
  color: var(--color-danger);
  margin-top: 2px;
  margin-bottom: var(--space-xs);
}

.settings-avatar-picker__options {
  display: flex;
  gap: var(--space-sm);
  margin-top: var(--space-sm);
}

.settings-avatar-picker__option {
  background: none;
  border: 2px solid transparent;
  border-radius: 50%;
  padding: 2px;
  cursor: pointer;
  line-height: 0;
  transition: border-color .2s ease, opacity .2s ease;
}

.settings-avatar-picker__option:disabled {
  opacity: .5;
  cursor: not-allowed;
}

.settings-avatar-picker__option--active {
  border-color: var(--color-primary);
}
</style>
