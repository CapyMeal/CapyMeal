<template>
  <LegalPageLayout
    :back-to="backTo"
    :back-label="t('legal.backLink')"
    :title="t('legal.terms.title')"
    :updated-label="t('legal.terms.updated')"
  >
    <section class="privacy-section">
      <h2>{{ t('legal.terms.whatHeading') }}</h2>
      <i18n-t keypath="legal.terms.whatBody" tag="p">
        <template #email><a href="mailto:hello@capymeal.app">hello@capymeal.app</a></template>
      </i18n-t>
    </section>

    <section class="privacy-section">
      <h2>{{ t('legal.terms.accountHeading') }}</h2>
      <p>{{ t('legal.terms.accountBody') }}</p>
    </section>

    <section class="privacy-section">
      <h2>{{ t('legal.terms.contentHeading') }}</h2>
      <i18n-t keypath="legal.terms.contentBody" tag="p">
        <template #privacyLink><RouterLink to="/privacidad">{{ t('legal.terms.privacyLinkLabel') }}</RouterLink></template>
      </i18n-t>
    </section>

    <section class="privacy-section">
      <h2>{{ t('legal.terms.acceptableUseHeading') }}</h2>
      <p>{{ t('legal.terms.acceptableUseBody') }}</p>
    </section>

    <section class="privacy-section">
      <h2>{{ t('legal.terms.asIsHeading') }}</h2>
      <p>{{ t('legal.terms.asIsBody') }}</p>
    </section>

    <section class="privacy-section">
      <h2>{{ t('legal.terms.closingHeading') }}</h2>
      <i18n-t keypath="legal.terms.closingBody" tag="p">
        <template #settingsPath><strong>{{ t('legal.terms.settingsPath') }}</strong></template>
      </i18n-t>
    </section>

    <section class="privacy-section">
      <h2>{{ t('legal.terms.changesHeading') }}</h2>
      <p>{{ t('legal.terms.changesBody') }}</p>
    </section>
  </LegalPageLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import LegalPageLayout from '../layouts/LegalPageLayout.vue'
import { isAuthenticated } from '../stores/authStore'

const { t } = useI18n()

// Misma lógica que PrivacyPolicyView.vue: desde Ajustes (logueada) vuelve
// al Diario, desde el pie de Registro (sin sesión) vuelve a esa pantalla.
const backTo = computed(() => (isAuthenticated.value ? '/ajustes' : '/registro'))
</script>
