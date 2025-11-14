# Cron Jobs - Mensalidades

Este documento descreve os comandos artisan que devem ser configurados no cron job do servidor.

## Comandos Disponíveis

### 1. Gerar Mensalidades
Gera mensalidades para o próximo mês para todas as assinaturas ativas.

```bash
php artisan payments:generate
```

**Quando executar:** No último dia de cada mês (ex: dia 30/31)

**Cron:** `0 0 28-31 * *` (executa no último dia do mês à meia-noite)

### 2. Enviar Lembretes de Pagamento
Envia emails de lembrete 5 dias antes do vencimento.

```bash
php artisan payments:send-reminders
```

**Quando executar:** Diariamente

**Cron:** `0 9 * * *` (executa todos os dias às 9h)

## Configuração no Servidor

Adicione ao crontab do servidor:

```bash
# Gerar mensalidades no último dia do mês
0 0 28-31 * * cd /caminho/para/projeto && php artisan payments:generate >> /dev/null 2>&1

# Enviar lembretes diariamente
0 9 * * * cd /caminho/para/projeto && php artisan payments:send-reminders >> /dev/null 2>&1
```

## Teste Manual

Para testar os comandos manualmente:

```bash
# Gerar mensalidades
php artisan payments:generate

# Enviar lembretes
php artisan payments:send-reminders
```

