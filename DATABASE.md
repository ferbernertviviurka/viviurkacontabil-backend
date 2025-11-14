# 💾 Banco de Dados - Sistema Viviurka Contábil

## ✅ STATUS: CONFIGURADO E OPERACIONAL

---

## 📊 Informações Gerais

- **Tipo:** SQLite 3.51.0
- **Arquivo:** `database/database.sqlite`
- **Tamanho:** 152KB
- **Total de Tabelas:** 18
- **Charset:** UTF-8

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
| name | string | Razão social |
| cnpj | string | CNPJ (único) |
| email | string | Email da empresa |
| phone | string | Telefone |
| address | text | Endereço completo |
| status | enum | 'active' ou 'inactive' |
| created_at | timestamp | Data de criação |
| updated_at | timestamp | Data de atualização |
| deleted_at | timestamp | Soft delete |

### 📄 Tabela: `invoices`
**Registros:** 0 (vazio)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | integer | ID único da nota fiscal |
| company_id | integer | ID da empresa emissora |
| number | string | Número da NFS-e |
| amount | decimal | Valor total |
| description | text | Descrição dos serviços |
| items | json | Array de itens |
| status | enum | 'pending', 'issued', 'cancelled' |
| provider_id | string | ID no provedor externo |
| issued_at | timestamp | Data de emissão |
| created_at | timestamp | Data de criação |
| updated_at | timestamp | Data de atualização |
| deleted_at | timestamp | Soft delete |

### 💰 Tabela: `boletos`
**Registros:** 0 (vazio)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | integer | ID único do boleto |
| company_id | integer | ID da empresa |
| amount | decimal | Valor do boleto |
| due_date | date | Data de vencimento |
| status | enum | 'pending', 'paid', 'cancelled' |
| barcode | string | Código de barras |
| provider_id | string | ID no provedor externo |
| paid_at | timestamp | Data de pagamento |
| created_at | timestamp | Data de criação |
| updated_at | timestamp | Data de atualização |
| deleted_at | timestamp | Soft delete |

### 💳 Tabela: `payment_methods`
**Registros:** 0 (vazio)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | integer | ID único |
| company_id | integer | ID da empresa |
| type | enum | 'credit_card', 'boleto' |
| status | enum | 'active', 'inactive' |
| provider_id | string | ID no provedor |
| card_last_digits | string | Últimos 4 dígitos do cartão |
| created_at | timestamp | Data de criação |
| updated_at | timestamp | Data de atualização |
| deleted_at | timestamp | Soft delete |

### 🔄 Tabela: `subscriptions`
**Registros:** 0 (vazio)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | integer | ID único |
| company_id | integer | ID da empresa |
| payment_method_id | integer | ID do método de pagamento |
| amount | decimal | Valor da assinatura |
| frequency | enum | 'monthly', 'quarterly', 'yearly' |
| status | enum | 'active', 'cancelled', 'suspended' |
| next_charge_at | timestamp | Próxima cobrança |
| created_at | timestamp | Data de criação |
| updated_at | timestamp | Data de atualização |
| deleted_at | timestamp | Soft delete |

### 📁 Tabela: `documents`
**Registros:** 0 (vazio)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | integer | ID único |
| company_id | integer | ID da empresa |
| user_id | integer | ID do usuário que fez upload |
| name | string | Nome do arquivo |
| type | string | Tipo do documento |
| path | string | Caminho do arquivo |
| size | integer | Tamanho em bytes |
| created_at | timestamp | Data de upload |
| updated_at | timestamp | Data de atualização |
| deleted_at | timestamp | Soft delete |

### 🤖 Tabela: `ai_requests`
**Registros:** 0 (vazio)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | integer | ID único |
| user_id | integer | ID do usuário |
| type | enum | 'summarize', 'email', 'suggestion' |
| prompt | text | Prompt enviado |
| response | text | Resposta da IA |
| created_at | timestamp | Data de criação |
| updated_at | timestamp | Data de atualização |

### 📋 Tabela: `logs`
**Registros:** 0 (vazio)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | integer | ID único |
| user_id | integer | ID do usuário |
| company_id | integer | ID da empresa (nullable) |
| action | string | Ação realizada |
| description | text | Descrição detalhada |
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
cp database/database.sqlite database/backup_$(date +%Y%m%d_%H%M%S).sqlite
```

### Ver Informações do Banco
```bash
php artisan db:show
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
Company::where('status', 'active')->get(['name', 'cnpj'])
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
$company->boletos  // Retorna todos os boletos
```

---

## ⚠️ Importante

1. **Senhas**: Todas as senhas de teste são **"password"**
2. **SQLite**: Banco de dados em arquivo único
3. **Soft Deletes**: Tabelas principais usam soft delete (não deletam fisicamente)
4. **Seeders**: Sempre recriam dados ao rodar `migrate:fresh --seed`
5. **Backup**: Faça backup antes de resetar o banco

---

## 🚀 Status Atual

✅ **Banco Configurado**  
✅ **Migrations Executadas**  
✅ **Seeders Rodados**  
✅ **Login Funcionando**  
✅ **Sistema Operacional**  

---

**Última atualização:** 13 de Novembro de 2025

