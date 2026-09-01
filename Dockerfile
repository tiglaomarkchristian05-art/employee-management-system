FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && a2enmod rewrite headers \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && printf 'expose_php=Off\ndisplay_errors=Off\nlog_errors=On\n' > /usr/local/etc/php/conf.d/zz-production.ini \
    && printf 'ServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-available/security-hardening.conf \
    && a2enconf security-hardening \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html

RUN mkdir -p /var/www/html/public/uploads/documents \
        /var/www/html/assets/uploads/documents \
        /var/www/html/assets/uploads/avatars \
        /var/www/html/assets/uploads/certificates \
        /var/www/html/uploads/loans \
    && chown -R www-data:www-data /var/www/html/public/uploads /var/www/html/assets/uploads /var/www/html/uploads

ENV APP_ENV=production \
    APP_DEBUG=0

VOLUME ["/var/www/html/public/uploads", "/var/www/html/assets/uploads", "/var/www/html/uploads"]

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD php -r '$socket=@fsockopen("127.0.0.1",80,$errno,$error,3);if(!$socket)exit(1);fwrite($socket,"GET /index.php?page=login HTTP/1.1\r\nHost: localhost\r\nConnection: close\r\n\r\n");$status=fgets($socket);fclose($socket);exit(strpos($status," 200 ")!==false||strpos($status," 302 ")!==false?0:1);'

CMD ["sh", "-c", "php scripts/init-database.php && exec apache2-foreground"]
