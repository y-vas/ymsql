FROM php:7.3-alpine

RUN apk update \
    apt add php-xml php-mbstring composer \
    curl

############################### Install composer ###############################
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

CMD composer update -d /var/www --ignore-platform-reqs \
    && chmod -R 777 /var/www/vendor \
