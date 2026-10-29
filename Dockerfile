FROM php:8.3-apache
RUN docker-php-ext-install pdo_mysql
COPY app/ /var/www/html/
RUN chown -R root:www-data /var/www/html \
 && find /var/www/html -type d -exec chmod 755 {} + \
 && find /var/www/html -type f -exec chmod 644 {} +
EXPOSE 80
