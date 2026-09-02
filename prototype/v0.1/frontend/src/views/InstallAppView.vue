<template>
  <div class="privacy-page">
    <div class="privacy-card">
      <RouterLink :to="backTo" class="privacy-back">← Volver</RouterLink>

      <div class="privacy-heading">
        <img src="../assets/icons/capy2.png" alt="Capi" class="privacy-heading__icon">
        <h1 class="privacy-heading__title">Descargar CapyMeal para Android</h1>
      </div>
      <p class="privacy-updated">
        Esta es una versión instalable de CapyMeal para tu celular Android, sin pasar por Play
        Store. Es la misma app que ya usás desde el navegador — solo que con ícono propio y sin
        la barra de direcciones.
      </p>

      <CapyButton href="/capymeal.apk" class="install-download-button">
        ⬇️ Descargar el .apk
      </CapyButton>

      <section class="privacy-section">
        <h2>Antes de instalar: un aviso normal</h2>
        <p>
          Como no lo bajaste de Play Store, Android va a mostrarte un aviso del estilo
          <strong>"No se pudo verificar la app"</strong> o <strong>"Instalar apps desconocidas"</strong>.
          Esto le pasa a <strong>cualquier</strong> app que se instala así, no es una señal de que
          algo esté mal — es simplemente que Android no reconoce Play Store como origen. Estos son
          los pasos para seguir adelante:
        </p>
      </section>

      <section class="privacy-section">
        <h2>Paso a paso</h2>
        <ol class="install-steps">
          <li>Tocá el botón de arriba. El archivo se descarga como <code>capymeal.apk</code>.</li>
          <li>
            Abrí el archivo descargado (desde la notificación de descarga, o desde tu carpeta de
            Descargas).
          </li>
          <li>
            Android va a mostrar el aviso de seguridad mencionado arriba, con un botón
            <strong>"Ajustes"</strong> o <strong>"Configuración"</strong>. Tocalo.
          </li>
          <li>
            Vas a ver una opción como <strong>"Permitir de esta fuente"</strong>. Activala.
          </li>
          <li>Volvé para atrás y tocá <strong>"Instalar"</strong> de nuevo.</li>
          <li>Cuando termine, tocá <strong>"Abrir"</strong> — ¡listo!</li>
        </ol>
      </section>

      <section class="privacy-section">
        <h2>¿Y las actualizaciones?</h2>
        <p>
          El contenido de la app (tu diario, tus comidas) siempre está al día solo, porque se
          conecta en vivo al mismo lugar que la versión web. Si en algún momento sacamos una
          versión nueva del instalador en sí, vas a tener que volver a esta página y repetir estos
          pasos con el <code>.apk</code> nuevo.
        </p>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { isAuthenticated } from '../stores/authStore'
import CapyButton from '../components/base/CapyButton.vue'

// Mismo patrón que TermsOfServiceView.vue/PrivacyPolicyView.vue: desde
// Ajustes (logueada) vuelve al Diario, sin sesión vuelve a la portada --
// esta página también se comparte suelta con gente que todavía no se
// registró.
const backTo = computed(() => (isAuthenticated.value ? '/ajustes' : '/'))
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

.install-download-button {
  width: 100%;
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
