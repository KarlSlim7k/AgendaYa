# ============================================
# Dockerfile PHP 8.4 - SOLO DESARROLLO LOCAL
# ============================================
# NO USAR EN PRODUCCIÓN (Neubox Tellit)
# ============================================

FROM php:8.4-fpm

# Metadatos
LABEL maintainer="CitasEmpresariales Dev Team"
LABEL description="PHP 8.2-FPM para desarrollo local - Laravel 12"

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    mariadb-client \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP requeridas por Laravel
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# Instalar Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Configurar OPcache para desarrollo
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Crear usuario no-root para evitar problemas de permisos
RUN groupadd -g 1000 laravel \
    && useradd -u 1000 -g laravel -m -s /bin/bash laravel

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Cambiar al usuario laravel
USER laravel

# Exponer puerto PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
