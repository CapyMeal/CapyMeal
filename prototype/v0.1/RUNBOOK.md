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
