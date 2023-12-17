# Imagen base de PHP con Apache
FROM php:8.0.1-apache

# Instala las extensiones de PHP necesarias y otras herramientas
RUN apt-get update && apt-get install -y \
    libzip-dev \
    git \
    && docker-php-ext-install mysqli pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# Configura el archivo de virtual host de Apache
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Copia los archivos de tu proyecto de PHP al directorio del servidor web
COPY . /var/www/html

# Instala Node.js y npm
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Regresa al directorio del servidor web de PHP
WORKDIR /var/www/html

# Expone el puerto en el que se ejecutará la aplicación de PHP
EXPOSE 2024

# Define el comando que se ejecutará cuando se inicie el contenedor
CMD ["apache2-foreground"]
