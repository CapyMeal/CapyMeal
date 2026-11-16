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
- vue-i18n (español / inglés, con toggle manual además de la detección automática)
- Vitest + Vue Test Utils
- Sentry (monitoreo de errores)
- Vercel Analytics (uso agregado, sin cookies de terceros)

## Deploy

Vercel, build estático de este directorio. Auto-deploy desde la rama `dev` (no `main`). Variables
de entorno (`VITE_API_URL`, `VITE_SENTRY_DSN`) configuradas en el dashboard de Vercel.

En producción la app no llama al backend de Render directamente: pide `/api/...` al mismo sitio
y `vercel.json` lo reenvía a `https://capymeal.onrender.com/api/...`. Así la cookie de sesión es
del propio sitio y no "de un tercero", y el ingreso funciona en navegadores que bloquean cookies
de terceros (Samsung Internet, Brave, Safari en iPhone). Lo activa `VITE_API_SAME_ORIGIN=true`
en `.env.production`; el ingreso con Google y Microsoft sigue yendo directo al backend porque es
una navegación de página completa. Ver `src/config/api.js`.

Para probar un build local contra otro backend (por ejemplo, una API falsa), compilar con
`VITE_API_SAME_ORIGIN=false VITE_API_URL=http://localhost:8787`.
