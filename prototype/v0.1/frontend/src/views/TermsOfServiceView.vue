<template>
  <div class="privacy-page">
    <div class="privacy-card">
      <RouterLink :to="backTo" class="privacy-back">← Volver</RouterLink>

      <div class="privacy-heading">
        <img src="../assets/icons/capy2.png" alt="Capi" class="privacy-heading__icon">
        <h1 class="privacy-heading__title">Términos de servicio</h1>
      </div>
      <p class="privacy-updated">Última actualización: 1 de septiembre de 2026.</p>

      <section class="privacy-section">
        <h2>Qué es CapyMeal</h2>
        <p>
          CapyMeal es un diario personal de comidas, pensado para uso personal y no
          comercial. Al crear una cuenta, aceptás estos términos. Si tenés dudas,
          escribinos a <a href="mailto:hello@capymeal.app">hello@capymeal.app</a>.
        </p>
      </section>

      <section class="privacy-section">
        <h2>Tu cuenta</h2>
        <p>Sos responsable de mantener segura tu contraseña y de todo lo que pase en tu cuenta. Si creás tu cuenta con Google, ese proveedor se encarga de verificar tu identidad. CapyMeal no está dirigida a menores de edad.</p>
      </section>

      <section class="privacy-section">
        <h2>Tu contenido</h2>
        <p>Lo que escribís en tu diario es tuyo. Lo usamos únicamente para darte el servicio (guardarlo, mostrártelo, dejarte exportarlo) — nunca lo vendemos ni lo usamos con fines comerciales. Más detalle en la <RouterLink to="/privacidad">Política de privacidad</RouterLink>.</p>
      </section>

      <section class="privacy-section">
        <h2>Uso aceptable</h2>
        <p>Pedimos que uses CapyMeal de buena fe: no intentes vulnerar su seguridad, no uses la cuenta de otra persona sin permiso, y no uses el servicio para fines ilegales.</p>
      </section>

      <section class="privacy-section">
        <h2>El servicio "tal cual está"</h2>
        <p>CapyMeal es un prototipo en desarrollo activo (v0.1). Hacemos lo posible por mantenerlo disponible y tus datos a salvo, pero no podemos garantizar que el servicio esté libre de errores o interrupciones. No somos responsables por pérdidas indirectas derivadas del uso de la app.</p>
      </section>

      <section class="privacy-section">
        <h2>Cerrar tu cuenta</h2>
        <p>Podés borrar tu cuenta cuando quieras desde <strong>Ajustes → Eliminar mi cuenta</strong>; se elimina todo tu diario de forma permanente. También podemos suspender o cerrar cuentas que incumplan estos términos.</p>
      </section>

      <section class="privacy-section">
        <h2>Cambios a estos términos</h2>
        <p>Si estos términos cambian de forma importante, vamos a actualizar la fecha de arriba.</p>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { isAuthenticated } from '../stores/authStore'

// Misma lógica que PrivacyPolicyView.vue: desde Ajustes (logueada) vuelve
// al Diario, desde el pie de Registro (sin sesión) vuelve a esa pantalla.
const backTo = computed(() => (isAuthenticated.value ? '/ajustes' : '/registro'))
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
  font-size: .82rem;
  color: var(--color-muted);
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

.privacy-section a {
  color: var(--color-primary);
  font-weight: 700;
}
</style>
