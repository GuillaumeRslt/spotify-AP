FROM php:8.4-apache

RUN a2enmod rewrite

# maj + install extension
RUN apt-get update \
  && apt-get install -y libzip-dev git wget --no-install-recommends \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
 
RUN docker-php-ext-install pdo mysqli pdo_mysql zip;

# Install composer
RUN wget https://getcomposer.org/download/2.9.3/composer.phar \ 
    && mv composer.phar /usr/bin/composer && chmod +x /usr/bin/composer

# pout utiliser le dossier "public" comme dossier par defaut
COPY ./apache.conf /etc/apache2/sites-enabled/000-default.conf
COPY docker/entrypoint.sh /entrypoint.sh

# copy du projet
COPY ./config /var/www/html/config
COPY ./public /var/www/html/public
COPY ./src /var/www/html/src
COPY docker/.env /var/www/html/.env
COPY ./composer.json /var/www/html/composer.json
COPY ./token_file.json /var/www/html/token_file.json

# par defaut /var/www/html
WORKDIR /var/www/html

RUN chmod +x /entrypoint.sh

CMD ["apache2-foreground"]

ENTRYPOINT ["/entrypoint.sh"]
