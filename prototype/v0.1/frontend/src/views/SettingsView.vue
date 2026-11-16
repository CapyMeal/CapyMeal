<template>
  <MainLayout :hide-nav="recoveryCodesPending">
    <PageHeading :icon="settingsIllustration" :title="t('settings.title')" />

    <!-- Tres secciones con título en vez de una sola lista de diez filas
         iguales: lo que se cambia seguido, la cuenta y la información. -->
    <section class="settings-section" aria-labelledby="settings-prefs-title">
      <h2 id="settings-prefs-title" class="settings-section__title">{{ t('settings.sectionPreferences') }}</h2>
      <div class="settings-list">

        <div class="settings-item settings-item--choice">
          <div class="settings-item__info">
            <v-icon :icon="`svg:${mdiPaletteOutline}`" class="settings-item__icon" />
            <p id="settings-theme-label" class="settings-item__label">{{ t('settings.theme') }}</p>
          </div>
          <SegmentedControl
            :model-value="themePreference"
            :options="themeOptions"
            labelledby="settings-theme-label"
            @update:model-value="changeTheme"
          />
        </div>

        <hr class="settings-divider" />

        <div class="settings-item settings-item--choice">
          <div class="settings-item__info">
            <v-icon :icon="`svg:${mdiTranslate}`" class="settings-item__icon" />
            <p id="settings-language-label" class="settings-item__label">{{ t('settings.language') }}</p>
          </div>
          <SegmentedControl
            :model-value="locale"
            :options="languageOptions"
            labelledby="settings-language-label"
            @update:model-value="changeLocale"
          />
        </div>

        <hr class="settings-divider" />

        <div class="settings-item settings-item--static">
          <div class="settings-avatar-picker">
            <p id="settings-avatar-label" class="settings-item__label">{{ t('settings.avatar') }}</p>
            <StatusAlert v-if="avatarError" :message="avatarError" class="settings-avatar-picker__error" />
            <div class="settings-avatar-picker__options" role="group" aria-labelledby="settings-avatar-label">
              <button
                v-for="option in avatarOptions"
                :key="option.value ?? 'gravatar'"
                type="button"
                class="settings-avatar-picker__option"
                :class="{ 'settings-avatar-picker__option--active': isCurrentAvatar(option.value) }"
                :aria-pressed="isCurrentAvatar(option.value)"
                :aria-label="option.label"
                :title="option.label"
                :disabled="savingAvatar"
                @click="selectAvatar(option.value)"
              >
                <UserAvatar :avatar="option.value" :email="currentUser?.email" :size="44" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="settings-section" aria-labelledby="settings-account-title">
      <h2 id="settings-account-title" class="settings-section__title">{{ t('settings.sectionAccount') }}</h2>
      <div class="settings-list">
        <div class="settings-item settings-item--static">
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

        <!-- Cerrar sesión no borra nada: va en tono neutro. El rojo queda
             solo para "Eliminar mi cuenta", que no tiene vuelta atrás. -->
        <button type="button" class="settings-item settings-item--action" @click="handleLogout">
          <v-icon :icon="`svg:${mdiLogout}`" class="settings-item__icon" />
          <p class="settings-item__label">{{ t('settings.logout') }}</p>
        </button>

        <hr class="settings-divider" />

        <DeleteAccountSettings />
      </div>
    </section>

    <section class="settings-section" aria-labelledby="settings-about-title">
      <h2 id="settings-about-title" class="settings-section__title">{{ t('settings.aboutTitle') }}</h2>
      <div class="settings-list">
        <div class="settings-item settings-item--static">
          <img src="../assets/icons/capy2.png" alt="" class="settings-item__capi">
          <div>
            <p class="settings-item__desc">{{ t('settings.aboutDesc') }}</p>
            <div class="settings-item__links">
              <router-link to="/privacidad" class="settings-item__link">{{ t('settings.privacyPolicy') }}</router-link>
              <router-link to="/terminos" class="settings-item__link">{{ t('settings.termsOfService') }}</router-link>
              <router-link to="/instalar-app" class="settings-item__link">{{ t('settings.downloadApp') }}</router-link>
            </div>
          </div>
        </div>

        <hr class="settings-divider" />

        <div class="settings-item settings-item--static">
          <v-icon :icon="`svg:${mdiInformationOutline}`" class="settings-item__icon" />
          <div>
            <p class="settings-item__label">{{ t('settings.version') }}</p>
            <p class="settings-item__desc">{{ t('settings.versionValue') }}</p>
          </div>
        </div>
      </div>
    </section>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useTheme } from 'vuetify'
