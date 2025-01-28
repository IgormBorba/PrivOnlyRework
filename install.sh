#!/bin/bash

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Função para log
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERRO:${NC} $1"
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] AVISO:${NC} $1"
}

# Verificar se está rodando como root
if [ "$EUID" -ne 0 ]; then 
    error "Este script precisa ser executado como root (sudo)"
    exit 1
fi

# Verificar sistema operacional
if ! grep -q "Ubuntu" /etc/os-release; then
    error "Este script foi projetado para Ubuntu"
    exit 1
fi

log "Iniciando instalação do sistema PrivOnly..."

# Atualizar sistema
log "Atualizando sistema..."
apt update
apt upgrade -y

# Instalar dependências básicas
log "Instalando dependências básicas..."
apt install -y software-properties-common curl git unzip

# Adicionar repositório PHP
log "Configurando repositório PHP..."
add-apt-repository ppa:ondrej/php -y
apt update

# Instalar Apache e PHP
log "Instalando Apache e PHP..."
apt install -y apache2 php8.1 libapache2-mod-php8.1 php8.1-cli php8.1-common \
    php8.1-curl php8.1-mbstring php8.1-xml php8.1-zip php8.1-gd

# Instalar Composer
log "Instalando Composer..."
if ! command -v composer &> /dev/null; then
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    php -r "unlink('composer-setup.php');"
fi

# Configurar diretório do projeto
log "Configurando diretório do projeto..."
cd /var/www/html || exit 1
rm -f index.html

# Se o diretório não está vazio e não é um repositório git
if [ "$(ls -A /var/www/html)" ] && [ ! -d "/var/www/html/.git" ]; then
    warning "Diretório /var/www/html não está vazio. Fazendo backup..."
    mkdir -p /var/www/html_backup
    mv /var/www/html/* /var/www/html_backup/
fi

# Copiar arquivos do projeto
log "Copiando arquivos do projeto..."
if [ -d "/tmp/privonly" ]; then
    cp -r /tmp/privonly/* /var/www/html/
    cp /tmp/privonly/.env.example /var/www/html/
    cp /tmp/privonly/.gitignore /var/www/html/ 2>/dev/null || true
fi

# Configurar ambiente
log "Configurando ambiente..."
if [ -f ".env.example" ]; then
    cp .env.example .env
    warning "Lembre-se de configurar o arquivo .env com suas credenciais"
fi

# Instalar dependências do Composer
log "Instalando dependências via Composer..."
composer install --no-interaction

# Criar diretórios necessários
log "Criando diretórios..."
mkdir -p data logs
chown -R www-data:www-data /var/www/html/
chmod -R 755 /var/www/html/
chmod -R 777 /var/www/html/data /var/www/html/logs

# Configurar Apache
log "Configurando Apache..."
cat > /etc/apache2/sites-available/privonly.conf << 'EOL'
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
EOL

# Ativar configurações do Apache
log "Ativando configurações do Apache..."
a2ensite privonly.conf
a2dissite 000-default.conf
a2enmod rewrite
a2enmod php8.1

# Configurar firewall
log "Configurando firewall..."
ufw allow 80/tcp
ufw allow 443/tcp
echo "y" | ufw enable

# Reiniciar Apache
log "Reiniciando Apache..."
systemctl restart apache2

# Verificar instalação
log "Verificando instalação..."
if systemctl is-active --quiet apache2; then
    log "Apache está rodando"
else
    error "Apache não está rodando. Verifique os logs em /var/log/apache2/error.log"
fi

if php -v > /dev/null 2>&1; then
    log "PHP está instalado corretamente"
else
    error "Problema com a instalação do PHP"
fi

# Instruções finais
log "Instalação concluída!"
echo -e "\n${GREEN}=== Próximos Passos ===${NC}"
echo "1. Configure o arquivo .env com suas credenciais"
echo "2. Acesse http://localhost para verificar a instalação"
echo "3. Verifique os logs em /var/log/apache2/error.log se encontrar problemas"
echo -e "\n${YELLOW}Para suporte, consulte INSTALL.md${NC}\n" 