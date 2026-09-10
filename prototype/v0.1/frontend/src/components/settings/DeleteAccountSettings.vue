<template>
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
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PasswordField from '../base/PasswordField.vue'
import CapyButton from '../base/CapyButton.vue'
import { deleteAccount } from '../../stores/authStore'
import { isNetworkError } from '../../services/mealEntriesApi'

const router = useRouter()
const { t } = useI18n()

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
</script>

<style scoped>
.settings-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  padding: var(--space-lg);
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

.settings-item--danger {
  /* Solo tiene icono + texto (sin control a la derecha, a diferencia
     de las otras filas), así que se centran juntos en vez de heredar
     el justify-content: space-between que los separa a los extremos. */
  justify-content: center;
  cursor: pointer;
  color: #B5453C;
  /* Es un <button> real -- se resetea el estilo nativo del botón para
     que siga viendo igual que antes; font:inherit ya lo cubre el reset
     global de main.css. */
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
</style>
