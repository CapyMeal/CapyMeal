<template>
  <MainLayout>
    <div class="settings-heading">
      <img src="../assets/icons/ajustes.png" alt="" class="settings-heading__icon">
      <h1 class="settings-heading__title">{{ t('settings.title') }}</h1>
    </div>

    <div class="settings-list">

      <!-- Tema -->
      <div class="settings-item">
        <div class="settings-item__info">
          <span class="settings-item__icon">🎨</span>
          <div>
            <p class="settings-item__label">{{ t('settings.theme') }}</p>
            <p class="settings-item__desc">{{ isDark ? t('settings.themeDark') : t('settings.themeLight') }}</p>
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

      <!-- Idioma -->
      <div class="settings-item">
        <div class="settings-item__info">
          <component :is="isEnglish ? UKFlagIcon : ArgentinaFlagIcon" />
          <div>
            <p class="settings-item__label">{{ t('settings.language') }}</p>
            <p class="settings-item__desc">{{ isEnglish ? t('settings.languageEnglish') : t('settings.languageSpanish') }}</p>
          </div>
        </div>
        <v-switch
          :model-value="isEnglish"
          color="primary"
          hide-details
          density="compact"
          @update:model-value="toggleLocale"
        />
      </div>

      <hr class="settings-divider" />

      <!-- Avatar -->
      <div class="settings-item settings-item--static">
        <div class="settings-avatar-picker">
          <p class="settings-item__label">{{ t('settings.avatar') }}</p>
          <p v-if="avatarError" class="settings-avatar-picker__error">{{ avatarError }}</p>
          <div class="settings-avatar-picker__options">
            <button
              type="button"
              class="settings-avatar-picker__option"
              :class="{ 'settings-avatar-picker__option--active': !currentUser?.avatar }"
              :disabled="savingAvatar"
              :title="t('settings.gravatarTitle')"
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
          <p class="settings-item__label">{{ t('settings.aboutTitle') }}</p>
          <p class="settings-item__desc">{{ t('settings.aboutDesc') }}</p>
          <div class="settings-item__links">
            <router-link to="/privacidad" class="settings-item__link">{{ t('settings.privacyPolicy') }}</router-link>
            <router-link to="/terminos" class="settings-item__link">{{ t('settings.termsOfService') }}</router-link>
            <router-link to="/instalar-app" class="settings-item__link">{{ t('settings.downloadApp') }}</router-link>
          </div>
        </div>
      </div>

      <div class="settings-item settings-item--static">
        <span class="settings-item__icon">❤️</span>
        <div>
          <p class="settings-item__label">{{ t('settings.version') }}</p>
          <p class="settings-item__desc">{{ t('settings.versionValue') }}</p>
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

      <!-- Verificación en dos pasos -->
      <template v-if="!settingUp && !recoveryCodes">
        <div class="settings-item">
          <div class="settings-item__info">
            <span class="settings-item__icon">🔐</span>
            <div>
              <p class="settings-item__label">{{ t('twoFactor.settingsTitle') }}</p>
              <p class="settings-item__desc">
                {{ currentUser?.two_factor_enabled ? t('twoFactor.settingsEnabled') : t('twoFactor.settingsDisabled') }}
                <template v-if="currentUser?.two_factor_enabled">
                  · {{ t('twoFactor.settingsRecoveryCodesRemaining', { count: currentUser?.two_factor_recovery_codes_remaining }) }}
                </template>
              </p>
            </div>
          </div>
          <CapyButton
            v-if="!currentUser?.two_factor_enabled"
            variant="ghost"
            compact
            :disabled="startingSetup"
            @click="startTwoFactorSetup"
          >
            {{ t('twoFactor.enableButton') }}
          </CapyButton>
          <CapyButton v-else variant="ghost" compact @click="confirmingDisableTwoFactor = true">
            {{ t('twoFactor.disableButton') }}
          </CapyButton>
        </div>
        <p v-if="settingUpError" class="settings-avatar-picker__error settings-two-factor-error">{{ settingUpError }}</p>

        <div v-if="confirmingDisableTwoFactor" class="settings-panel">
          <PasswordField
            v-model="disableTwoFactorPassword"
            :label="t('settings.confirmPassword')"
            autocomplete="current-password"
          />
          <p v-if="disableTwoFactorError" class="settings-delete-account__error">{{ disableTwoFactorError }}</p>
          <div class="settings-delete-account__actions">
            <CapyButton variant="danger" :disabled="disablingTwoFactor" @click="handleDisableTwoFactor">
              {{ disablingTwoFactor ? t('twoFactor.disabling') : t('twoFactor.disableConfirmButton') }}
            </CapyButton>
            <CapyButton variant="ghost" :disabled="disablingTwoFactor" @click="cancelDisableTwoFactor">{{ t('twoFactor.cancelButton') }}</CapyButton>
          </div>
        </div>
      </template>

      <!-- Setup de 2FA: QR + código de confirmación -->
      <div v-else-if="settingUp" class="settings-panel">
        <p class="settings-item__label">{{ t('twoFactor.settingUpTitle') }}</p>
        <p class="settings-item__desc">{{ t('twoFactor.settingUpBody') }}</p>
        <img v-if="setupData" :src="setupData.qrCodeSvg" alt="" class="settings-two-factor-qr">
        <p v-if="setupData" class="settings-two-factor-key">{{ setupData.secret }}</p>
        <v-text-field
          v-model="confirmCode"
          :label="t('twoFactor.confirmCodeLabel')"
          placeholder="123456"
          autocomplete="one-time-code"
        />
        <p v-if="confirmError" class="settings-delete-account__error">{{ confirmError }}</p>
        <div class="settings-delete-account__actions">
          <CapyButton :disabled="confirmingCode" @click="confirmTwoFactorSetup">
            {{ confirmingCode ? t('twoFactor.verifying') : t('twoFactor.confirmButton') }}
          </CapyButton>
          <CapyButton variant="ghost" :disabled="confirmingCode" @click="cancelTwoFactorSetup">{{ t('twoFactor.cancelButton') }}</CapyButton>
        </div>
      </div>

      <!-- Códigos de recuperación: se muestran una sola vez -->
      <div v-else class="settings-panel">
        <p class="settings-item__label">{{ t('twoFactor.recoveryCodesTitle') }}</p>
        <p class="settings-delete-account__warning">{{ t('twoFactor.recoveryCodesWarning') }}</p>
        <ul class="settings-two-factor-codes">
          <li v-for="recoveryCode in recoveryCodes" :key="recoveryCode">{{ recoveryCode }}</li>
        </ul>
        <CapyButton @click="recoveryCodes = null">{{ t('twoFactor.recoveryCodesSavedButton') }}</CapyButton>
      </div>

      <hr class="settings-divider" />

      <button type="button" class="settings-item settings-item--danger" @click="handleLogout">
        <span class="settings-item__icon">🚪</span>
        <p class="settings-item__label">{{ t('settings.logout') }}</p>
      </button>

      <hr class="settings-divider" />

      <button
        v-if="!confirmingDeleteAccount"
        type="button"
        class="settings-item settings-item--danger"
        @click="confirmingDeleteAccount = true"
      >
        <span class="settings-item__icon">🗑</span>
        <p class="settings-item__label">{{ t('settings.deleteAccount') }}</p>
      </button>

      <div v-else class="settings-delete-account">
        <p class="settings-delete-account__warning">
          {{ t('settings.deleteAccountWarning') }}
        </p>
        <PasswordField
          v-model="deletePassword"
          :label="t('settings.confirmPassword')"
          autocomplete="current-password"
        />
        <p v-if="deleteError" class="settings-delete-account__error">{{ deleteError }}</p>
        <div class="settings-delete-account__actions">
          <CapyButton variant="danger" :disabled="deletingAccount" @click="handleDeleteAccount">
            {{ deletingAccount ? t('settings.deleting') : t('settings.confirmDelete') }}
          </CapyButton>
          <CapyButton variant="ghost" :disabled="deletingAccount" @click="cancelDeleteAccount">{{ t('settings.cancel') }}</CapyButton>
        </div>
      </div>

    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useTheme } from 'vuetify'
