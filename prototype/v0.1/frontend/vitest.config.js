import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import vuetify from 'vite-plugin-vuetify'

// Config propia, separada de vite.config.js: ese archivo ya tiene VitePWA
// configurado para generar un service worker a partir de un build real --
// mezclarlo con el test runner acopla "correr los tests" a la generación
// del manifest de la PWA sin ninguna necesidad real.
export default defineConfig({
  plugins: [vue(), vuetify({ autoImport: true })],
  test: {
    environment: 'jsdom',
    globals: false,
    setupFiles: ['./tests/setup.js'],
    // vite-plugin-vuetify inyecta imports de CSS por cada componente de
    // Vuetify usado en cualquier .vue que se cargue (aunque después Vue
    // Test Utils lo stubee en el render) -- sin esto, Vitest trata a
    // vuetify como una dependencia externa y Node no sabe qué hacer con un
    // import de ".css" directo.
    server: {
      deps: {
        inline: ['vuetify'],
      },
    },
  },
})
