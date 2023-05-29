#!/bin/bash
set -e

[ "$EXEC_RSYSLOG" = "1" ] && rsyslogd

[ "$EXEC_PHPFPM" = "1" ] && php-fpm7.3 --daemonize

[ "$EXEC_NGINX" = "1" ] && nginx -c /etc/nginx/nginx.conf -g 'daemon off;'
