FROM php:7.1.3-cli

MAINTAINER Riverline

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install php extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    zlib1g-dev

# PHP EXTENSIONS
RUN apt-get install -y libzip-dev libgearman-dev \
    && docker-php-ext-install -j$(nproc) zip sysvmsg sysvshm \
    && docker-php-source delete

RUN apt-get -y --allow-unauthenticated install libgearman-dev wget unzip \
    && cd /tmp \
    && wget https://github.com/wcgallego/pecl-gearman/archive/gearman-2.0.6.zip \
    && unzip gearman-2.0.6.zip \
    && mv pecl-gearman-gearman-2.0.6 pecl-gearman \
    && cd pecl-gearman \
    && phpize \
    && ./configure \
    && make -j$(nproc) \
    && make install \
    && cd / \
    && rm /tmp/gearman-2.0.6.zip \
    && rm -r /tmp/pecl-gearman \
    && docker-php-ext-enable gearman
