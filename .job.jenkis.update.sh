# /bin/bash
# proceso de instalación 
git pull 
git submodule init 
git submodule update
pwd
composer dump-autoload
npm run prod
php artisan migrate
#php artisan db:seed
cp app/developer_docs/PostgresBuilder.php.example vendor/laravel/framework/src/Illuminate/Database/Schema/PostgresBuilder.php
php artisan serve &
#sudo systemctl restart httpd.service
