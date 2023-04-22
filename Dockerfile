# FROM php:7.3-fpm-alpine3.14
FROM nginx:1.24.0-alpine3.17-slim

RUN echo -e " \
# http://dl-cdn.alpinelinux.org/alpine/edge/main \
http://dl-cdn.alpinelinux.org/alpine/edge/community \
# http://dl-cdn.alpinelinux.org/alpine/edge/testing \
" >> /etc/apk/repositories

# RUN echo -e " \
# # http://dl-cdn.alpinelinux.org/alpine/3.17/main \
# http://dl-cdn.alpinelinux.org/alpine/3.17/community \
# # http://dl-cdn.alpinelinux.org/alpine/3.17/testing \
# " >> /etc/apk/repositories

RUN apk update && apk upgrade && apk add \
    php7.3 \
    php7.3-fpm \
    php7.3-curl \
    php7.3-fileinfo \
    php7.3-gd \
    php7.3-imagick \
    php7.3-json \
    php7.3-mbstring \
    php7.3-mysql \
    php7.3-opcache \
    php7.3-xml \
    php7.3-xmlrpc \
    php-pear

RUN echo "server { \
    listen 80; \
    access_log /var/log/nginx/rsm_access.log; \
    error_log /var/log/nginx/rsm_error.log; \
    root /var/www/localhost/htdocs; \
    index index.html index.htm index.php; \
    location / { \
        try_files $uri $uri/ =404; \
    } \
    location ~ \.php$ { \
        # fastcgi_pass      127.0.0.1:9000; \
        fastcgi_pass      unix:/var/run/php/php7.3-fpm.sock; \
        fastcgi_index     index.php; \
        include           fastcgi.conf; \
    } \
}" > /etc/nginx/conf.d/rsm.conf

RUN mkdir -p /var/log/php-fpm && touch /var/log/php-fpm/access.log
RUN chown -R www-data: /var/log/php-fpm && chown -R www-data: /var/log/nginx

RUN ls -la /etc; \
ls -la /etc/php7; \
ls -la /etc/php7/php-fpm.d; \
ls -la /etc/nginx/; \
la -la /etc/nginx/conf.d/; \
cat /etc/php7/php-fpm.conf; \
cat /etc/php7/php.ini; \
cat /etc/nginx/conf.d/fastcgi.conf; \
echo $PHP_INI_DIR; \
cat $PHP_INI_DIR

RUN rc-update add nginx default
RUN rc-update add php-fpm7 default

RUN php-fpm7 -t

RUN rc-service php-fpm7 restart
RUN rc-service nginx restart

# RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www

# COPY ./src /var/www/localhost/htdocs

EXPOSE 9000

CMD ["php-fpm"]
