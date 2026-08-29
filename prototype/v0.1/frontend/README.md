# CapyMeal — Frontend

PWA de Vue 3 + Vuetify para CapyMeal, un diario personal de comidas (explícitamente no una app de fitness/calorías).

Para levantar el proyecto completo (backend + frontend + base de datos) con Docker, ver la
[guía de desarrollo](../README.md) en la raíz de `prototype/v0.1/` — ese es el README mantenido
con los pasos reales de instalación.

## Comandos específicos de este paquete

```bash
npm run dev      # servidor de desarrollo (Vite)
npm run build    # build de producción
npm run lint     # ESLint (solo reporta)
npm run format   # Prettier (aplica los arreglos)
npm test         # Vitest
```

## Stack

- Vue 3 (`<script setup>`) + Vue Router + Vuetify 4
- Vite + `vite-plugin-pwa` (instalable, con caché offline del diario ya guardado)
- Vitest + Vue Test Utils
- Sentry (monitoreo de errores)

## Deploy

Vercel, build estático de este directorio. Auto-deploy desde la rama `dev` (no `main`). Variables
de entorno (`VITE_API_URL`, `VITE_SENTRY_DSN`) configuradas en el dashboard de Vercel.
