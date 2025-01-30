#!/bin/bash

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}Iniciando instalação do PrivOnly...${NC}"

# Atualiza o sistema
echo -e "${YELLOW}Atualizando o sistema...${NC}"
sudo apt update && sudo apt upgrade -y

# Instala Apache, PHP e extensões necessárias
echo -e "${YELLOW}Instalando Apache, PHP e extensões...${NC}"
sudo apt install -y apache2 \
    php8.1 \
    php8.1-cli \
    php8.1-common \
    php8.1-curl \
    php8.1-mbstring \
    php8.1-xml \
    php8.1-zip \
    php8.1-json \
    unzip \
    git

# Habilita o mod_rewrite do Apache
sudo a2enmod rewrite

# Cria diretório do projeto
echo -e "${YELLOW}Configurando diretórios do projeto...${NC}"
sudo mkdir -p /var/www/html/privonly
sudo chown -R $USER:www-data /var/www/html/privonly

# Cria diretórios necessários
sudo mkdir -p /var/www/html/privonly/data
sudo mkdir -p /var/www/html/privonly/logs
sudo mkdir -p /var/www/html/privonly/img/profile
sudo mkdir -p /var/www/html/privonly/img/banners

# Configura permissões
sudo chmod -R 775 /var/www/html/privonly/data
sudo chmod -R 775 /var/www/html/privonly/logs
sudo chmod -R 775 /var/www/html/privonly/img
sudo chown -R www-data:www-data /var/www/html/privonly/data
sudo chown -R www-data:www-data /var/www/html/privonly/logs
sudo chown -R www-data:www-data /var/www/html/privonly/img

# Cria arquivo de configuração do Apache
echo -e "${YELLOW}Configurando Virtual Host do Apache...${NC}"
sudo tee /etc/apache2/sites-available/privonly.conf > /dev/null <<EOL
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    ServerName privonly.local
    DocumentRoot /var/www/html/privonly
    
    <Directory /var/www/html/privonly>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/privonly_error.log
    CustomLog \${APACHE_LOG_DIR}/privonly_access.log combined
</VirtualHost>
EOL

# Habilita o site e desabilita o default
sudo a2dissite 000-default.conf
sudo a2ensite privonly.conf

# Reinicia o Apache
sudo systemctl restart apache2

# Cria arquivo .htaccess
echo -e "${YELLOW}Criando arquivo .htaccess...${NC}"
tee /var/www/html/privonly/.htaccess > /dev/null <<EOL
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Proteção de diretórios
Options -Indexes

# Proteção do arquivo de configuração
<Files ".env">
    Order Allow,Deny
    Deny from all
</Files>

# Proteção dos logs
<Files "*.log">
    Order Allow,Deny
    Deny from all
</Files>
EOL

# Cria arquivo .env de exemplo
echo -e "${YELLOW}Criando arquivo .env...${NC}"
tee /var/www/html/privonly/.env.example > /dev/null <<EOL
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost

# Credenciais da API de Pagamento
PAYMENT_API_KEY=sua_chave_api
PAYMENT_API_SECRET=seu_secret
PAYMENT_API_URL=https://api.exemplo.com
EOL

# Adiciona entrada no /etc/hosts
echo -e "${YELLOW}Adicionando entrada no /etc/hosts...${NC}"
sudo tee -a /etc/hosts > /dev/null <<EOL
127.0.0.1   privonly.local
EOL

# Mensagem final
echo -e "${GREEN}Instalação concluída!${NC}"
echo -e "${YELLOW}Para começar:${NC}"
echo -e "1. Copie seus arquivos do projeto para: /var/www/html/privonly/"
echo -e "2. Copie .env.example para .env e configure suas credenciais"
echo -e "3. Acesse: http://privonly.local"
echo -e "\n${YELLOW}Logs do Apache:${NC}"
echo -e "- Erro: /var/log/apache2/privonly_error.log"
echo -e "- Acesso: /var/log/apache2/privonly_access.log"

# Verifica se Apache está rodando
if systemctl is-active --quiet apache2; then
    echo -e "\n${GREEN}Apache está rodando corretamente!${NC}"
else
    echo -e "\n${RED}Erro: Apache não está rodando. Verifique os logs.${NC}"
fi 