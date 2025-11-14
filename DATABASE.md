# 💾 Banco de Dados - Sistema Viviurka Contábil

## ✅ STATUS: CONFIGURADO E OPERACIONAL

---

## 📊 Informações Gerais

- **Tipo:** PostgreSQL 16
- **Host:** Configurado via variável de ambiente `DB_HOST`
- **Porta:** 5432 (padrão)
- **Database:** `viviurka_contabil` (configurável via `DB_DATABASE`)
- **Charset:** UTF-8
- **SSL Mode:** `prefer` (configurável via `DB_SSLMODE`)

---

## 🗄️ Estrutura das Tabelas

### 👥 Tabela: `users`
**Registros:** 4 (1 Master + 3 Clientes)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | integer | ID único do usuário |
| name | string | Nome completo |
| email | string | Email (único) |
| password | string | Senha (hash bcrypt) |
| role | enum | 'master' ou 'normal' |
| company_id | integer | ID da empresa (nullable) |
| created_at | timestamp | Data de criação |
| updated_at | timestamp | Data de atualização |

### 🏢 Tabela: `companies`
**Registros:** 10 empresas

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | integer | ID único da empresa |
| uuid | uuid | UUID único da empresa |
| razao_social | string | Razão social |
| nome_fantasia | string | Nome fantasia |
| cnpj | string | CNPJ (único) |
| email | string | Email da empresa |
| telefone | string | Telefone |
| whatsapp | string | WhatsApp |
| cep | string | CEP |
| endereco | string | Endereço completo |
| cidade | string | Cidade |
| estado | string | Estado |
| ativo | boolean | Status da empresa |
| responsavel_financeiro_nome | string | Nome do responsável financeiro |
| responsavel_financeiro_email | string | Email do responsável financeiro |
| responsavel_financeiro_telefone | string | Telefone do responsável financeiro |
| responsavel_financeiro_whatsapp | string | WhatsApp do responsável financeiro |
| created_at | timestamp | Data de criação |
| updated_at | timestamp | Data de atualização |
| deleted_at | timestamp | Soft delete |

### 📄 Tabela: `invoices`
**Registros:** Variável

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | integer | ID único da nota fiscal |
| company_id | integer | ID da empresa emissora |
| numero | string | Número da NFS-e |
| valor | decimal | Valor total |
| descricao | text | Descrição dos serviços |
| items | json | Array de itens |
| status | enum | 'pending', 'emitida', 'cancelada' |
| provider_id | string | ID no provedor externo |
| emitted_at | timestamp | Data de emissão |
| created_at | timestamp | Data de criação |
| updated_at | timestamp | Data de atualização |
| deleted_at | timestamp | Soft delete |

### 💰 Tabela: `boletos`
**Registros:** Variável

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | integer | ID único do boleto/cobrança |
| company_id | integer | ID da empresa |
| tipo_pagamento | enum | 'boleto', 'pix', 'credit_card' |
| valor | decimal | Valor da cobrança |
| vencimento | date | Data de vencimento |
| status | enum | 'pending', 'paid', 'overdue', 'cancelled' |
| descricao | text | Descrição da cobrança |
| provider_id | string | ID no provedor externo |
| chave_pix | string | Chave PIX (se PIX) |
| qr_code_pix | text | QR Code PIX (base64) |
| link_pagamento | string | Link de pagamento (se cartão) |
| url_pdf | string | URL do boleto PDF |
| linha_digitavel | string | Linha digitável do boleto |
| codigo_barras | string | Código de barras |
| dados_pagamento | json | Dados adicionais de pagamento |
| data_pagamento | timestamp | Data de pagamento |
| created_at | timestamp | Data de criação |
| updated_at | timestamp | Data de atualização |
| deleted_at | timestamp | Soft delete |

### 💳 Tabela: `payment_methods`
**Registros:** Variável

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | integer | ID único |
| company_id | integer | ID da empresa |
| type | enum | 'credit_card', 'boleto', 'pix' |
| status | enum | 'active', 'inactive' |
| provider_id | string | ID no provedor |
| card_last_digits | string | Últimos 4 dígitos do cartão |
| created_at | timestamp | Data de criação |
| updated_at | timestamp | Data de atualização |
| deleted_at | timestamp | Soft delete |

