FROM php:8.4-apache

# pout utiliser le dossier "public" comme dossier par defaut
COPY ./apache.conf /etc/apache2/sites-enabled/000-default.conf

# copy du projet
COPY ./deploiement/spotify-AP_1.0.0 /var/www/

# par defaut /var/www/html
WORKDIR /var/www
