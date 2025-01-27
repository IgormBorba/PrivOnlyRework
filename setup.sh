#!/bin/bash

# Atualizar o sistema
sudo yum update -y

# Instalar Apache e PHP 8.1
sudo yum install -y httpd
sudo amazon-linux-extras enable php8.1
sudo yum clean metadata
sudo yum install -y php php-cli php-common php-json php-curl php-fpm php-mysqlnd php-zip php-gd php-mbstring php-xml

# Instalar Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

# Configurar Apache
sudo systemctl start httpd
sudo systemctl enable httpd

# Configurar permissões
sudo usermod -a -G apache ec2-user
sudo chown -R ec2-user:apache /var/www
sudo chmod 2775 /var/www
find /var/www -type d -exec sudo chmod 2775 {} \;
find /var/www -type f -exec sudo chmod 0664 {} \;

# Criar diretórios necessários
sudo mkdir -p /var/www/html/data
sudo mkdir -p /var/www/html/logs
sudo chmod 777 /var/www/html/data
sudo chmod 777 /var/www/html/logs 