### 🔄 Tabela: `subscriptions`
**Registros:** Variável

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | integer | ID único |
| company_id | integer | ID da empresa |
| subscription_plan_id | integer | ID do plano de assinatura |
| payment_method_id | integer | ID do método de pagamento |
| valor | decimal | Valor da assinatura |
| frequencia | enum | 'monthly', 'quarterly', 'yearly' |
| status | enum | 'active', 'cancelled', 'suspended' |
| cnae_principal_id | integer | ID do CNAE principal |
| data_inicio | date | Data de início |
| data_fim | date | Data de término |
| proxima_cobranca | date | Próxima cobrança |
| created_at | timestamp | Data de criação |
| updated_at | timestamp | Data de atualização |
| deleted_at | timestamp | Soft delete |

### 📁 Tabela: `documents`
**Registros:** Variável

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | integer | ID único |
| company_id | integer | ID da empresa |
| user_id | integer | ID do usuário que fez upload |
| categoria | enum | Tipo do documento |
| nome_original | string | Nome original do arquivo |
| caminho | string | Caminho do arquivo |
| tamanho | integer | Tamanho em bytes |
| tipo_mime | string | Tipo MIME do arquivo |
| documento_chave | boolean | Se é documento chave |
| created_at | timestamp | Data de upload |
| updated_at | timestamp | Data de atualização |
| deleted_at | timestamp | Soft delete |

### 🤖 Tabela: `ai_requests`
**Registros:** Variável

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | integer | ID único |
| user_id | integer | ID do usuário |
| conversation_uuid | uuid | UUID da conversa |
| tipo | enum | 'chat', 'summarize', 'email' |
| prompt | text | Prompt enviado |
| response | text | Resposta da IA |
| tokens_used | integer | Tokens utilizados |
| model | string | Modelo usado |
| provider | string | Provedor de IA |
| cost | decimal | Custo da requisição |
| context | json | Contexto da conversa |
| uuid | uuid | UUID único da requisição |
| created_at | timestamp | Data de criação |
| updated_at | timestamp | Data de atualização |

### 📋 Tabela: `logs`
**Registros:** Variável

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | integer | ID único |
| user_id | integer | ID do usuário |
| company_id | integer | ID da empresa (nullable) |
| action | string | Ação realizada |
| resource_type | string | Tipo do recurso |
| resource_id | integer | ID do recurso |
| data | json | Dados adicionais |
| ip_address | string | IP do usuário |
| user_agent | string | User agent |
| created_at | timestamp | Data do evento |
| updated_at | timestamp | Data de atualização |

### 🔑 Tabela: `personal_access_tokens`
**Registros:** Variável (tokens ativos)

Gerenciada automaticamente pelo Laravel Sanctum para autenticação via tokens.

---

## 🔐 Usuários Mockados

### Master (Administrador)
```
Nome: Viviurka
Email: viviurka@contabil.com
Senha: password
Role: master
Empresa: null (acesso a todas)
```

### Clientes
```
1. João Silva
   Email: joao@example.com
   Senha: password
   Empresa: Tech Solutions Brasil Ltda

2. Maria Santos
   Email: maria@example.com
   Senha: password
   Empresa: Comercial Alimentos ABC Ltda

3. Pedro Oliveira
   Email: pedro@example.com
   Senha: password
   Empresa: Construtora Oliveira Engenharia Ltda
```

---

## 🏢 Empresas Mockadas

Total: 10 empresas criadas com CNPJs válidos

**Empresas com Usuários:**
1. Tech Solutions Brasil Ltda - CNPJ: 12.345.678/0001-90
2. Comercial Alimentos ABC Ltda - CNPJ: 98.765.432/0001-01
3. Construtora Oliveira Engenharia Ltda - CNPJ: 11.223.344/0001-55

**Empresas Disponíveis:**
- 7 empresas adicionais com CNPJs válidos
- Disponíveis para testes e vinculação

---

