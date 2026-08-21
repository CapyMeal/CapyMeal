import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import { getInitialTheme } from './utils/theme'

import './styles/main.css'
import './styles/vuetify-overrides.css'

document.documentElement.setAttribute('data-theme', getInitialTheme())

createApp(App)
  .use(router)
  .use(vuetify)
  .mount('#app')