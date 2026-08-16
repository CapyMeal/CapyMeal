#!/bin/bash
set -e

# Copia Laravel desde staging si es la primera vez (artisan no existe en el volumen)
if [ ! -f "artisan" ]; then
    echo "🐹 Primera vez — copiando Laravel al volumen..."
    cp -rn /laravel-staging/. /var/www/html/
    echo "✅ Laravel listo"
fi

# Crea o reescribe el .env con la configuración del entorno Docker
cat > .env <<EOF
APP_NAME=CapyMeal
APP_ENV=${APP_ENV:-local}
APP_DEBUG=${APP_DEBUG:-true}
APP_URL=${APP_URL:-http://localhost:8000}
APP_LOCALE=es
APP_KEY=${APP_KEY:-}

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=${DB_CONNECTION:-pgsql}
DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-capymeal}
DB_USERNAME=${DB_USERNAME:-capymeal}
DB_PASSWORD=${DB_PASSWORD:-capymeal}

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

FRONTEND_URL=${FRONTEND_URL:-http://localhost:5174}

MAIL_MAILER=${MAIL_MAILER:-log}
RESEND_API_KEY=${RESEND_API_KEY:-}
MAIL_HOST=${MAIL_HOST:-}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USERNAME=${MAIL_USERNAME:-}
MAIL_PASSWORD=${MAIL_PASSWORD:-}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-hello@capymeal.local}
MAIL_FROM_NAME=CapyMeal
EOF

if [ -z "${APP_KEY:-}" ]; then
    php artisan key:generate --force --no-interaction
fi

echo "🌸 Ejecutando migraciones..."
php artisan migrate --force --no-interaction

echo "🚀 Iniciando servidor en :8000"
php artisan serve --host=0.0.0.0 --port=8000
