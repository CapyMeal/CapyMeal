<template>
  <LegalPageLayout
    :back-to="backTo"
    :back-label="t('legal.backLink')"
    :title="t('legal.privacy.title')"
    :updated-label="t('legal.privacy.updated')"
  >
    <section class="privacy-section">
      <h2>{{ t('legal.privacy.whoHeading') }}</h2>
      <i18n-t keypath="legal.privacy.whoBody" tag="p">
        <template #email><a href="mailto:hello@capymeal.app">hello@capymeal.app</a></template>
      </i18n-t>
    </section>

    <section class="privacy-section">
      <h2>{{ t('legal.privacy.dataHeading') }}</h2>
      <ul>
        <li>{{ t('legal.privacy.dataItem1') }}</li>
        <li>{{ t('legal.privacy.dataItem2') }}</li>
        <li>{{ t('legal.privacy.dataItem3') }}</li>
        <li>{{ t('legal.privacy.dataItem4') }}</li>
      </ul>
    </section>

    <section class="privacy-section">
      <h2>{{ t('legal.privacy.useHeading') }}</h2>
      <p>{{ t('legal.privacy.useBody') }}</p>
    </section>

    <section class="privacy-section">
      <h2>{{ t('legal.privacy.thirdPartiesHeading') }}</h2>
      <ul>
        <li><strong>{{ t('legal.privacy.thirdPartyGravatarLabel') }}</strong>: {{ t('legal.privacy.thirdPartyGravatar') }}</li>
        <li><strong>{{ t('legal.privacy.thirdPartyEmailLabel') }}</strong>: {{ t('legal.privacy.thirdPartyEmail') }}</li>
        <li><strong>{{ t('legal.privacy.thirdPartyHostingLabel') }}</strong>: {{ t('legal.privacy.thirdPartyHosting') }}</li>
      </ul>
    </section>

    <section class="privacy-section">
      <h2>{{ t('legal.privacy.retentionHeading') }}</h2>
      <p>{{ t('legal.privacy.retentionBody') }}</p>
    </section>

    <section class="privacy-section">
      <h2>{{ t('legal.privacy.rightsHeading') }}</h2>
      <i18n-t keypath="legal.privacy.rightsBody" tag="p">
        <template #settingsPath><strong>{{ t('legal.privacy.settingsPath') }}</strong></template>
        <template #email><a href="mailto:hello@capymeal.app">hello@capymeal.app</a></template>
      </i18n-t>
    </section>

    <section class="privacy-section">
      <h2>{{ t('legal.privacy.minorsHeading') }}</h2>
      <p>{{ t('legal.privacy.minorsBody') }}</p>
    </section>

    <section class="privacy-section">
      <h2>{{ t('legal.privacy.changesHeading') }}</h2>
      <p>{{ t('legal.privacy.changesBody') }}</p>
    </section>
  </LegalPageLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import LegalPageLayout from '../layouts/LegalPageLayout.vue'
import { isAuthenticated } from '../stores/authStore'

const { t } = useI18n()

// Desde Ajustes (logueada) "Volver" va al Diario; desde el pie de
// Registro (sin sesión) vuelve a esa misma pantalla.
const backTo = computed(() => (isAuthenticated.value ? '/ajustes' : '/registro'))
</script>
