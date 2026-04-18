FROM php:8.2-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install pdo pdo_mysql

# Copiar la aplicación
COPY app/ /var/www/html/

# Permisos
RUN chmod -R 755 /var/www/html

EXPOSE 80
