FROM php:8.2-apache

# Enable mysqli
RUN docker-php-ext-install mysqli

# Fix MPM conflict — disable event/worker, keep prefork
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
 && a2enmod mpm_prefork

# Basic Apache config
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Copy all project files
COPY . /var/www/html/

# Make entrypoint executable
RUN chmod +x /var/www/html/start.sh

# Railway uses a dynamic PORT — start.sh patches Apache at runtime
CMD ["/var/www/html/start.sh"]
