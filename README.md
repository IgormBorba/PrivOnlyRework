# Sistema de Pagamentos PrivOnly

Sistema de processamento de pagamentos integrado com a API HyperCash Brasil.

## Requisitos

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
- `FASTSOFT_API_URL`: URL da API (geralmente não precisa ser alterada)

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

4. Clone o repositório e execute o script de setup:
```bash
git clone [seu-repositorio]
cd [seu-diretorio]
chmod +x setup.sh
./setup.sh
```

5. Configure as variáveis de ambiente:
```bash
cp .env.example .env
nano .env  # Configure suas credenciais
```

6. Mova os arquivos para o diretório web:
```bash
sudo cp -r ./* /var/www/html/
sudo cp privonly.conf /etc/httpd/conf.d/
sudo systemctl restart httpd
```

7. Configure as permissões:
```bash
sudo chown -R apache:apache /var/www/html/data
sudo chown -R apache:apache /var/www/html/logs
```

## Estrutura de Diretórios

```
.
├── admin/           # Área administrativa
├── data/           # Armazenamento de dados JSON
├── logs/           # Logs do sistema
├── styles/         # Arquivos CSS
├── img/            # Imagens
├── vendor/         # Dependências do Composer
├── index.php       # Página principal
└── payment_handler.php  # Processador de pagamentos
```

## Segurança

- Sempre use HTTPS em produção
- Mantenha as credenciais seguras no arquivo `.env`
- Faça backup regular dos diretórios `data/` e `logs/`
- Monitore os logs de erro do Apache e da aplicação

## Logs

O sistema mantém diferentes tipos de logs:
- `debug.txt`: Log geral de debug
- `logs/approved.log`: Transações aprovadas
- `logs/rejected.log`: Transações rejeitadas

## Monitoramento em Produção

Recomendamos configurar:
1. Amazon CloudWatch para logs
2. Alertas de erro críticos
3. Backup automático para Amazon S3

## Suporte

Para suporte, entre em contato com [seu-email] 