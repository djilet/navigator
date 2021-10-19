#!/bin/sh

ssh ubuntu@84.201.169.35 -i /var/www/monitoring/restart/id_rsa 'bash -s' < /var/www/monitoring/restart/remote-script.sh
