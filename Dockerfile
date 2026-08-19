FROM php:8.2-apache

# Enable mysqli
RUN docker-php-ext-install mysqli

# Copy all project files
COPY . /var/www/html/

# Make entrypoint executable
RUN chmod +x /var/www/html/start.sh

# Basic Apache config
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Railway uses a dynamic PORT — start.sh patches Apache at runtime
CMD ["/var/www/html/start.sh"]
