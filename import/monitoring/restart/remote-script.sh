#!/bin/sh

sudo service apache2 restart
echo "$(date -Iseconds) Apache restarted" >> /var/www/html/var/log/monitoring.log
