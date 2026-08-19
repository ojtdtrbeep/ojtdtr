FROM php:8.2-apache

# Enable mysqli
RUN docker-php-ext-install mysqli

# Copy API files
COPY . /var/www/html/

# Apache listens on PORT env var (Railway sets this)
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf
EXPOSE 80

CMD ["apache2-foreground"]
