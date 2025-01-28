# Guia de Instalação - Sistema PrivOnly no Ubuntu

Este guia fornece instruções detalhadas para instalar e configurar o sistema PrivOnly em servidores Ubuntu, incluindo instâncias EC2.

## Pré-requisitos

- Ubuntu 22.04 LTS ou superior
- Acesso root/sudo
- Git instalado

## 1. Instalação Rápida (Script Automatizado)

```bash
# Clone o repositório
git clone [seu-repositorio]
cd [seu-diretorio]

# Execute o script de instalação
chmod +x setup.sh
./setup.sh
```

## 2. Instalação Manual Passo a Passo

### 2.1. Atualizar o Sistema

```bash
sudo apt update
sudo apt upgrade -y
```

### 2.2. Instalar Apache e PHP

```bash
# Adicionar repositório PHP
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Instalar Apache e PHP
sudo apt install -y apache2 php8.1 libapache2-mod-php8.1 php8.1-cli php8.1-common \
    php8.1-curl php8.1-mbstring php8.1-xml php8.1-zip php8.1-gd

# Instalar Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"
```

### 2.3. Configurar o Projeto

```bash
# Criar diretório do projeto (se não estiver usando git clone)
sudo mkdir -p /var/www/html
cd /var/www/html

# Se estiver clonando do git
git clone [seu-repositorio] .

# Instalar dependências
composer install

# Configurar ambiente
cp .env.example .env
# Edite o arquivo .env com suas configurações
nano .env

# Criar diretórios necessários
sudo mkdir -p data logs
sudo chmod 777 data logs

# Configurar permissões
sudo chown -R www-data:www-data /var/www/html/
sudo chmod -R 755 /var/www/html/
sudo chmod -R 777 /var/www/html/data /var/www/html/logs
```

### 2.4. Configurar Apache

```bash
# Criar arquivo de configuração do site
sudo nano /etc/apache2/sites-available/privonly.conf
```

Conteúdo do arquivo `privonly.conf`:
```apache
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    ServerName localhost
    
    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

```bash
# Ativar o site e desativar o padrão
sudo a2ensite privonly.conf
sudo a2dissite 000-default.conf

# Ativar módulos necessários
sudo a2enmod rewrite
sudo a2enmod php8.1

# Reiniciar Apache
sudo systemctl restart apache2
```

## 3. Solução de Problemas Comuns

### 3.1. Página Padrão do Apache Aparecendo

```bash
# Remover página padrão
sudo rm /var/www/html/index.html

# Verificar se os arquivos do projeto estão no lugar
ls -la /var/www/html/

# Reconfigurar permissões
sudo chown -R www-data:www-data /var/www/html/
sudo chmod -R 755 /var/www/html/
```

### 3.2. Erros de Permissão

```bash
# Configurar permissões completas
sudo chown -R www-data:www-data /var/www/html/
sudo chmod -R 755 /var/www/html/
sudo chmod -R 777 /var/www/html/data /var/www/html/logs
```

### 3.3. Apache Não Inicia

```bash
# Verificar status
sudo systemctl status apache2

# Verificar logs
sudo tail -f /var/log/apache2/error.log

# Reinstalar Apache e PHP (caso necessário)
sudo apt-get remove --purge apache2 php8.1 libapache2-mod-php8.1
sudo apt-get autoremove
sudo apt-get install apache2 php8.1 libapache2-mod-php8.1
```

### 3.4. Problemas com Módulo PHP

```bash
# Verificar se o módulo PHP está instalado e ativo
php -v
apache2ctl -M | grep php

# Reconfigurar PHP no Apache
sudo apt-get install --reinstall libapache2-mod-php8.1
sudo a2enmod php8.1
sudo systemctl restart apache2
```

## 4. Script de Instalação Completo

Crie um arquivo `install.sh`:

```bash
#!/bin/bash

# Atualizar sistema
sudo apt update
sudo apt upgrade -y

# Adicionar repositório PHP
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Instalar Apache e PHP
sudo apt install -y apache2 php8.1 libapache2-mod-php8.1 php8.1-cli php8.1-common \
    php8.1-curl php8.1-mbstring php8.1-xml php8.1-zip php8.1-gd

# Instalar Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

# Configurar projeto
cd /var/www/html
composer install
cp .env.example .env

# Criar diretórios e configurar permissões
sudo mkdir -p data logs
sudo chown -R www-data:www-data /var/www/html/
sudo chmod -R 755 /var/www/html/
sudo chmod -R 777 /var/www/html/data /var/www/html/logs

# Configurar Apache
sudo rm -f /etc/apache2/sites-available/000-default.conf
sudo tee /etc/apache2/sites-available/privonly.conf > /dev/null <<EOL
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    ServerName localhost
    
    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOL

# Ativar configurações
sudo a2ensite privonly.conf
sudo a2dissite 000-default.conf
sudo a2enmod rewrite
sudo a2enmod php8.1

# Reiniciar Apache
sudo systemctl restart apache2

echo "Instalação concluída! Acesse http://localhost"
```

## 5. Verificação da Instalação

Após a instalação, verifique:

1. Acesse http://localhost - deve mostrar a página inicial do sistema
2. Verifique os logs: `sudo tail -f /var/log/apache2/error.log`
3. Teste a conexão com a API: `curl -I http://localhost`

## 6. Segurança

1. Configure o firewall:
```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

2. Mantenha as permissões seguras:
- Apenas `data` e `logs` precisam de permissão 777
- Demais arquivos devem ter permissão 755
- Arquivos de configuração devem ter permissão 644

## 7. Backup e Manutenção

1. Backup dos dados:
```bash
sudo tar -czf backup.tar.gz /var/www/html/data /var/www/html/logs
```

2. Limpeza de logs:
```bash
sudo find /var/www/html/logs -type f -name "*.log" -mtime +30 -delete
```

## Suporte

Para suporte adicional, consulte:
- Logs do Apache: `/var/log/apache2/error.log`
- Logs do sistema: `/var/www/html/logs/`
- Status do serviço: `sudo systemctl status apache2` 