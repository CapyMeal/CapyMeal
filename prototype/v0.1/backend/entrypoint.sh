#!/bin/bash
set -e

# Copia Laravel desde staging si es la primera vez (artisan no existe en el volumen)
if [ ! -f "artisan" ]; then
    echo "🐹 Primera vez — copiando Laravel al volumen..."
    cp -rn /laravel-staging/. /var/www/html/
    echo "✅ Laravel listo"
fi

# Configura el .env con las variables del entorno Docker
php -r "
\$env = file_get_contents('.env');
\$env = preg_replace('/^DB_CONNECTION=.*/m', 'DB_CONNECTION=' . getenv('DB_CONNECTION'), \$env);
\$env = preg_replace('/^DB_HOST=.*/m',       'DB_HOST='       . getenv('DB_HOST'),       \$env);
\$env = preg_replace('/^DB_PORT=.*/m',       'DB_PORT='       . getenv('DB_PORT'),       \$env);
\$env = preg_replace('/^DB_DATABASE=.*/m',   'DB_DATABASE='   . getenv('DB_DATABASE'),   \$env);
\$env = preg_replace('/^DB_USERNAME=.*/m',   'DB_USERNAME='   . getenv('DB_USERNAME'),   \$env);
\$env = preg_replace('/^DB_PASSWORD=.*/m',   'DB_PASSWORD='   . getenv('DB_PASSWORD'),   \$env);
file_put_contents('.env', \$env);
"

php artisan key:generate --no-interaction 2>/dev/null || true

echo "🌸 Ejecutando migraciones..."
php artisan migrate --force --no-interaction 2>/dev/null || true

echo "🚀 Iniciando servidor en :8000"
php artisan serve --host=0.0.0.0 --port=8000
