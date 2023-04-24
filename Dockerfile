# https://github.com/codecasts/php-alpine/blob/master/README.md#php-73
FROM php:7.3-fpm-alpine3.14

ARG PHP_INFO="true"

# FROM nginx:1.24.0-alpine3.17-slim
# RUN echo -e " \
# # http://dl-cdn.alpinelinux.org/alpine/edge/main \
#  http://dl-cdn.alpinelinux.org/alpine/edge/community \
# # http://dl-cdn.alpinelinux.org/alpine/edge/testing \
# " >> /etc/apk/repositories
# RUN echo -e " \
# http://dl-cdn.alpinelinux.org/alpine/3.17/main \
# http://dl-cdn.alpinelinux.org/alpine/3.17/community \
# " >> /etc/apk/repositories

ENV PHP_INFO=$PHP_INFO

RUN \
echo ">>>>>>>>$PHP_INFO<<<<<<<<"; \
nproc; \
echo ">>>>>>>>$PHP_INFO<<<<<<<<"; \
[ "$PHP_INFO" = "true" ] && echo "PHP -R PHPINFO()" && php -r "phpinfo();"; \
[ "$PHP_INFO" = "true" ] && echo "PHP i" && php -i; \
echo "APK REPOS LIST" && cat /etc/apk/repositories; \
echo "PHP MODULES" && php -m; \
echo "ETC content" && ls -la /etc; \
echo "ETC/CONF.D content" && ls -la /etc/conf.d; \
echo "ETC/INIT.D content" && ls -la /etc/init.d; \
echo "END OF INFO";
# [ "$PHP_INFO" = "true" ] && echo "$PHP_INI_DIR PHP_INI_DIR content" && ls -la $PHP_INI_DIR; \
# [ "$PHP_INFO" = "true" ] && echo "$PHP_INI_DIR/CONF.D' PHP_INI_DIR/PHP-PRODUCTION.INI content" && cat $PHP_INI_DIR/php.ini-production; \
# [ "$PHP_INFO" = "true" ] && echo "$PHP_INI_DIR/CONF.D' PHP_INI_DIR/CONF.D content" && ls -la $PHP_INI_DIR/conf.d; \
# [ "$PHP_INFO" = "true" ] && echo "$PHP_INI_DIR/CONF.D/docker-php-ext-sodium.ini content" && cat $PHP_INI_DIR/conf.d/docker-php-ext-sodium.ini; \
# [ "$PHP_INFO" = "true" ] && echo "ETC/NGINX content" && ls -la /etc/nginx/; \
# [ "$PHP_INFO" = "true" ] && echo "ETC/NGINX/CONF.D content" && la -la /etc/nginx/conf.d/; \
# [ "$PHP_INFO" = "true" ] && echo "ETC/PHP7 content" && ls -la /etc/php7; \
# [ "$PHP_INFO" = "true" ] && echo "ETC/PHP7/PHP-FPM.D content" && ls -la /etc/php7/php-fpm.d; \
# [ "$PHP_INFO" = "true" ] && echo "ETC/PHP7/PHP-FPM.conf content" && cat /etc/php7/php-fpm.conf; \
# [ "$PHP_INFO" = "true" ] && echo "ETC/PHP7/PHP.ini content" && cat /etc/php7/php.ini; \
# [ "$PHP_INFO" = "true" ] && echo "ETC/PHP7/PHP-FPM.conf content" && cat /etc/nginx/conf.d/fastcgi.conf; \

RUN apk update && apk upgrade

# RUN apk add \
#     nginx=1.24 \
#     # php7.3 \
#     # php7.3-fpm \
#     php7.3-curl \
#     php7.3-fileinfo \
#     php7.3-gd \
#     php7.3-imagick \
#     php7.3-json \
#     php7.3-mbstring \
#     php7.3-mysql \
#     php7.3-opcache \
#     php7.3-xml \
#     php7.3-xmlrpc \
#     php-pear

RUN apk add --update util-linux
RUN apk add autoconf gcc tar
# RUN pecl install imagick
ADD https://pecl.php.net/get/imagick-3.4.3.tgz /tmp/imagick.tgz
RUN tar -xvzf imagick-3.4.3.tgz \
cd imagick-3.4.3 \
phpize \
./configure \
make install \
rm -rf /tmp/imagick-3.4.3*

