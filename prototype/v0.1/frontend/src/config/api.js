// Dónde está el backend. Antes esta constante estaba copiada en cuatro
// archivos (authStore, mealEntriesApi, LoginView, RegisterView).
//
// En producción, API_BASE_URL es vacía: todo va a /api del mismo sitio
// (capy-meal.vercel.app) y vercel.json lo reenvía al backend de Render.
// Incluye el ingreso con Google y Microsoft, que vuelve por ahí. Así las
// cookies de sesión son del propio sitio y no "de un tercero". Con el
// backend en otro dominio, los navegadores que bloquean cookies de
// terceros (Samsung Internet, Brave, Safari en iPhone, Chrome con esa
// opción) nunca guardaban la sesión y el ingreso fallaba.
// VITE_API_SAME_ORIGIN se activa en .env.production.
//
// En local y en los tests, la app le habla directo a VITE_API_URL.
const BACKEND_URL = import.meta.env.VITE_API_URL || 'http://localhost:8080'

export const API_BASE_URL = import.meta.env.VITE_API_SAME_ORIGIN === 'true' ? '' : BACKEND_URL
