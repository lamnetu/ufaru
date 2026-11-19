FROM php:8.6-rc-cli
# GA-образ выйдет сегодня-завтра → замени на FROM php:8.6-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# Надёжный установщик расширений, без гонок параллельной сборки
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql zip intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
