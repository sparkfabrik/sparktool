FROM php:7.0.7

# Install php packages.
RUN apt-get update \
  && apt-get install -y libicu-dev git zip \
  && docker-php-ext-configure intl \
  && docker-php-ext-configure pcntl \
  && docker-php-ext-install mbstring intl pcntl \
  && echo "${TIMEZONE}" > /etc/timezone \
  && dpkg-reconfigure -f noninteractive tzdata \
  && apt-get autoremove -y \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install composer.
ENV COMPOSER_HOME /composer-libs
RUN mkdir /composer-libs \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

RUN mkdir /app
WORKDIR /app
COPY composer.json /app
RUN composer install --no-interaction --prefer-dist
COPY . /app/
ENTRYPOINT ["./spark.php"]
