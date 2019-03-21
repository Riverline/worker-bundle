FROM php:5-cli

MAINTAINER Riverline

# Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('SHA384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');" \
    && chmod a+x /usr/local/bin/composer

# Install php extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    zlib1g-dev

# PHP EXTENSIONS
RUN apt-get install -y libgearman-dev \
    && pecl install gearman \
    && docker-php-ext-enable gearman \
    && docker-php-ext-install -j$(nproc) zip sysvmsg sysvshm \
    && docker-php-source delete
