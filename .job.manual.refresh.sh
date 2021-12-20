# /bin/bash
# proceso de instalación 
git pull 
git submodule init 
git submodule update
pwd
composer install
npm install
php artisan migrate:refresh
php artisan db:seed
cp app/developer_docs/PostgresBuilder.php.example vendor/laravel/framework/src/Illuminate/Database/Schema/PostgresBuilder.php
php artisan serve &
#sudo systemctl restart httpd.service
