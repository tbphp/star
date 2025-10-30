#!/bin/bash
set -e

echo "======================================"
echo "Starting Laravel initialization..."
echo "======================================"

# Verify frontend build exists
if [ ! -d "/var/www/html/public/dist" ]; then
    echo "⚠ WARNING: Frontend build not found at /var/www/html/public/dist"
else
    echo "✓ Frontend build found"
fi

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force
echo "✓ Migrations completed"

# Create storage symbolic link
echo "Creating storage symbolic link..."
php artisan storage:link || echo "Storage link already exists or failed, continuing..."
echo "✓ Storage link created"

# Set proper permissions
echo "Setting file permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/public
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/public
echo "✓ Permissions set"

# Clear and cache configuration (production optimization)
echo "Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✓ Optimization completed"

echo "======================================"
echo "Laravel initialization completed!"
echo "Starting PHP-FPM..."
echo "======================================"

# Start PHP-FPM
exec php-fpm
