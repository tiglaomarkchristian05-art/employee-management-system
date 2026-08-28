FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . /var/www/html

RUN mkdir -p /var/www/html/public/uploads/documents \
        /var/www/html/assets/uploads/documents \
        /var/www/html/assets/uploads/avatars \
        /var/www/html/assets/uploads/certificates \
    && chown -R www-data:www-data /var/www/html/public/uploads /var/www/html/assets/uploads

ENV APP_ENV=production \
    APP_DEBUG=0

EXPOSE 80

CMD ["sh", "-c", "php scripts/init-database.php && exec apache2-foreground"]
