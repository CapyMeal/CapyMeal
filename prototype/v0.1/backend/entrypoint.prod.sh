#!/bin/bash
set -e

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
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

FRONTEND_URL=${FRONTEND_URL:-*}

MAIL_MAILER=${MAIL_MAILER:-log}
RESEND_API_KEY=${RESEND_API_KEY:-}
MAIL_HOST=${MAIL_HOST:-}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USERNAME=${MAIL_USERNAME:-}
MAIL_PASSWORD=${MAIL_PASSWORD:-}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-hello@capymeal.app}
MAIL_FROM_NAME=CapyMeal
EOF

mkdir -p storage/framework/sessions \
  storage/framework/views \
  storage/framework/cache \
  storage/framework/cache/data \
  storage/logs \
  bootstrap/cache

# Solo generamos una key nueva si Render no nos dio una ya (si la
# tenemos, hay que preservarla: cambiarla invalida las sesiones/tokens
# encriptados de los usuarios en cada deploy).
if [ -z "${APP_KEY:-}" ]; then
    echo "🔑 No hay APP_KEY, generando una nueva..."
    php artisan key:generate --force --no-interaction
else
    echo "🔑 Usando la APP_KEY existente de Render"
fi

php artisan package:discover --ansi

echo "🌸 Ejecutando migraciones..."
php artisan migrate --force --no-interaction

php artisan config:cache
php artisan route:cache

echo "🚀 Iniciando servidor en :8000"
php artisan serve --host=0.0.0.0 --port=8000
