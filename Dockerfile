FROM php:8.4-fpm

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip \
    intl \
    gd \
    opcache

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définir le répertoire de travail
WORKDIR /app

# Copier les fichiers du projet
COPY . /app/

# Installer les dépendances Composer
RUN composer install --no-scripts --no-autoloader

# Générer l'autoloader
RUN composer dump-autoload --optimize

# Créer les répertoires nécessaires
RUN mkdir -p var/cache var/log

# Définir les permissions
RUN chown -R www-data:www-data /app

EXPOSE 9000

CMD ["php-fpm"]
