import { createApp } from 'vue'
import App from './App.vue'
import router from './router'

import './styles/main.css'

const savedTheme = localStorage.getItem('capymeal-theme') || 'dark'
document.documentElement.setAttribute('data-theme', savedTheme)

createApp(App)
  .use(router)
  .mount('#app')