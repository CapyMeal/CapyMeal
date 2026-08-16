import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'

import './styles/main.css'
import './styles/vuetify-overrides.css'

const savedTheme = localStorage.getItem('capymeal-theme') || 'dark'
document.documentElement.setAttribute('data-theme', savedTheme)

createApp(App)
  .use(router)
  .use(vuetify)
  .mount('#app')