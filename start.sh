#!/bin/bash
set -e

PORT="${PORT:-80}"

# Fix: disable conflicting MPMs, keep only prefork
a2dismod mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

# Patch Apache to listen on Railway's dynamic PORT
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-enabled/000-default.conf

echo "Starting Apache on port $PORT"
exec apache2-foreground
