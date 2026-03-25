#!/bin/bash
cron
/usr/bin/supervisord -n -c /etc/supervisord.conf &
php-fpm