# RUN docker-php-ext-enable imagick

RUN docker-php-ext-install -j$(nproc) curl fileinfo gd imagick json mdstring mysqli opcache xml xmlrpc

RUN apk add \
    nginx=1.20 \
    php-pear
    # php-curl \
    # php-fileinfo \
    # php-gd \
    # php-imagick \
    # php-json \
    # php-mbstring \
    # php-mysqli \
    # php-opcache \
    # php-xml \
    # php-xmlrpc \

########### final try if ALL others fail    # RUN docker-php-ext-install -j$(nproc) gd ...
# this seems not to be necessary            # RUN docker-php-ext-enable gd

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

RUN mkdir -p /var/log/php-fpm && touch /var/log/php-fpm/access.log \
&& chown -R www-data: /var/log/php-fpm && chown -R www-data: /var/log/nginx

RUN \
echo ">>>>>>>>$PHP_INFO<<<<<<<<"; \
nproc; \
echo ">>>>>>>>$PHP_INFO<<<<<<<<"; \
[ "$PHP_INFO" = "true" ] && echo "PHP -R PHPINFO()" && php -r "phpinfo();"; \
[ "$PHP_INFO" = "true" ] && echo "PHP i" && php -i; \
echo "APK REPOS LIST" && cat /etc/apk/repositories; \
echo "PHP MODULES" && php -m; \
echo "ETC content" && ls -la /etc; \
echo "ETC/CONF.D content" && ls -la /etc/conf.d; \
echo "ETC/INIT.D content" && ls -la /etc/init.d; \
echo "END OF INFO";
# [ "$PHP_INFO" = "true" ] && echo "$PHP_INI_DIR PHP_INI_DIR content" && ls -la $PHP_INI_DIR; \
# [ "$PHP_INFO" = "true" ] && echo "$PHP_INI_DIR/CONF.D' PHP_INI_DIR/PHP-PRODUCTION.INI content" && cat $PHP_INI_DIR/php.ini-production; \
# [ "$PHP_INFO" = "true" ] && echo "$PHP_INI_DIR/CONF.D' PHP_INI_DIR/CONF.D content" && ls -la $PHP_INI_DIR/conf.d; \
# [ "$PHP_INFO" = "true" ] && echo "$PHP_INI_DIR/CONF.D/docker-php-ext-sodium.ini content" && cat $PHP_INI_DIR/conf.d/docker-php-ext-sodium.ini; \
# [ "$PHP_INFO" = "true" ] && echo "ETC/NGINX content" && ls -la /etc/nginx/; \
# [ "$PHP_INFO" = "true" ] && echo "ETC/NGINX/CONF.D content" && la -la /etc/nginx/conf.d/; \
# [ "$PHP_INFO" = "true" ] && echo "ETC/PHP7 content" && ls -la /etc/php7; \
# [ "$PHP_INFO" = "true" ] && echo "ETC/PHP7/PHP-FPM.D content" && ls -la /etc/php7/php-fpm.d; \
# [ "$PHP_INFO" = "true" ] && echo "ETC/PHP7/PHP-FPM.conf content" && cat /etc/php7/php-fpm.conf; \
# [ "$PHP_INFO" = "true" ] && echo "ETC/PHP7/PHP.ini content" && cat /etc/php7/php.ini; \
# [ "$PHP_INFO" = "true" ] && echo "ETC/PHP7/PHP-FPM.conf content" && cat /etc/nginx/conf.d/fastcgi.conf; \

RUN rc-update add nginx default
RUN rc-update add php-fpm7 default

RUN php-fpm7 -t

RUN rc-service php-fpm7 restart
RUN rc-service nginx restart

# RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# default WORKDIR /var/www/html

RUN mkdir -p /var/www/{rsm_image_cache,rsm_file_cache} && mkdir -p /tmp/php_tmp
COPY ./Server/htdocs/ /var/www/html/

EXPOSE 9000

CMD ["php-fpm"]
