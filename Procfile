web: vendor/bin/heroku-php-nginx -C nginx_app.conf public/
worker: php artisan queue:work --queue=feeds,articles,digests,topics,default --sleep=3 --tries=3 --timeout=180 --no-interaction
scheduler: php artisan schedule:work
release: php artisan migrate --force && php artisan db:seed --class=DatabaseSeeder --force
