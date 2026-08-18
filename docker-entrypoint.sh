#!/bin/bash
set -e

# Disable conflicting Apache MPM modules and ensure only prefork is active
a2dismod mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true
rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_event.conf /etc/apache2/mods-enabled/mpm_worker.conf 2>/dev/null || true

# Bind Apache to dynamic Railway PORT (default to 80 if not set)
TARGET_PORT="${PORT:-80}"
sed -i "s/Listen [0-9]*/Listen ${TARGET_PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${TARGET_PORT}>/g" /etc/apache2/sites-available/*.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${TARGET_PORT}>/g" /etc/apache2/sites-enabled/*.conf 2>/dev/null || true

# Ensure storage directories exist with appropriate permissions
mkdir -p /var/www/html/storage/uploads
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Start Apache in the foreground
exec apache2-foreground
