FROM ubuntu:focal-20230412

ARG ARG_DBHOST="dbhost"
ARG ARG_DBNAME="dbname"
ARG ARG_DBUSERNAME="dbusername"
ARG ARG_DBPASSWORD="dbpassword"
ARG ARG_MONGODBHOST=""
ARG ARG_TEMPPATH="/tmp/php_tmp"
ARG ARG_APIURL="http://localhost/AppController/commands_RSM/api/"
ARG ARG_MEDIAURL=""
ARG ARG_IMAGECACHE="/var/www/rsm_image_cache"
ARG ARG_FILECACHE="/var/www/rsm_file_cache"
ARG ARG_BLOWFISHKEY=""

RUN apt update -y && \
    apt upgrade -y && \
    apt-get install -y ca-certificates gnupg2

RUN echo "deb https://ppa.launchpadcontent.net/ondrej/php/ubuntu focal main" >> /etc/apt/sources.list && \
    echo "#deb-src https://ppa.launchpadcontent.net/ondrej/php/ubuntu focal main" >> /etc/apt/sources.list && \
    apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 4f4ea0aae5267a6c

RUN apt update -y && apt-get install -y \
    vim nano curl \
    rsyslog \
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
    php7.3-imagick

RUN mv /etc/rsyslog.conf /etc/rsyslog.conf.orig && \
    rm -f /etc/rsyslog.d/*.*
COPY ./configurations/rsyslog.conf /etc/rsyslog.conf

COPY ./configurations/rsyslog_rsm.conf /etc/rsyslog.d/rsm.conf

RUN mv /etc/nginx/nginx.conf /etc/nginx/nginx.conf.orig
COPY ./configurations/nginx.conf /etc/nginx/nginx.conf

RUN rm -rf /var/log/nginx && mkdir -p /var/log/nginx
RUN ln -s /dev/stdout /var/log/nginx/access.log
RUN ln -s /dev/stderr /var/log/nginx/error.log
RUN chmod u=rw,g=r,o=r /var/log/nginx && \
    chown -R www-data:www-data /var/log/nginx

COPY ./configurations/nginx_rsm.conf /etc/nginx/sites-available/rsm.conf

RUN ln -s /etc/nginx/sites-available/rsm.conf /etc/nginx/sites-enabled/rsm.conf && \
    rm -f /etc/nginx/sites-enabled/default

RUN mv /etc/php/7.3/fpm/php.ini /etc/php/7.3/fpm/php.ini.orig
COPY ./configurations/php.ini /etc/php/7.3/fpm/php.ini

RUN mv /etc/php/7.3/fpm/pool.d/www.conf /etc/php/7.3/fpm/pool.d/www.conf.orig
COPY ./configurations/www.conf /etc/php/7.3/fpm/pool.d/www.conf

RUN rm -rf /var/log/php-fpm && \
    mkdir -p /var/log/php-fpm && \
    ln -s /dev/stdout /var/log/php-fpm/access.log && \
    ln -s /dev/stderr /var/log/php-fpm/error.log && \
    chmod u=rw,g=r,o=r /var/log/php-fpm && \
    chown -R www-data: /var/log/php-fpm

RUN mkdir -p ${ARG_FILECACHE} && \
    mkdir -p ${ARG_IMAGECACHE} && \
    mkdir -p ${ARG_TEMPPATH}

COPY ./Server/htdocs/ /var/www/html/

RUN rm -f /var/www/html/index.nginx-debian.html && \
    find /var/www/html/ -type d -exec chmod u=rwx,g=rx,o=rx {} + && \
    find /var/www/html/ -type f -exec chmod u=rw,g=r,o=r {} + && \
    chown -R www-data:www-data /var/www

ENV DBHOST=$ARG_DBHOST
ENV DBNAME=$ARG_DBNAME
ENV DBUSERNAME=$ARG_DBUSERNAME
ENV DBPASSWORD=$ARG_DBPASSWORD
ENV MONGODBHOST=$ARG_MONGODBHOST
ENV TEMPPATH=$ARG_TEMPPATH
ENV APIURL=$ARG_APIURL
ENV MEDIAURL=$ARG_MEDIAURL
ENV IMAGECACHE=$ARG_IMAGECACHE
ENV FILECACHE=$ARG_FILECACHE
ENV BLOWFISHKEY=$ARG_BLOWFISHKEY

RUN php-fpm7.3 -t && nginx -t

EXPOSE 80

HEALTHCHECK --interval=60s --timeout=5s --start-period=60s --retries=3 CMD curl -f http://localhost/healthcheck || exit 1

COPY ./configurations/entrypoint.sh /entrypoint.sh
RUN chmod 744 /entrypoint.sh

ENV EXEC_RSYSLOG=0
ENV EXEC_PHPFPM=1
ENV EXEC_NGINX=1

ENTRYPOINT ["/entrypoint.sh"]

CMD [ "." ]
