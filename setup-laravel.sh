#!/bin/bash

echo "--------------------------------------"
echo "CONFIGURANDO AMBIENTE LARAVEL DOCKER"
echo "--------------------------------------"

echo "Instalando dependencias PHP..."

docker exec laravel_php bash -c "

apt-get update

apt-get install -y \
git \
curl \
zip \
unzip \
libpng-dev \
libonig-dev \
libxml2-dev \
libzip-dev \
default-mysql-client

docker-php-ext-install \
pdo_mysql \
mbstring \
exif \
pcntl \
bcmath \
gd \
zip

pecl install redis
docker-php-ext-enable redis

"

echo "--------------------------------------"
echo "Criando projeto Laravel"
echo "--------------------------------------"

docker exec laravel_php bash -c "
cd /var/www
composer create-project laravel/laravel .
"

echo "--------------------------------------"
echo "Configurando permissões"
echo "--------------------------------------"

docker exec laravel_php bash -c "
cd /var/www
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
chmod -R 775 storage
chmod -R 775 bootstrap/cache
"

echo "--------------------------------------"
echo "Configurando banco"
echo "--------------------------------------"

docker exec laravel_php bash -c "
cd /var/www
sed -i 's/DB_DATABASE=.*/DB_DATABASE=zeControl/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=admin/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=admin/' .env
sed -i 's/DB_HOST=.*/DB_HOST=mysql/' .env
"

echo "--------------------------------------"
echo "Gerando chave da aplicação"
echo "--------------------------------------"

docker exec laravel_php bash -c "
cd /var/www
php artisan key:generate
"

echo "--------------------------------------"
echo "Rodando migrations"
echo "--------------------------------------"

docker exec laravel_php bash -c "
cd /var/www
php artisan migrate
"

echo "--------------------------------------"
echo "Testando Redis"
echo "--------------------------------------"

docker exec laravel_redis redis-cli ping

echo "--------------------------------------"
echo "AMBIENTE PRONTO"
echo "--------------------------------------"

echo "Abra no navegador:"
echo "http://localhost:8080"
