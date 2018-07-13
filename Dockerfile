FROM php:7
RUN apt-get update -y && apt-get install -y openssl zip unzip git
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install pdo pdo_mysql
WORKDIR /var/www
COPY composer.json /var/www
COPY composer.lock /var/www
RUN composer install --no-scripts --no-autoloader --no-interaction --no-progress
COPY . /var/www
RUN composer dump-autoload --optimize
RUN php artisan key:generate
RUN php artisan config:cache
# CMD php artisan serve --host=0.0.0.0 --port=8009
# COPY ./run.sh /tmp
ENTRYPOINT ["/var/www/run.sh"]
EXPOSE 8000