import { useI18n } from 'vue-i18n'
import MainLayout from '../layouts/MainLayout.vue'
import UserAvatar  from '../components/base/UserAvatar.vue'
import PasswordField from '../components/base/PasswordField.vue'
import CapyButton  from '../components/base/CapyButton.vue'
import ArgentinaFlagIcon from '../components/base/ArgentinaFlagIcon.vue'
import UKFlagIcon from '../components/base/UKFlagIcon.vue'
import {
  logout, currentUser, updateAvatar, deleteAccount,
  setupTwoFactor, confirmTwoFactor, disableTwoFactor,
} from '../stores/authStore'
import { isNetworkError } from '../services/mealEntriesApi'

const router      = useRouter()
const vuetifyTheme = useTheme()
const { t, locale } = useI18n()
const isDark      = ref(document.documentElement.getAttribute('data-theme') === 'dark')
const isEnglish   = computed(() => locale.value === 'en')

const avatarOptions = computed(() => [
  { value: 'capy1', label: t('settings.avatarOption1') },
  { value: 'capy2', label: t('settings.avatarOption2') },
  { value: 'capy3', label: t('settings.avatarOption3') },
])
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
    avatarError.value = t('settings.avatarError')
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

// Mismo patrón que toggleTheme: local ref (via locale.value del i18n
// global) + atributo en <html> + localStorage, en ese orden.
function toggleLocale(value) {
  const newLocale = value ? 'en' : 'es'
  locale.value = newLocale
  document.documentElement.setAttribute('lang', newLocale)
  localStorage.setItem('capymeal-locale', newLocale)
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
    deleteError.value = t('settings.confirmPasswordRequired')
    return
  }

  deletingAccount.value = true
  deleteError.value = ''

  try {
    await deleteAccount(deletePassword.value)
    router.push('/login')
  } catch (error) {
    deleteError.value = isNetworkError(error)
      ? t('settings.deleteOfflineError')
      : error.message
  } finally {
    deletingAccount.value = false
  }
}

