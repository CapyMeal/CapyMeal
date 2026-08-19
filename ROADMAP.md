🌱 Fase 1 — Fundaciones 
    ✅ Nombre
    ✅ Identidad
    ✅ Manifiesto
    ✅ Design System
    ✅ Capi
    ✅ Arquitectura

🌸 Fase 2 — Prototipo funcional
    ✅ Frontend Vue
    ✅ Navegación
    ✅ Componentes
    ✅ Responsive

🍽️ Fase 3 — Registro de comidas
    ✅ Crear
    ✅ Editar
    ✅ Eliminar
    ✅ Filtro por fecha

📖 Fase 4 — Diario
    ✅ Historial
    ✅ Notas
    🔜 Búsqueda de texto — junto con paginación server-side: hoy GET /meal-entries
       trae toda la historia del usuario en una sola respuesta y el filtro de
       fecha del Diario es client-side sobre esa lista completa

📄 Fase 5 — Exportación
    ✅ PDF bonito
    ✅ Portada con Capi
    ✅ Fechas seleccionadas

☁️ Fase 6 — Cuenta de usuario
    ✅ Login / registro
    ✅ Recuperación de contraseña
    ✅ Avatar (Gravatar o Capi)
    🔜 Eliminar cuenta — hoy no existe, hace falta antes de abrir la app a más gente
    🔜 Rate limiting en /register y /login — solo /forgot-password y
       /reset-password están protegidos contra abuso hoy
    ⏳ Backup / export de datos crudo — el único export hoy es PDF (para leer,
       no para reimportar)

📱 Fase 7 — App móvil
    🔜 PWA instalable (manifest + service worker, sin offline todavía)
    🔜 Recordatorio diario — el mecanismo más directo para retención ("que
       vuelvan todos los días"); en iOS depende de que la PWA ya esté agregada
       a la pantalla de inicio, y solo desde iOS 16.4
    ⏳ Offline real (cola local + sync) — evaluar después de medir cuánto se
       usa la app sin señal
    ⏳ Android / iOS nativo (Capacitor) — solo si hace falta estar en las stores

🌟 Fase 8 — Crecimiento (agregada 2026-08-19, para cuando la use más gente)
    🔜 Onboarding real en el splash — hoy es un botón "Comenzar" sin ninguna
       guía; una página en blanco intimida más en un diario que en una app
       utilitaria
    🔜 Política de privacidad / términos — se guarda algo personal (diario de
       comidas), vale la pena tenerlo antes de invitar a desconocidos
    ⏳ Fotos en el "recuerdo del día" — el diferenciador más fuerte frente a
       anotar en el celular, pero el salto de infraestructura más grande
       (storage + subida de archivos)