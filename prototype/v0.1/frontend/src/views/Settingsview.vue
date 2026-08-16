<template>
  <MainLayout>
    <div class="settings-heading">
      <img src="../assets/icons/ajustes.png" alt="" class="settings-heading__icon">
      <h1 class="settings-heading__title">Ajustes</h1>
    </div>

    <div class="settings-list">

      <!-- Tema -->
      <div class="settings-item">
        <div class="settings-item__info">
          <span class="settings-item__icon">🎨</span>
          <div>
            <p class="settings-item__label">Tema</p>
            <p class="settings-item__desc">{{ isDark ? 'Oscuro' : 'Claro' }}</p>
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

      <!-- Sobre CapyMeal -->
      <div class="settings-item settings-item--static">
        <span class="settings-item__icon">🐹</span>
        <div>
          <p class="settings-item__label">Sobre CapyMeal</p>
          <p class="settings-item__desc">Un lugar tranquilo para guardar los pequeños momentos alrededor de la comida.</p>
        </div>
      </div>

      <div class="settings-item settings-item--static">
        <span class="settings-item__icon">❤️</span>
        <div>
          <p class="settings-item__label">Versión</p>
          <p class="settings-item__desc">v0.1 — prototipo</p>
        </div>
      </div>

      <hr class="settings-divider" />

      <!-- Cuenta -->
      <div class="settings-item">
        <div class="settings-item__info">
          <span class="settings-item__icon">👤</span>
          <div>
            <p class="settings-item__label">{{ currentUser?.name }}</p>
            <p class="settings-item__desc">{{ currentUser?.email }}</p>
          </div>
        </div>
      </div>

      <hr class="settings-divider" />

      <div class="settings-item settings-item--danger" @click="handleLogout">
        <span class="settings-item__icon">🚪</span>
        <p class="settings-item__label">Cerrar sesión</p>
      </div>

    </div>
  </MainLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useTheme } from 'vuetify'
import MainLayout from '../layouts/MainLayout.vue'
import { logout, currentUser } from '../stores/authStore'

const router      = useRouter()
const vuetifyTheme = useTheme()
const isDark      = ref(document.documentElement.getAttribute('data-theme') === 'dark')

function toggleTheme(value) {
  isDark.value = value
  const theme = isDark.value ? 'dark' : 'light'
  document.documentElement.setAttribute('data-theme', theme)
  localStorage.setItem('capymeal-theme', theme)
  // data-theme/localStorage siguen siendo la fuente de verdad; Vuetify
  // solo se mantiene sincronizado con eso.
  vuetifyTheme.change(isDark.value ? 'capymealDark' : 'capymealLight')
}

async function handleLogout() {
  await logout()
  router.push('/login')
}
</script>

<style scoped>
.settings-heading {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-xl);
}

.settings-heading__icon {
  width: 44px;
  height: 44px;
  object-fit: contain;
  filter: drop-shadow(0 2px 6px rgba(169,130,116,.2));
}

.settings-heading__title {
  font-family: var(--font-title);
  font-size: 1.6rem;
  font-weight: 700;
  color: var(--color-title);
}

.settings-list {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}

.settings-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  padding: var(--space-lg);
}

.settings-item--static {
  align-items: flex-start;
}

.settings-item__info {
  display: flex;
  align-items: center;
  gap: var(--space-md);
}

.settings-item__icon {
  font-size: 1.3rem;
  flex-shrink: 0;
}

.settings-item--static .settings-item__icon {
  margin-top: 2px;
}

.settings-item--danger {
  cursor: pointer;
  color: #B5453C;
}

.settings-item--danger .settings-item__label {
  color: #B5453C;
}

.settings-item--danger:hover {
  background: rgba(227,150,140,.15);
}

.settings-item__label {
  font-size: .95rem;
  font-weight: 700;
  color: var(--color-title);
  margin-bottom: 2px;
}

.settings-item__desc {
  font-size: .82rem;
  color: var(--color-text);
  opacity: .7;
  line-height: 1.5;
}

.settings-divider {
  border: none;
  border-top: 1px solid var(--color-border);
  margin: 0;
}
</style>
