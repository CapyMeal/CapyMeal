#!/bin/bash
set -e

# Genera .env desde variables de entorno de Render
cat > .env <<EOF
APP_NAME=CapyMeal
APP_ENV=production
APP_DEBUG=false
APP_URL=${APP_URL:-http://localhost:8000}
APP_KEY=

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

MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=465
MAIL_USERNAME=resend
MAIL_PASSWORD=${RESEND_API_KEY:-}
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-onboarding@resend.dev}
MAIL_FROM_NAME=CapyMeal

FRONTEND_URL=${FRONTEND_URL:-*}
EOF

mkdir -p storage/framework/sessions \
  storage/framework/views \
  storage/framework/cache \
  storage/framework/cache/data \
  storage/logs \
  bootstrap/cache

php artisan key:generate --force --no-interaction
php artisan package:discover --ansi

echo "🌸 Ejecutando migraciones..."
php artisan migrate --force --no-interaction

php artisan config:cache
php artisan route:cache

echo "🚀 Iniciando servidor en :8000"
php artisan serve --host=0.0.0.0 --port=8000
