FROM php:5-cli

MAINTAINER Riverline

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install php extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    zlib1g-dev

# PHP EXTENSIONS
RUN apt-get install -y libgearman-dev \
    && pecl install gearman \
    && docker-php-ext-enable gearman \
    && docker-php-ext-install -j$(nproc) zip sysvmsg sysvshm \
    && docker-php-source delete
