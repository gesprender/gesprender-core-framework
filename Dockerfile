FROM php:8.2.3-fpm

# Instala las extensiones de PHP necesarias, herramientas y utilidades para descomprimir archivos
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    git \
    libpq-dev \
    nginx \
    unzip \
    p7zip-full \
    && docker-php-ext-install pgsql pdo pdo_pgsql zip mysqli pdo_mysql gd \
    && pecl install xdebug-3.3.1 \
    && docker-php-ext-enable xdebug

# Limpieza de archivos temporales de apt-get
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Configuración de Xdebug
RUN { \
        echo 'xdebug.mode=debug'; \
        echo 'xdebug.client_host=host.docker.internal'; \
        echo 'xdebug.client_port=9003'; \
        echo 'xdebug.start_with_request=yes'; \
    } > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Instalación de Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# Copia del código fuente a la imagen
COPY . /var/www/html

# Establece el directorio de trabajo
WORKDIR /var/www/html

# Verifica que composer.json está presente
RUN if [ ! -f composer.json ]; then echo "composer.json not found!"; exit 1; fi

# Ejecuta composer install
RUN composer install

# Configuración de Nginx
COPY ./Docker/nginx.conf /etc/nginx/nginx.conf

# Custom php.ini
COPY ./Docker/php.ini /usr/local/etc/php/

# Expone los puertos para Nginx y PHP-FPM
EXPOSE 80 9000

# comando para ejecutar Nginx y PHP-FPM
CMD ["sh", "-c", "nginx && php-fpm"]
