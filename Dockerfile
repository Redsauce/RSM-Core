FROM ubuntu:focal-20230412

RUN echo "deb https://ppa.launchpadcontent.net/ondrej/php/ubuntu focal main" >> /etc/apt/sources.list
RUN echo "#deb-src https://ppa.launchpadcontent.net/ondrej/php/ubuntu focal main" >> /etc/apt/sources.list
RUN apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 4f4ea0aae5267a6c

RUN apt update && apt upgrade

RUN apt-get install -y \
    nginx \
    php7.3 \
    php7.3-fpm \
    php7.3-curl \
    php7.3-gd \
    php7.3-json \
    php7.3-mbstring \
    php7.3-mysql \
    php7.3-opcache \
    php7.3-xml \
    php7.3-xmlrpc \
    php7.3-fileinfo \
    php7.3-imagick \
    php-pear

RUN cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.orig
RUN sed -i -E 's/^(\s*keepalive_timeout\s+\w+\s*)/# \1\nkeepalive_timeout 2/' /etc/nginx/nginx.conf
RUN sed -i -E 's/^(\s*server_tokens\s+\w+\s*)/# \1\nserver_tokens off/' /etc/nginx/nginx.conf

RUN cp /etc/php/7.3/fpm/php.ini /etc/php/7.3/fpm/php.ini.orig
RUN sed -i -E 's/^(\s*cgi\.fix_pathinfo\s*=\s*\w*\s*)/# \1\ncgi.fix_pathinfo=0/' /etc/php/7.3/fpm/php.ini

RUN echo -e "server {\n \
    listen 80;\n \
    access_log /var/log/nginx/rsm_access.log;\n \
    error_log /var/log/nginx/rsm_error.log;\n \
    root /var/www/localhost/htdocs;\n \
    index index.html index.htm index.php;\n \
    location / {\n \
        try_files $uri $uri/ =404;\n \
    }\n \
    location ~ \.php$ {\n \
        include           fastcgi.conf;\n \
        fastcgi_pass      unix:/var/run/php/php7.3-fpm.sock;\n \
    }\n \
}\n" > /etc/nginx/sites-available/rsm.conf


RUN mkdir -p /var/log/nginx && touch /var/log/nginx/rsm_access.log && touch /var/log/nginx/rsm_error.log && chown -R www-data: /var/log/nginx

RUN ln -s /etc/nginx/sites-available/rsm.conf /etc/nginx/sites-enabled/rsm.conf && rm /etc/nginx/sites-enabled/default && nginx -t

RUN mkdir -p /var/log/php-fpm && touch /var/log/php-fpm/access.log && touch /var/log/php-fpm/error.log && chown -R www-data: /var/log/php-fpm

RUN cp /etc/php/7.3/fpm/pool.d/www.conf /etc/php/7.3/fpm/pool.d/www.conf.orig
RUN sed -i -E 's/^(\s*php_admin_flag\[log_errors\]\s*=\s*\w*\s*)/# \1\nphp_admin_flag[log_errors] = on/' /etc/php/7.3/fpm/pool.d/www.conf
RUN sed -i -E 's/^(\s*php_admin_value\[error_log\]\s*=\s*\w*\s*)/# \1\nphp_admin_value[error_log] = /var/log/php-fpm/error.log/' /etc/php/7.3/fpm/pool.d/www.conf
RUN sed -i -E 's/^(\s*php_flag\[display_errors\]\s*=\s*\w*\s*)/# \1\nphp_flag[display_errors] = on/' /etc/php/7.3/fpm/pool.d/www.conf
RUN sed -i -E 's/^(\s*catch_workers_output\s*=\s*\w*\s*)/# \1\ncatch_workers_output = yes/' /etc/php/7.3/fpm/pool.d/www.conf
RUN sed -i -E 's/^(\s*listen\.allowed_clients\s*=\s*\w*\s*)/# \1\nlisten.allowed_clients = 127.0.0.1/' /etc/php/7.3/fpm/pool.d/www.conf
RUN sed -i -E 's/^(\s*access\.log\s*=\s*\w*\s*)/# \1\naccess.log = /var/log/php-fpm/access.log/' /etc/php/7.3/fpm/pool.d/www.conf

# default WORKDIR /var/www/html

RUN mkdir -p /var/www/{rsm_image_cache,rsm_file_cache} && mkdir -p /tmp/php_tmp

COPY ./Server/htdocs/ /var/www/html/
RUN find /var/www/html/AppController -type d -exec chmod u=rwx,g=rx,o=rx {} +
RUN find /var/www/html/AppController -type f -exec chmod u=rw,g=r,o=r {} +
RUN chmod u=rw,g=r,o=r /var/www/html/index*
RUN chmod u=rw,g=r,o=r /var/www/html/roche.svg

RUN chown -R www-data:www-data /var/www

RUN rc-update add nginx default
RUN rc-update add php-fpm7 default

RUN php-fpm7 -t

RUN rc-service php-fpm7 restart
RUN rc-service nginx restart

EXPOSE 9000
