<template>
  <button
    v-if="!confirmingDeleteAccount"
    type="button"
    class="settings-item settings-item--action settings-item--danger"
    @click="confirmingDeleteAccount = true"
  >
    <v-icon :icon="`svg:${mdiDeleteOutline}`" class="settings-item__icon" />
    <p class="settings-item__label">{{ t('settings.deleteAccount') }}</p>
  </button>

  <div v-else class="settings-panel">
    <p class="settings-delete-account__warning">
      {{ t('settings.deleteAccountWarning') }}
    </p>
    <!-- Las cuentas creadas con Google o Microsoft no tienen contraseña:
         antes se les pedía igual y no tenían forma de borrarse. -->
    <PasswordField
      v-if="needsPassword"
      v-model="deletePassword"
      :label="t('settings.confirmPassword')"
      autocomplete="current-password"
    />
    <StatusAlert v-if="deleteError" :message="deleteError" />
    <div class="settings-panel__actions">
      <CapyButton variant="danger" :disabled="deletingAccount" @click="handleDeleteAccount">
        {{ deletingAccount ? t('settings.deleting') : t('settings.confirmDelete') }}
      </CapyButton>
      <CapyButton variant="ghost" :disabled="deletingAccount" @click="cancelDeleteAccount">{{ t('settings.cancel') }}</CapyButton>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { mdiDeleteOutline } from '@mdi/js'
import PasswordField from '../base/PasswordField.vue'
import CapyButton from '../base/CapyButton.vue'
import StatusAlert from '../base/StatusAlert.vue'
import { deleteAccount, currentUser } from '../../stores/authStore'
import { isNetworkError } from '../../services/mealEntriesApi'

const router = useRouter()
const { t } = useI18n()

const confirmingDeleteAccount = ref(false)
const deletePassword = ref('')
const deletingAccount = ref(false)
const deleteError = ref('')

// Sin el dato (sesión guardada antes de que la API lo enviara) se pide la
// contraseña, como siempre: el backend la ignora si la cuenta no tiene.
const needsPassword = computed(() => currentUser.value?.has_password !== false)

function cancelDeleteAccount() {
  confirmingDeleteAccount.value = false
  deletePassword.value = ''
  deleteError.value = ''
}

async function handleDeleteAccount() {
  if (needsPassword.value && !deletePassword.value) {
    deleteError.value = t('settings.confirmPasswordRequired')
    return
  }

  deletingAccount.value = true
  deleteError.value = ''

  try {
    await deleteAccount(needsPassword.value ? deletePassword.value : undefined)
    router.push('/login')
  } catch (error) {
    // error.message ya viene redactado: httpClient traduce los errores del
    // servidor y los de validación llegan en el idioma de la usuaria.
    deleteError.value = isNetworkError(error)
      ? t('settings.deleteOfflineError')
      : error.message
  } finally {
    deletingAccount.value = false
  }
}
</script>

<style scoped src="./settings-row.css"></style>

<style scoped>
.settings-delete-account__warning {
  font-size: var(--font-size-label);
  color: var(--color-danger-text);
  line-height: 1.5;
}
</style>
