// Dónde está el backend. Antes esta constante estaba copiada en cuatro
// archivos (authStore, mealEntriesApi, LoginView, RegisterView).
//
// Hay dos usos distintos y por eso dos constantes:
//
// - BACKEND_URL: la dirección real del backend (Render). Se usa para
//   navegaciones de página completa, como el ingreso con Google o
//   Microsoft: el navegador va al backend y el backend lo devuelve acá.
//
// - API_BASE_URL: la base de los fetch de la app. En producción es vacía:
//   las llamadas van a /api del mismo sitio (capy-meal.vercel.app) y
//   vercel.json las reenvía al backend. Así la cookie de sesión es del
//   propio sitio y no "de un tercero". Con el backend en otro dominio,
//   los navegadores que bloquean cookies de terceros (Samsung Internet,
//   Brave, Safari en iPhone, Chrome con esa opción) nunca guardaban la
//   sesión y el ingreso fallaba con "No pude validar tu sesión".
//   VITE_API_SAME_ORIGIN se activa en .env.production.
export const BACKEND_URL = import.meta.env.VITE_API_URL || 'http://localhost:8080'

export const API_BASE_URL = import.meta.env.VITE_API_SAME_ORIGIN === 'true' ? '' : BACKEND_URL
