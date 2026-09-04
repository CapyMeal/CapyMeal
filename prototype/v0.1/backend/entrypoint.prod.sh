#!/bin/bash
set -e

# Fail-fast en vez de un default silencioso: generar una APP_KEY nueva acá
# invalidaría las sesiones/tokens encriptados de los usuarios en cada
# redeploy sin ningún aviso, y una DATABASE_URL vacía haría que Laravel
# intente conectar a un Postgres local inexistente (vía los defaults de
# DB_HOST/DB_PORT de más abajo) en vez de fallar con un mensaje que
# señale el problema real -- las dos son variables que Render debe tener
# seteadas siempre, así que su ausencia es un error de configuración, no
# un caso a tolerar.
if [ -z "${APP_KEY:-}" ]; then
    echo "❌ Falta la variable de entorno APP_KEY (configurala en el dashboard de Render)." >&2
    exit 1
fi

if [ -z "${DATABASE_URL:-}" ]; then
    echo "❌ Falta la variable de entorno DATABASE_URL (configurala en el dashboard de Render)." >&2
    exit 1
fi

# Sanctum sólo acepta auth por cookie de sesión desde los dominios listados
# acá -- siempre el mismo host que FRONTEND_URL (sin protocolo), así que se
# deriva de esa misma variable en vez de exigir una segunda que Render
# tendría que mantener en sync a mano.
FRONTEND_HOST=$(echo "${FRONTEND_URL:-}" | sed -E 's#^https?://##; s#/+$##')

# Genera .env desde variables de entorno de Render
cat > .env <<EOF
APP_NAME=CapyMeal
APP_ENV=production
APP_DEBUG=false
APP_URL=${APP_URL:-http://localhost:8000}
APP_LOCALE=es
APP_KEY=${APP_KEY:-}

LOG_CHANNEL=stderr
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_URL=${DATABASE_URL:-}
DB_HOST=${DB_HOST:-localhost}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-capymeal}
DB_USERNAME=${DB_USERNAME:-capymeal}
DB_PASSWORD=${DB_PASSWORD:-capymeal}
DB_SSLMODE=require

CACHE_STORE=file
# "database" y no "file": el plan free de Render no tiene disco persistente
# (ver render.yaml, sin sección "disk:") -- el filesystem es efímero y se
# pisa en cada redeploy, y probablemente en cada reinicio del servicio tras
# dormirse por inactividad. Una sesión en archivo se perdería ahí,
# desconectando a todo el mundo en silencio mucho más seguido que con los
# bearer tokens de antes (que vivían en Postgres). La tabla "sessions" ya
# existe (viene con la migración default de Laravel, junto con "users"),
# no hace falta ninguna migración nueva para este cambio.
SESSION_DRIVER=database
QUEUE_CONNECTION=sync

# Si Render no seteó esta variable, mejor dejarla vacía (el frontend real
# se queda sin poder hacer requests, algo que se nota al toque) que un
# fallback tipo "*" que abriría CORS a cualquier origen en silencio.
FRONTEND_URL=${FRONTEND_URL:-}
SANCTUM_STATEFUL_DOMAINS=${FRONTEND_HOST}

# Fijos, no configurables por env: frontend (Vercel) y backend (Render) son
# dominios distintos de verdad, así que la cookie de sesión siempre necesita
# SameSite=None -- y SameSite=None exige Secure. Cualquier valor que no sea
# éste rompería el login en producción.
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=none

MAIL_MAILER=${MAIL_MAILER:-log}
RESEND_API_KEY=${RESEND_API_KEY:-}
MAIL_HOST=${MAIL_HOST:-}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USERNAME=${MAIL_USERNAME:-}
MAIL_PASSWORD=${MAIL_PASSWORD:-}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-hello@capymeal.app}
MAIL_FROM_NAME=CapyMeal
EOF

mkdir -p storage/framework/views \
  storage/framework/cache \
  storage/framework/cache/data \
  storage/logs \
  bootstrap/cache

echo "🔑 Usando la APP_KEY existente de Render"

php artisan package:discover --ansi

echo "🌸 Ejecutando migraciones..."
php artisan migrate --force --no-interaction

php artisan config:cache
php artisan route:cache

# "artisan serve" es un servidor de desarrollo (un solo proceso, sin
# concurrencia real) -- Laravel mismo advierte no usarlo en producción.
# FrankenPHP sí sirve requests concurrentes de verdad; "exec" reemplaza
# este shell por el proceso de FrankenPHP para que reciba directo las
# señales de Render (SIGTERM en cada redeploy) y pueda cerrar prolijo.
echo "🚀 Iniciando FrankenPHP en :8000"
exec frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile
