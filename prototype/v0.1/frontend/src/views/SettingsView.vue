<template>
  <MainLayout :hide-nav="recoveryCodesPending">
    <PageHeading :icon="settingsIllustration" :title="t('settings.title')" />

    <div class="settings-list">

      <!-- Tema -->
      <div class="settings-item">
        <div class="settings-item__info">
          <v-icon :icon="`svg:${mdiPaletteOutline}`" class="settings-item__icon" />
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
          <StatusAlert v-if="avatarError" :message="avatarError" class="settings-avatar-picker__error" />
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
        <v-icon :icon="`svg:${mdiInformationOutline}`" class="settings-item__icon" />
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

      <TwoFactorSettings @update:recovery-codes-pending="recoveryCodesPending = $event" />

      <hr class="settings-divider" />

      <button type="button" class="settings-item settings-item--danger" @click="handleLogout">
        <v-icon :icon="`svg:${mdiLogout}`" class="settings-item__icon" />
        <p class="settings-item__label">{{ t('settings.logout') }}</p>
      </button>

      <hr class="settings-divider" />

      <DeleteAccountSettings />

    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useTheme } from 'vuetify'
import { useI18n } from 'vue-i18n'
import { mdiInformationOutline, mdiLogout, mdiPaletteOutline } from '@mdi/js'
import MainLayout from '../layouts/MainLayout.vue'
import PageHeading from '../components/base/PageHeading.vue'
import StatusAlert from '../components/base/StatusAlert.vue'
import settingsIllustration from '../assets/icons/ajustes.png'
import UserAvatar  from '../components/base/UserAvatar.vue'
import ArgentinaFlagIcon from '../components/base/ArgentinaFlagIcon.vue'
import UKFlagIcon from '../components/base/UKFlagIcon.vue'
import TwoFactorSettings from '../components/settings/TwoFactorSettings.vue'
import DeleteAccountSettings from '../components/settings/DeleteAccountSettings.vue'
import { logout, currentUser, updateAvatar } from '../stores/authStore'
import { switchLocale } from '../plugins/i18n'

const router      = useRouter()
const vuetifyTheme = useTheme()
const { t, locale } = useI18n()
const isDark      = ref(document.documentElement.getAttribute('data-theme') === 'dark')
const isEnglish   = computed(() => locale.value === 'en')

// Los códigos de recuperación de 2FA se muestran una sola vez -- mientras
// TwoFactorSettings los tiene en pantalla, la barra de navegación de
// MainLayout se esconde para que no sea tan fácil perderlos por accidente
// (ver también las guardas de ruta/cierre de pestaña dentro del propio
// TwoFactorSettings).
const recoveryCodesPending = ref(false)

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
// global) + atributo en <html> + localStorage, en ese orden. switchLocale
// (a diferencia de asignar locale.value directo) primero pide el catálogo
// de ese idioma si todavía no se cargó -- ver plugins/i18n.js.
async function toggleLocale(value) {
  const newLocale = value ? 'en' : 'es'
  await switchLocale(newLocale)
  document.documentElement.setAttribute('lang', newLocale)
  localStorage.setItem('capymeal-locale', newLocale)
}

async function handleLogout() {
  await logout()
  router.push('/login')
}
</script>

<style scoped src="../components/settings/settings-row.css"></style>

<style scoped>
.settings-list {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}

.settings-item__capi {
  width: 28px;
  height: 28px;
  object-fit: contain;
  flex-shrink: 0;
  margin-top: 2px;
}

/* Cada link entero en su renglón si no entra: antes se partían en tres
   columnas angostas de dos o tres renglones cada una. */
.settings-item__links {
  display: flex;
  flex-wrap: wrap;
  column-gap: var(--space-md);
  row-gap: var(--space-xs);
  margin-top: var(--space-xs);
}

.settings-item__link {
  font-size: .82rem;
  font-weight: 700;
  color: var(--color-primary-text);
  white-space: nowrap;
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
  margin: var(--space-xs) 0;
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
