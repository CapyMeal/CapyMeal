<template>
  <div class="privacy-page">
    <div class="privacy-card">
      <RouterLink :to="backTo" class="privacy-back">{{ t('installApp.backLink') }}</RouterLink>

      <div class="privacy-heading">
        <img src="../assets/icons/capy2.png" alt="Capi" class="privacy-heading__icon">
        <h1 class="privacy-heading__title">{{ t('installApp.title') }}</h1>
      </div>
      <p class="privacy-updated">{{ t('installApp.intro') }}</p>

      <div class="install-download">
        <CapyButton
          :loading="downloading"
          :disabled="downloading"
          @click="downloadApk"
        >
          {{ downloading ? t('installApp.downloading') : t('installApp.downloadButton') }}
        </CapyButton>
        <v-alert v-if="downloadError" type="error" variant="tonal" density="compact">
          {{ downloadError }}
        </v-alert>
      </div>

      <section class="privacy-section">
        <h2>{{ t('installApp.warningsHeading') }}</h2>
        <i18n-t keypath="installApp.warningsBody" tag="p">
          <template #emphasis><strong>{{ t('installApp.warningsEmphasis') }}</strong></template>
        </i18n-t>
      </section>

      <section class="privacy-section">
        <h2>{{ t('installApp.stepsHeading') }}</h2>
        <ol class="install-steps">
          <i18n-t keypath="installApp.step1" tag="li">
            <template #filename><code>capymeal.apk</code></template>
          </i18n-t>
          <li>{{ t('installApp.step2') }}</li>
          <i18n-t keypath="installApp.step3" tag="li">
            <template #warning><strong>&quot;{{ t('installApp.step3Warning') }}&quot;</strong></template>
            <template #settings><strong>&quot;{{ t('installApp.step3Settings') }}&quot;</strong></template>
            <template #trustSource><strong>&quot;{{ t('installApp.step3TrustSource') }}&quot;</strong></template>
          </i18n-t>
          <i18n-t keypath="installApp.step4" tag="li">
            <template #install><strong>&quot;{{ t('installApp.step4Install') }}&quot;</strong></template>
          </i18n-t>
          <i18n-t keypath="installApp.step5" tag="li">
            <template #playProtect><strong>{{ t('installApp.step5PlayProtect') }}</strong></template>
            <template #blockedMsg><strong>&quot;{{ t('installApp.step5BlockedMsg') }}&quot;</strong></template>
            <template #moreDetails><strong>&quot;{{ t('installApp.step5MoreDetails') }}&quot;</strong></template>
            <template #installAnyway><strong>&quot;{{ t('installApp.step5InstallAnyway') }}&quot;</strong></template>
          </i18n-t>
          <i18n-t keypath="installApp.step6" tag="li">
            <template #open><strong>&quot;{{ t('installApp.step6Open') }}&quot;</strong></template>
          </i18n-t>
        </ol>
      </section>

      <section class="privacy-section">
        <h2>{{ t('installApp.updatesHeading') }}</h2>
        <i18n-t keypath="installApp.updatesBody" tag="p">
          <template #filename><code>.apk</code></template>
        </i18n-t>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { isAuthenticated } from '../stores/authStore'
import CapyButton from '../components/base/CapyButton.vue'

const { t } = useI18n()

// Mismo patrón que TermsOfServiceView.vue/PrivacyPolicyView.vue: desde
// Ajustes (logueada) vuelve al Diario, sin sesión vuelve a la portada,
// esta página también se comparte suelta con gente que todavía no se
// registró.
const backTo = computed(() => (isAuthenticated.value ? '/ajustes' : '/'))

const downloading = ref(false)
const downloadError = ref('')

// Un <a href> plano no da ninguna señal visual mientras baja: en una
// conexión lenta parece que el botón no hizo nada. Bajamos el archivo
// nosotros con fetch para mostrar el spinner mientras tanto, y recién
// disparamos el guardado real cuando el .apk ya está completo en memoria.
async function downloadApk() {
  downloading.value = true
  downloadError.value = ''

  try {
    const response = await fetch('/capymeal.apk')
    if (!response.ok) {
      throw new Error(`El servidor respondió ${response.status}`)
    }
    const blob = await response.blob()
    const blobUrl = URL.createObjectURL(blob)

    const link = document.createElement('a')
    link.href = blobUrl
    link.download = 'capymeal.apk'
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(blobUrl)
  } catch {
    downloadError.value = t('installApp.downloadError')
  } finally {
    downloading.value = false
  }
}
</script>

<style scoped>
.privacy-page {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  padding: var(--space-xl) var(--space-md);
  background: var(--color-background);
}

.privacy-card {
  width: 100%;
  max-width: 600px;
}

.privacy-back {
  display: inline-block;
  margin-bottom: var(--space-lg);
  color: var(--color-primary);
  font-weight: 700;
  font-size: .9rem;
}

.privacy-heading {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-xs);
}

.privacy-heading__icon {
  width: 40px;
  height: 40px;
  object-fit: contain;
}

.privacy-heading__title {
  font-family: var(--font-title);
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-title);
}

.privacy-updated {
  font-size: .92rem;
  color: var(--color-text);
  line-height: 1.6;
  margin-bottom: var(--space-lg);
}

.install-download {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  margin-bottom: var(--space-xl);
}

.privacy-section {
  margin-bottom: var(--space-xl);
}

.privacy-section h2 {
  font-family: var(--font-title);
  font-size: 1.05rem;
  color: var(--color-title);
  margin-bottom: var(--space-sm);
}

.privacy-section p,
.privacy-section li {
  font-size: .92rem;
  color: var(--color-text);
  line-height: 1.6;
}

.install-steps {
  padding-left: 1.2em;
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.privacy-section code {
  background: var(--color-surface);
  border-radius: var(--radius-sm);
  padding: 0 .3em;
  font-size: .88em;
}
</style>