import { useI18n } from 'vue-i18n'
import { mdiInformationOutline, mdiLogout, mdiPaletteOutline, mdiTranslate } from '@mdi/js'
import MainLayout from '../layouts/MainLayout.vue'
import PageHeading from '../components/base/PageHeading.vue'
import StatusAlert from '../components/base/StatusAlert.vue'
import settingsIllustration from '../assets/icons/ajustes.png'
import UserAvatar  from '../components/base/UserAvatar.vue'
import SegmentedControl from '../components/base/SegmentedControl.vue'
import TwoFactorSettings from '../components/settings/TwoFactorSettings.vue'
import DeleteAccountSettings from '../components/settings/DeleteAccountSettings.vue'
import { logout, currentUser, updateAvatar } from '../stores/authStore'
import { switchLocale } from '../plugins/i18n'
import { getThemePreference, setThemePreference } from '../utils/theme'

const router      = useRouter()
const vuetifyTheme = useTheme()
const { t, locale } = useI18n()
const themePreference = ref(getThemePreference())

const themeOptions = computed(() => [
  { value: 'light',  label: t('settings.themeLight') },
  { value: 'dark',   label: t('settings.themeDark') },
  { value: 'system', label: t('settings.themeSystem') },
])

// Cada idioma en su propio idioma: quien no entiende el actual tiene que
// poder reconocer el suyo.
const languageOptions = [
  { value: 'es', label: 'Español' },
  { value: 'en', label: 'English' },
]

// Los códigos de recuperación de 2FA se muestran una sola vez -- mientras
// TwoFactorSettings los tiene en pantalla, la barra de navegación de
// MainLayout se esconde para que no sea tan fácil perderlos por accidente
// (ver también las guardas de ruta/cierre de pestaña dentro del propio
// TwoFactorSettings).
const recoveryCodesPending = ref(false)

const avatarOptions = computed(() => [
  { value: null,    label: t('settings.gravatarTitle') },
  { value: 'capy1', label: t('settings.avatarOption1') },
  { value: 'capy2', label: t('settings.avatarOption2') },
  { value: 'capy3', label: t('settings.avatarOption3') },
])
const savingAvatar  = ref(false)
const avatarError   = ref('')

// null es Gravatar (sin ilustración elegida).
function isCurrentAvatar(value) {
  return (currentUser.value?.avatar ?? null) === value
}

async function selectAvatar(value) {
  if (savingAvatar.value || isCurrentAvatar(value)) return

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

function changeTheme(preference) {
  themePreference.value = preference
  setThemePreference(preference, vuetifyTheme)
}

// switchLocale (a diferencia de asignar locale.value directo) primero pide
// el catálogo de ese idioma si todavía no se cargó -- ver plugins/i18n.js.
async function changeLocale(newLocale) {
  await switchLocale(newLocale)
  document.documentElement.setAttribute('lang', newLocale)
  try { localStorage.setItem('capymeal-locale', newLocale) } catch { /* sin almacenamiento */ }
}

async function handleLogout() {
  await logout()
  router.push('/login')
}
</script>

<style scoped src="../components/settings/settings-row.css"></style>

<style scoped>
.settings-section + .settings-section {
  margin-top: var(--space-xl);
}

.settings-section__title {
  font-family: var(--font-title);
  font-size: var(--font-size-title-sm);
  font-weight: 600;
  color: var(--color-title);
  margin: 0 0 var(--space-sm) var(--space-xs);
}

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
  font-size: var(--font-size-label);
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
