#!/bin/sh

cd /var/www
sleep 30s
php artisan migrate:fresh --seed
php artisan serve --host=0.0.0.0 --port=8000
