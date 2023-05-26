#!/bin/bash
set -e

if ! [ -z "${EXEC_RSYSLOG+x}" ]; then
	rsyslogd
fi

if ! [ -z "${EXEC_PHPFPM+x}" ]; then
	php-fpm7.3 --daemonize
fi

if ! [ -z "${EXEC_NGINX+x}" ]; then
	nginx -c /etc/nginx/nginx.conf -g 'daemon off;'
fi
