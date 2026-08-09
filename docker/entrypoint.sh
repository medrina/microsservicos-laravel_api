#!/bin/sh

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "Instalando dependências..."

if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist
fi

echo "Aguardando MySQL..."

until php artisan migrate --force
do
  echo "Aguardando banco..."
  sleep 5
done

echo 'MySQL disponível'

echo "Gerando APP_KEY..."

if ! grep -q "^APP_KEY=base64:" .env; then
    php artisan key:generate --force
fi

php artisan optimize:clear
php artisan config:clear

php artisan l5-swagger:generate

exec "$@"
