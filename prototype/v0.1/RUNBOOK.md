# Runbook de incidentes

Qué hacer si un deploy a `dev` rompe algo en producción. `dev` auto-deploya a
Render (backend) y Vercel (frontend) en cada push — no hay staging ni
blue-green: el rollback siempre es manual.

## 1. Revertir el código

Válido para la mayoría de los casos: un bug de lógica, una regresión visual,
una config mal puesta.

```bash
git checkout dev
git pull origin dev
git revert <hash-del-commit-roto>   # o el rango de commits, si son varios
git push origin dev
```

El push a `dev` dispara el redeploy automático en ambos lados:

- **Backend (Render)**: dashboard → capymeal-backend → pestaña "Events" para
  seguir el build. `curl https://capymeal.onrender.com/up` debería volver a
  dar `200` cuando termine.
- **Frontend (Vercel)**: dashboard → capy-meal → pestaña "Deployments".

No hace falta ninguna acción manual además del revert — ninguno de los dos
servicios necesita un paso de aprobación.

## 2. Si el deploy roto tocó datos o el schema de la base

El revert de código de arriba **no revierte una migración ya corrida**. Si el
deploy problemático incluyó una migración destructiva (borró una columna,
corrompió datos), hay que restaurar desde el backup diario:

1. Confirmar qué backup usar: `.github/workflows/backup-db.yml` corre todos
   los días a las 3am hora Argentina y sube el dump como artifact (30 días de
   retención). Elegí el run de la corrida **anterior** al incidente.
2. Descargar el artifact (`gh run download <run-id> --name <nombre-del-backup>`
   o desde la pestaña "Actions" de GitHub).
3. Restaurar contra la base real de Neon:
   ```bash
   pg_restore --clean --if-exists --no-owner --no-privileges \
     -d "$NEON_DATABASE_URL" backup.dump
   ```
   Usar el `postgresql-client` de la versión real de Postgres (18), no el que
   trae por defecto el repo de apt de Ubuntu — ver el comentario en
   `.github/workflows/restore-drill.yml` si aparece el error
   `unsupported version ... in file header`.
4. Verificar que los datos quedaron sanos antes de dar el incidente por
   cerrado: `SELECT count(*) FROM users;` / `SELECT count(*) FROM
   meal_entries;` deberían dar números razonables, no cero.

`restore-drill.yml` (manual, `workflow_dispatch`) hace exactamente este
proceso contra un Postgres descartable, sin tocar la base real — correrlo de
vez en cuando confirma que el backup más reciente sigue siendo restaurable,
antes de que haga falta usarlo de verdad en un incidente.

## 3. Si el problema es de configuración (Render/Vercel)

Antes de tocar código, revisar si el problema es una variable de entorno mal
seteada o faltante en el dashboard de cada proveedor (no está versionado en
el repo). `backend/entrypoint.prod.sh` falla explícito con un mensaje claro
si falta `APP_KEY` o `DATABASE_URL` — si el deploy no arranca, ese es
frecuentemente el primer lugar para mirar.

## 4. Incidente de seguridad

Para cuando hay sospecha de que alguien entró a una cuenta ajena, se filtró
una clave o un secreto, o hay un ataque en curso (muchos intentos de login,
registros falsos, abuso del envío de mails). Primero se contiene, después se
investiga.

### Cómo darse cuenta

- **Sentry** (proyectos `capymeal-backend` y `capymeal-frontend`): manda mail
  con cada issue nuevo. Un pico de errores 429 o 422 en `/api/login` o
  `/api/login/two-factor` es la señal típica de alguien probando
  contraseñas.
- **Render** → capymeal-backend → "Logs": muchas requests seguidas a las
  mismas rutas de autenticación.
- **Brevo** (mails): un salto en los envíos de "recuperar contraseña" o de
  verificación de email que nadie pidió.
- **GitHub** → Security: alertas de secret scanning si un secreto quedó
  commiteado en el repo (que es público).

### Qué ya frena solo

- Límite por IP en cada ruta de autenticación (`routes/api.php`).
- Límite por cuenta (`app/Support/AccountAttemptLimiter.php`): 5 intentos
  fallidos de contraseña por email, o 5 códigos de dos pasos equivocados por
  usuario, bloquean esa cuenta 15 minutos. Se desbloquea sola.
- Las cuentas de Google y Microsoft no tienen contraseña en CapyMeal: un
  ataque de contraseñas no las alcanza.

### Contener

1. **Cortar todas las sesiones** (obliga a todo el mundo a volver a entrar):
   desde el SQL Editor de Neon,
   ```sql
   DELETE FROM sessions;
   DELETE FROM personal_access_tokens;
   ```
   Para una sola cuenta: `DELETE FROM sessions WHERE user_id = <id>;` y
   `DELETE FROM personal_access_tokens WHERE tokenable_id = <id>;`.
2. **Frenar todo de golpe** si el ataque sigue: Render → capymeal-backend →
   Settings → "Suspend Web Service". La app queda sin backend (nadie puede
   entrar ni guardar) hasta que se reanuda. Es el último recurso.
3. **Rotar el secreto comprometido** en el dashboard de Render (Environment)
   y redeployar:
   - **Google** (`GOOGLE_CLIENT_SECRET`): Google Cloud Console →
     Credenciales → el cliente OAuth de CapyMeal → nuevo secreto.
   - **Microsoft** (`MICROSOFT_CLIENT_SECRET`): Azure → App registrations →
     CapyMeal → Certificates & secrets.
   - **Mail** (`MAIL_PASSWORD`): Brevo → SMTP & API → nueva clave SMTP.
   - **Base** (`DATABASE_URL`): Neon → Roles → resetear la contraseña del
     rol, y actualizar también el secret `NEON_DATABASE_URL` de GitHub
     Actions (lo usa el backup diario).
   - **Scheduler** (`SCHEDULER_TOKEN`): uno nuevo en Render y el mismo en el
     secret de GitHub que usa `.github/workflows/scheduler.yml`.
   - **`APP_KEY`, con cuidado**: además de las sesiones, cifra el secreto y
     los códigos de recuperación de la verificación en dos pasos
     (`two_factor_secret`, `two_factor_recovery_codes` en `User.php`). Si se
     cambia sin más, nadie con dos pasos activado puede volver a entrar.
     Hacerlo así: generar la nueva (`php artisan key:generate --show` en
     local), poner la **vieja** en `APP_PREVIOUS_KEYS` y la nueva en
     `APP_KEY`. Laravel sigue leyendo lo cifrado con la anterior. No borrar
     `APP_PREVIOUS_KEYS` mientras haya cuentas con dos pasos configurados
     antes de la rotación.
4. **Si la sospecha es sobre una cuenta puntual**: cortar sus sesiones
   (paso 1) y avisarle a esa persona que cambie su contraseña desde
   "¿Olvidaste tu contraseña?" y active la verificación en dos pasos.

### Después

- Revisar en los logs de Render y en Sentry qué se hizo, desde dónde y
  cuándo, antes de que se borren (los logs del plan gratuito duran poco).
- Si hubo acceso a datos de otras personas (diarios, emails), avisarles a
  las afectadas, y consultar si corresponde notificar a la Agencia de
  Acceso a la Información Pública (ley 25.326 de datos personales).
- Anotar qué pasó y qué se cambió, y si hace falta, sumar un paso nuevo a
  esta sección.
