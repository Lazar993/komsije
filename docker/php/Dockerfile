FROM php:8.4-fpm

# Instalacija sistemskih paketa i zavisnosti
RUN apt-get update && apt-get install -yq --no-install-recommends \
    git \
    unzip \
    zip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Opcionalno: očistimo cache da slika bude manja
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalacija Composer-a
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Radni direktorijum
WORKDIR /var/www