## 🛠️ Comandos Úteis

### Resetar Banco de Dados
```bash
php artisan migrate:fresh --seed
```

### Ver Status das Migrations
```bash
php artisan migrate:status
```

### Criar Nova Migration
```bash
php artisan make:migration create_nome_tabela
```

### Rodar Seeder Específico
```bash
php artisan db:seed --class=UserSeeder
```

### Acessar Tinker (Console Interativo)
```bash
php artisan tinker
```

### Backup do Banco
```bash
# Usando pg_dump
pg_dump -U postgres -d viviurka_contabil > backup.sql

# Ou usando Docker
docker-compose exec postgres pg_dump -U postgres viviurka_contabil > backup.sql
```

### Restore do Banco
```bash
# Usando psql
psql -U postgres -d viviurka_contabil < backup.sql

# Ou usando Docker
docker-compose exec -T postgres psql -U postgres viviurka_contabil < backup.sql
```

### Ver Informações do Banco
```bash
php artisan db:show
```

### Conectar ao PostgreSQL
```bash
# Usando psql
psql -U postgres -d viviurka_contabil

# Ou usando Docker
docker-compose exec postgres psql -U postgres -d viviurka_contabil
```

---

## 📊 Queries Úteis (Tinker)

### Contar Registros
```php
User::count()
Company::count()
Invoice::count()
```

### Listar Usuários Master
```php
User::where('role', 'master')->get(['name', 'email'])
```

### Listar Empresas Ativas
```php
Company::where('ativo', true)->get(['razao_social', 'cnpj'])
```

### Buscar Usuário por Email
```php
User::where('email', 'viviurka@contabil.com')->first()
```

### Criar Novo Usuário
```php
User::create([
    'name' => 'Novo Usuário',
    'email' => 'novo@example.com',
    'password' => Hash::make('senha123'),
    'role' => 'normal',
])
```

---

## 🔄 Relacionamentos

### User -> Company
```php
$user->company  // Retorna a empresa do usuário
```

### Company -> Users
```php
$company->users  // Retorna todos os usuários da empresa
```

### Company -> Invoices
```php
$company->invoices  // Retorna todas as notas fiscais
```

### Company -> Boletos
```php
$company->boletos  // Retorna todas as cobranças
```

---

## ⚠️ Importante

1. **Senhas**: Todas as senhas de teste são **"password"**
2. **PostgreSQL**: Banco de dados relacional robusto
3. **Soft Deletes**: Tabelas principais usam soft delete (não deletam fisicamente)
4. **Seeders**: Sempre recriam dados ao rodar `migrate:fresh --seed`
5. **Backup**: Faça backup regularmente do banco de dados
6. **SSL**: Use SSL para conexões em produção (`DB_SSLMODE=require`)
7. **Connection Pooling**: Configure connection pooling para alta performance
8. **Indexes**: Verifique se os índices estão criados corretamente

---

## 🚀 Status Atual

✅ **Banco Configurado**  
✅ **Migrations Executadas**  
✅ **Seeders Rodados**  
✅ **Login Funcionando**  
✅ **Sistema Operacional**  

---

## 🌐 Configuração do PostgreSQL

### Variáveis de Ambiente

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=viviurka_contabil
DB_USERNAME=postgres
DB_PASSWORD=postgres
DB_SSLMODE=prefer
```

### Conexão com Docker

```bash
# Conectar ao PostgreSQL
docker-compose exec postgres psql -U postgres -d viviurka_contabil

# Listar bancos de dados
docker-compose exec postgres psql -U postgres -c "\l"

# Listar tabelas
docker-compose exec postgres psql -U postgres -d viviurka_contabil -c "\dt"

# Ver estrutura de uma tabela
docker-compose exec postgres psql -U postgres -d viviurka_contabil -c "\d users"
```

### Backup e Restore

```bash
# Backup
docker-compose exec postgres pg_dump -U postgres viviurka_contabil > backup.sql

# Restore
docker-compose exec -T postgres psql -U postgres viviurka_contabil < backup.sql
```

---

**Última atualização:** 14 de Novembro de 2025
