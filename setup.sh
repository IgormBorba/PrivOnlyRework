#!/bin/bash

# Atualizar o sistema
sudo apt update -y
sudo apt upgrade -y

# Instalar Apache e PHP 8.1
sudo apt install -y apache2
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update -y
sudo apt install -y php8.1 php8.1-cli php8.1-common php8.1-json php8.1-curl php8.1-fpm php8.1-mysql php8.1-zip php8.1-gd php8.1-mbstring php8.1-xml

# Instalar Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

# Configurar Apache
sudo systemctl start apache2
sudo systemctl enable apache2

# Configurar permissões
sudo usermod -a -G www-data $USER
sudo chown -R $USER:www-data /var/www
sudo chmod 2775 /var/www
find /var/www -type d -exec sudo chmod 2775 {} \;
find /var/www -type f -exec sudo chmod 0664 {} \;

# Criar diretórios necessários
sudo mkdir -p /var/www/html/data
sudo mkdir -p /var/www/html/logs
sudo chmod 777 /var/www/html/data
sudo chmod 777 /var/www/html/logs

# Reiniciar Apache para aplicar as mudanças
sudo systemctl restart apache2