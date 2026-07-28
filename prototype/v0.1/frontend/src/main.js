import { createApp } from 'vue'
import App from './App.vue'
import router from './router'

import './styles/main.css'

// Tema por defecto
document.documentElement.setAttribute('data-theme', 'dark')

createApp(App)
  .use(router)
  .mount('#app')