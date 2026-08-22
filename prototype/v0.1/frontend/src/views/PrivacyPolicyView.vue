<template>
  <div class="privacy-page">
    <div class="privacy-card">
      <RouterLink :to="backTo" class="privacy-back">← Volver</RouterLink>

      <div class="privacy-heading">
        <img src="../assets/icons/capy2.png" alt="Capi" class="privacy-heading__icon">
        <h1 class="privacy-heading__title">Política de privacidad</h1>
      </div>
      <p class="privacy-updated">Última actualización: 22 de agosto de 2026.</p>

      <section class="privacy-section">
        <h2>¿Quién ofrece este servicio?</h2>
        <p>
          CapyMeal es un diario personal de comidas. Si tenés cualquier duda sobre
          tus datos o esta política, podés escribirnos a
          <a href="mailto:hello@capymeal.app">hello@capymeal.app</a>.
        </p>
      </section>

      <section class="privacy-section">
        <h2>Qué datos guardamos</h2>
        <ul>
          <li>Tu nombre y email, para identificar tu cuenta.</li>
          <li>Tu contraseña, siempre encriptada — nunca la vemos en texto plano.</li>
          <li>El contenido de tu diario: lo que escribís en cada comida y en "Recuerdo del día".</li>
          <li>Tu preferencia de avatar (una ilustración de Capi, o tu foto de Gravatar si tenés una asociada a tu email).</li>
        </ul>
      </section>

      <section class="privacy-section">
        <h2>Para qué los usamos</h2>
        <p>Únicamente para darte el servicio: guardar y mostrarte tu propio diario, permitirte exportarlo en PDF, y enviarte un email si pedís recuperar tu contraseña. No vendemos ni compartimos tus datos con terceros para publicidad.</p>
      </section>

      <section class="privacy-section">
        <h2>Terceros que usamos</h2>
        <ul>
          <li><strong>Gravatar</strong>: si elegís mostrar tu foto de Gravatar como avatar, tu email se usa (de forma encriptada) para buscar esa imagen en gravatar.com.</li>
          <li><strong>Proveedor de email</strong>: usamos un servicio externo de envío de correo solo para mandarte el mail de recuperación de contraseña.</li>
          <li><strong>Hosting</strong>: la app y tu información viven en proveedores de hosting en la nube (servidor, base de datos y sitio web), contratados para poder ofrecerte el servicio.</li>
        </ul>
      </section>

      <section class="privacy-section">
        <h2>Cuánto tiempo guardamos tus datos</h2>
        <p>Mientras tu cuenta exista. Si la eliminás, tu cuenta y todo tu diario se borran de forma permanente e inmediata — no queda ninguna copia.</p>
      </section>

      <section class="privacy-section">
        <h2>Tus derechos: acceder, corregir o borrar</h2>
        <p>
          Podés ver y editar todo tu diario en cualquier momento desde la app.
          Para eliminar tu cuenta y todos tus datos, andá a
          <strong>Ajustes → Eliminar mi cuenta</strong>. Si no podés acceder a tu cuenta,
          también podés pedirnos el borrado escribiendo a
          <a href="mailto:hello@capymeal.app">hello@capymeal.app</a>.
        </p>
      </section>

      <section class="privacy-section">
        <h2>Menores de edad</h2>
        <p>CapyMeal no está dirigida a niños ni niñas. No recolectamos intencionalmente datos de menores de edad.</p>
      </section>

      <section class="privacy-section">
        <h2>Cambios a esta política</h2>
        <p>Si esta política cambia de forma importante, vamos a actualizar la fecha de arriba.</p>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { isAuthenticated } from '../stores/authStore'

// Desde Ajustes (logueada) "Volver" va al Diario; desde el pie de
// Registro (sin sesión) vuelve a esa misma pantalla.
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

.privacy-section ul {
  padding-left: 1.2rem;
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}

.privacy-section a {
  color: var(--color-primary);
  font-weight: 700;
}
</style>
