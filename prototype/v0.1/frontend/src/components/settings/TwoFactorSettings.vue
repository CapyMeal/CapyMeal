<template>
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
    <p v-if="settingUpError" class="settings-two-factor-inline-error">{{ settingUpError }}</p>

    <div v-if="confirmingDisableTwoFactor" class="settings-panel">
      <PasswordField
        v-model="disableTwoFactorPassword"
        :label="t('settings.confirmPassword')"
        autocomplete="current-password"
      />
      <p v-if="disableTwoFactorError" class="settings-panel__error">{{ disableTwoFactorError }}</p>
      <div class="settings-panel__actions">
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
    <p v-if="confirmError" class="settings-panel__error">{{ confirmError }}</p>
    <div class="settings-panel__actions">
      <CapyButton :disabled="confirmingCode" @click="confirmTwoFactorSetup">
        {{ confirmingCode ? t('twoFactor.verifying') : t('twoFactor.confirmButton') }}
      </CapyButton>
      <CapyButton variant="ghost" :disabled="confirmingCode" @click="cancelTwoFactorSetup">{{ t('twoFactor.cancelButton') }}</CapyButton>
    </div>
  </div>

  <!-- Códigos de recuperación: se muestran una sola vez -->
  <div v-else class="settings-panel">
    <p class="settings-item__label">{{ t('twoFactor.recoveryCodesTitle') }}</p>
    <p class="settings-panel__warning">{{ t('twoFactor.recoveryCodesWarning') }}</p>
    <ul class="settings-two-factor-codes">
      <li v-for="recoveryCode in recoveryCodes" :key="recoveryCode">{{ recoveryCode }}</li>
    </ul>
    <CapyButton @click="recoveryCodes = null">{{ t('twoFactor.recoveryCodesSavedButton') }}</CapyButton>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { onBeforeRouteLeave } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PasswordField from '../base/PasswordField.vue'
import CapyButton from '../base/CapyButton.vue'
import { currentUser, setupTwoFactor, confirmTwoFactor, disableTwoFactor } from '../../stores/authStore'

// v-model:recoveryCodesPending -- SettingsView necesita saber cuándo los
// códigos de recuperación están en pantalla (todavía sin confirmar "ya los
// guardé") para esconder la barra de navegación de MainLayout mientras
// tanto, ver SettingsView.vue.
const emit = defineEmits(['update:recoveryCodesPending'])

const { t } = useI18n()

const startingSetup  = ref(false)
const settingUp      = ref(false)
const setupData      = ref(null)
const settingUpError = ref('')
const confirmCode    = ref('')
const confirmingCode = ref(false)
const confirmError   = ref('')
const recoveryCodes  = ref(null)

watch(recoveryCodes, (value) => emit('update:recoveryCodesPending', !!value))

// Los códigos se muestran una sola vez -- esto cubre las formas de irse
// sin querer (atrás del navegador) mientras siguen en pantalla sin
// haberlos guardado.
onBeforeRouteLeave(() => {
  if (recoveryCodes.value && !window.confirm(t('twoFactor.recoveryCodesLeaveConfirm'))) {
    return false
  }
})

// Mismo motivo que la guarda de arriba, pero para cerrar la pestaña o
// recargar -- onBeforeRouteLeave no cubre esos casos.
function warnBeforeClosingTab(event) {
  if (recoveryCodes.value) {
    event.preventDefault()
  }
}

onMounted(() => window.addEventListener('beforeunload', warnBeforeClosingTab))
onUnmounted(() => window.removeEventListener('beforeunload', warnBeforeClosingTab))

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
.settings-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  padding: var(--space-lg);
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

.settings-panel {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  padding: var(--space-lg);
}

.settings-two-factor-inline-error {
  font-size: .8rem;
  color: var(--color-danger);
  margin-top: 2px;
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

.settings-panel__warning {
  font-size: .85rem;
  color: #B5453C;
  line-height: 1.5;
}

.settings-panel__error {
  font-size: .82rem;
  color: var(--color-danger);
}

.settings-panel__actions {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}
</style>
