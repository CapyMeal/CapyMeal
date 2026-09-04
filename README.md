<div align="center">

# CapyMeal

### 🍂 Tu diario de comidas con la calma de un carpincho.

</div>

CapyMeal es una aplicación web para registrar las comidas diarias de forma simple, agradable y visualmente acogedora.

No busca controlar calorías ni imponer objetivos nutricionales. Su propósito es ayudarte a construir el hábito de registrar tus comidas y conservar un diario personal de tu alimentación.

🔗 **App en vivo:** [capy-meal.vercel.app](https://capy-meal.vercel.app)


## Características

- ✅ Registro diario de comidas
- ✅ Diario / historial con filtro por fechas
- ✅ Exportación a PDF
- ✅ Cuenta de usuario con recuperación de contraseña por email
- ✅ Identidad visual cálida basada en carpinchos, con tema claro y oscuro
- ✅ Diseño Material Design 3, paleta "Tierra"


## Tecnologías

| Capa | Tecnología |
|---|---|
| Frontend | Vue 3 + Vite, Vuetify (Material Design 3), PWA instalable |
| Backend  | Laravel 12, Laravel Sanctum (auth por cookie de sesión httpOnly) |
| Base de datos | PostgreSQL (Neon) |
| PDF | DomPDF |
| Email | SMTP (Brevo) / Resend |
| Deploy | Render (backend) + Vercel (frontend) + Neon (Postgres) |

Para levantar el proyecto localmente con Docker, ver la [guía de desarrollo](prototype/v0.1/README.md).


## Calidad e infraestructura

CapyMeal corre con la misma disciplina que un proyecto en producción real, aunque sea chico:

- ✅ **Tests automáticos** (backend): login, registro, diario, recuperación de contraseña y exportación de PDF.
- ✅ **CI/CD** (GitHub Actions): cada cambio corre tests + lint antes de poder mergearse.
- ✅ **Monitoreo de errores** (Sentry): backend y frontend avisan solos si algo se rompe en producción.
- ✅ **Monitoreo de disponibilidad** (UptimeRobot): aviso por email si el backend se cae.
- ✅ **Backups diarios** de la base (además del punto de restauración de Neon).
- ✅ **Dependabot**: alerta y actualiza solo las dependencias con vulnerabilidades conocidas.
- ✅ **Lint automático** (ESLint + Laravel Pint): el estilo del código no depende de que alguien lo revise a mano.


## Capi 🤎

Capi es un carpincho curioso y tranquilo. Le encanta compartir la mesa con sus amigos y cree que cada comida cuenta una historia.

No está interesado en dietas ni reglas estrictas; solo quiere ayudarte a recordar esos pequeños momentos del día que, con el tiempo, también forman parte de tus recuerdos.
