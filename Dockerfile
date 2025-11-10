FROM php:8.2-apache

# Install PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Configure Apache
RUN a2enmod rewrite

# Copy app (serve from repo root)
WORKDIR /var/www/html
COPY . /var/www/html/

# Healthcheck
HEALTHCHECK --interval=30s --timeout=3s CMD curl -fsS http://localhost/ || exit 1