const startingSetup    = ref(false)
const settingUp        = ref(false)
const setupData        = ref(null)
const settingUpError   = ref('')
const confirmCode      = ref('')
const confirmingCode   = ref(false)
const confirmError     = ref('')
const recoveryCodes    = ref(null)

async function startTwoFactorSetup() {
  startingSetup.value = true
  settingUpError.value = ''

  try {
    setupData.value = await setupTwoFactor()
    settingUp.value = true
  } catch {
    settingUpError.value = t('twoFactor.settingUpError')
  } finally {
    startingSetup.value = false
  }
}

function cancelTwoFactorSetup() {
  settingUp.value   = false
  setupData.value   = null
  confirmCode.value = ''
  confirmError.value = ''
}

async function confirmTwoFactorSetup() {
  confirmingCode.value = true
  confirmError.value   = ''

  try {
    recoveryCodes.value = await confirmTwoFactor(confirmCode.value)
    settingUp.value     = false
    setupData.value     = null
    confirmCode.value   = ''
  } catch (error) {
    confirmError.value = error.message || t('twoFactor.genericError')
  } finally {
    confirmingCode.value = false
  }
}

const confirmingDisableTwoFactor = ref(false)
const disableTwoFactorPassword   = ref('')
const disablingTwoFactor         = ref(false)
const disableTwoFactorError      = ref('')

function cancelDisableTwoFactor() {
  confirmingDisableTwoFactor.value = false
  disableTwoFactorPassword.value   = ''
  disableTwoFactorError.value      = ''
}

async function handleDisableTwoFactor() {
  disablingTwoFactor.value    = true
  disableTwoFactorError.value = ''

  try {
    await disableTwoFactor(disableTwoFactorPassword.value)
    cancelDisableTwoFactor()
  } catch (error) {
    disableTwoFactorError.value = error.message || t('twoFactor.genericError')
  } finally {
    disablingTwoFactor.value = false
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

.settings-delete-account,
.settings-panel {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  padding: var(--space-lg);
}

.settings-two-factor-error {
  padding: 0 var(--space-lg) var(--space-md);
}

.settings-two-factor-qr {
  align-self: center;
  width: 180px;
  height: 180px;
}

.settings-two-factor-key {
  align-self: center;
  font-family: monospace;
  font-size: .85rem;
  letter-spacing: .05em;
  color: var(--color-muted);
  word-break: break-all;
  text-align: center;
}

.settings-two-factor-codes {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: var(--space-md);
  background: var(--color-background);
  border-radius: var(--radius-sm);
  font-family: monospace;
  font-size: .88rem;
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
