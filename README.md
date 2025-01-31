# Documentação Técnica - PrivOnly

## Descrição do Projeto
O PrivOnly é uma plataforma de conteúdo exclusivo inspirada no OnlyFans, desenvolvida com foco em processamento de pagamentos seguros e gestão de assinaturas recorrentes. O sistema possui duas interfaces principais:

1. **Frontend (Área do Cliente)**
   - Interface similar ao OnlyFans
   - Perfil da modelo com informações
   - Sistema de pagamento via cartão de crédito
   - Área de conteúdo exclusivo

2. **Backend (Painel Administrativo)**
   - Gestão de cobranças recorrentes semanais
   - Processamento de transações
   - Monitoramento de pagamentos
   - Logs de transações

## Tecnologias Utilizadas

### Backend
- **PHP 8.1+**
- Apache 2.4+
- Composer (Gerenciador de dependências)
- Firebase JWT (Autenticação)
- API HyperCash Brasil (Processamento de pagamentos)

### Frontend
- HTML5
- CSS3
- Bootstrap 5.1.3
- JavaScript

### Armazenamento
- Sistema de arquivos JSON para dados
- Logs estruturados

## Estrutura do Projeto

```
.
├── admin/           # Área administrativa
├── data/           # Dados JSON (transações e leads)
├── logs/           # Logs do sistema
├── styles/         # Arquivos CSS
├── img/            # Imagens
├── vendor/         # Dependências
├── index.php       # Página principal
├── payment_handler.php  # Processador de pagamentos
└── salvar_log.php  # Sistema de logging
```

## Funcionalidades Implementadas

### Sistema de Pagamentos
- Integração com API HyperCash Brasil
- Processamento de cartão de crédito
- Cobranças recorrentes semanais
- Sistema anti-fraude
- Logs de transações (aprovadas/rejeitadas)

### Área Administrativa
- Login seguro com sessões PHP
- Dashboard de transações
- Gestão de cobranças recorrentes
- Visualização de logs
- Relatórios de pagamentos

### Segurança
- Autenticação via JWT
- Variáveis de ambiente (.env)
- HTTPS obrigatório em produção
- Logs de segurança
- Sanitização de inputs
- Proteção contra SQL Injection
- Proteção contra XSS

## Requisitos do Sistema

- PHP 8.1 ou superior
- Apache 2.4 ou superior
- Composer
- Extensões PHP: curl, json, mbstring, xml

## Instalação Local

1. Clone o repositório:
```bash
git clone [seu-repositorio]
cd [seu-diretorio]
```

2. Instale as dependências via Composer:
```bash
composer install
```

3. Configure as variáveis de ambiente:
```bash
cp .env.example .env
```
Edite o arquivo `.env` e configure suas credenciais:
- `FASTSOFT_SECRET_KEY`: Sua chave secreta da HyperCash
- `FASTSOFT_API_URL`: URL da API

4. Configure as permissões dos diretórios:
```bash
mkdir -p data logs
chmod 777 data logs
```

## Instalação na AWS

1. Crie uma instância EC2 com Amazon Linux 2

2. Configure o Security Group:
   - HTTP (80)
   - HTTPS (443)
   - SSH (22)

3. Conecte-se à instância:
```bash
ssh -i sua-chave.pem ec2-user@seu-ip-ec2
```

4. Execute o script de setup:
```bash
chmod +x setup.sh
./setup.sh
```

5. Configure o ambiente:
```bash
cp .env.example .env
nano .env  # Configure suas credenciais
```

## Monitoramento e Logs

O sistema mantém diferentes tipos de logs:
- `debug.txt`: Log geral de debug
- `logs/approved.log`: Transações aprovadas
- `logs/rejected.log`: Transações rejeitadas

### Recomendações para Produção
1. Configurar Amazon CloudWatch para logs
2. Implementar alertas de erro críticos
3. Configurar backup automático para Amazon S3

## Armazenamento de Dados
- Sistema baseado em JSON
- Arquivos:
  - `data/transactions.json`: Registro de transações
  - `data/leads.json`: Informações de clientes

## Segurança e Boas Práticas
1. Credenciais sensíveis em arquivo .env
2. Backup automático recomendado
3. Monitoramento de logs
4. Validação de inputs
5. Proteção contra ataques comuns
6. Sessões seguras

## Manutenção
- Backup regular dos diretórios data/ e logs/
- Monitoramento de logs de erro
- Atualização regular das dependências
- Verificação periódica de segurança

## Considerações Técnicas
1. O sistema utiliza PHP moderno com práticas atuais
2. Integração robusta com gateway de pagamento
3. Sistema de logs detalhado para debugging
4. Arquitetura preparada para escalabilidade
5. Foco em segurança e proteção de dados

## Suporte

Para suporte técnico, entre em contato através dos canais:
- Email: [seu-email]
- Issues: GitHub Issues
- Discord: [seu-servidor]

## Atualização no Apache

Para atualizar o código no servidor Apache, siga os passos abaixo:

1. Crie os diretórios necessários e ajuste as permissões:
```bash
# Criar e configurar diretório de imagens de perfil
sudo mkdir -p /var/www/html/img/profile
sudo chmod -R 755 /var/www/html/img/profile
sudo chown -R www-data:www-data /var/www/html/img/profile

# Criar e configurar diretório de dados
sudo mkdir -p /var/www/html/data
sudo chmod -R 755 /var/www/html/data
sudo chown -R www-data:www-data /var/www/html/data
```

2. Copie os arquivos atualizados para o diretório do Apache:
```bash
# Copiar arquivos e ajustar permissões
sudo cp -r admin/ /var/www/html/
sudo cp index.php /var/www/html/
sudo chown -R www-data:www-data /var/www/html/admin/ /var/www/html/index.php
```

3. Reinicie o Apache para aplicar as alterações:
```bash
sudo systemctl restart apache2
```

### URLs de Acesso
- Página principal: `http://localhost/`
- Painel administrativo: `http://localhost/admin/`
- Visualização de cartões e usuários: `http://localhost/admin/view_cards_and_users.php`
- Edição de perfil: `http://localhost/admin/edit_profile.php`

### Observações Importantes
1. Os diretórios `/var/www/html/data` e `/var/www/html/img/profile` precisam ter permissões de escrita para o Apache (www-data).
2. Todos os arquivos devem pertencer ao usuário www-data para funcionamento correto.
3. Após alterações significativas, é recomendado reiniciar o Apache.
4. Mantenha backups dos arquivos em `/var/www/html/data` antes de atualizações. 