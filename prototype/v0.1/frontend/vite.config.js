import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vuetify from 'vite-plugin-vuetify'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    vue(),
    vuetify({ autoImport: true }),
    VitePWA({
      registerType: 'autoUpdate',
      manifest: {
        name: 'CapyMeal',
        short_name: 'CapyMeal',
        description: 'Tu diario personal de comidas, con Capi el carpincho.',
        lang: 'es',
        theme_color: '#96684A',
        background_color: '#F2EAE0',
        display: 'standalone',
        start_url: '/',
        scope: '/',
        icons: [
          { src: '/icon-192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
          { src: '/icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'any' },
          { src: '/icon-512-maskable.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
      },
      workbox: {
        // Por default vite-plugin-pwa solo precachea js/css/html — sumamos
        // imágenes y las fuentes realmente usadas (mdi carga woff2 primero
        // y el navegador nunca pide ttf/eot/svg, así que se excluyen para
        // no duplicar ~2.6MB de fuente que nadie va a usar).
        globPatterns: ['**/*.{js,css,html,png,svg,ico,webp,woff,woff2}'],
        // Sin esto, el fallback de navegación de abajo (para que rutas de
        // Vue Router como /hoy sigan andando sin conexión) también
        // interceptaba la descarga del .apk: al ser un <a href> real, el
        // navegador la trata como una navegación de nivel superior, el
        // service worker la agarraba y servía index.html en vez del
        // archivo, y la app terminaba mostrando su propio 404 en vez de
        // bajar el instalador.
        navigateFallbackDenylist: [/^\/capymeal\.apk$/],
        // Los GET a /api/meal-entries se cachean para que el diario siga
        // visible sin señal; POST/PUT/DELETE quedan fuera a propósito, van
        // directo a la red y fallan "al desnudo" (ver useOnlineStatus.js).
        runtimeCaching: [
          {
            urlPattern: ({ url, request }) =>
              request.method === 'GET' && url.pathname.startsWith('/api/meal-entries'),
            handler: 'NetworkFirst',
            options: {
              cacheName: 'meal-entries-cache',
              networkTimeoutSeconds: 4,
              expiration: {
                maxEntries: 50,
                maxAgeSeconds: 60 * 60 * 24 * 7,
              },
            },
          },
        ],
      },
    }),
  ],
  server: {
    host: '0.0.0.0',
    port: 5173,
    watch: {
      usePolling: true,
      interval: 300,
    },
  },
})
