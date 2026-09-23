#!/bin/sh
set -e

# Railway provides dynamic PORT environment variable (default to 8080 if not set)
PORT="${PORT:-8080}"
echo "Configuring Apache for Railway on port ${PORT}..."

# Update Apache listening port dynamically
sed -i "s/Listen [0-9]*/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Ensure storage directories exist
mkdir -p /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/app/public/products \
         /var/www/html/storage/app/slips \
         /var/www/html/storage/logs

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Run database migrations before serving traffic
echo "Running database migrations..."
php artisan migrate --force

# Ensure storage symlink exists for public uploads
php artisan storage:link || true

# Optimize configuration and routes for production if APP_ENV=production
if [ "$APP_ENV" = "production" ]; then
    echo "Caching configuration and routes for production..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Auto-create or update admin account if ADMIN_EMAIL and ADMIN_PASSWORD are set
if [ -n "$ADMIN_EMAIL" ] && [ -n "$ADMIN_PASSWORD" ]; then
    echo "Configuring admin account from environment..."
    php artisan admin:create --no-interaction || true
fi

echo "Starting Apache on port ${PORT}..."
exec apache2-foreground
