<?php

namespace App\Services;

use App\Models\Boleto;
use App\Models\Company;
use App\Contracts\PaymentProviderInterface;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class ChargeService
{
    protected PaymentProviderInterface $paymentProvider;
    protected NotificationService $notificationService;

    public function __construct(
        PaymentProviderInterface $paymentProvider,
        NotificationService $notificationService
    ) {
        $this->paymentProvider = $paymentProvider;
        $this->notificationService = $notificationService;
    }

    /**
     * Create a charge (boleto, PIX, or credit card).
     */
    public function createCharge(array $data): Boleto
    {
        $tipoPagamento = $data['tipo_pagamento'] ?? 'boleto';
        $company = Company::findOrFail($data['company_id']);

        // Create boleto record (we use boleto table for all charge types)
        $boleto = Boleto::create([
            'company_id' => $data['company_id'],
            'tipo_pagamento' => $tipoPagamento,
            'valor' => $data['valor'],
            'vencimento' => $data['vencimento'],
            'descricao' => $data['descricao'] ?? null,
            'status' => 'pending',
        ]);

        // Generate payment based on type via Mercado Pago
        try {
            $paymentData = match ($tipoPagamento) {
                'boleto' => $this->generateBoleto($boleto),
                'pix' => $this->generatePix($boleto),
                'credit_card' => $this->generateCreditCard($boleto, $data),
                default => throw new \Exception('Tipo de pagamento inválido'),
            };

            // Update boleto with payment data
            $updateData = [
                'provider_id' => $paymentData['provider_id'] ?? null,
                'chave_pix' => $paymentData['chave_pix'] ?? null,
                'qr_code_pix' => $paymentData['qr_code_pix'] ?? null,
                'link_pagamento' => $paymentData['link_pagamento'] ?? null,
                'url_pdf' => $paymentData['url_pdf'] ?? null,
                'linha_digitavel' => $paymentData['linha_digitavel'] ?? null,
                'codigo_barras' => $paymentData['codigo_barras'] ?? null,
            ];
            
            // Store dados_pagamento as JSON
            if (!empty($paymentData)) {
                $updateData['dados_pagamento'] = json_encode($paymentData);
            }
            
            $boleto->update($updateData);
        } catch (\Exception $e) {
            Log::error('Erro ao gerar pagamento', [
                'error' => $e->getMessage(),
                'boleto_id' => $boleto->id,
                'tipo_pagamento' => $tipoPagamento,
            ]);
            
            $boleto->update([
                'status' => 'error',
                'provider_response' => ['error' => $e->getMessage()],
            ]);
            
            throw $e;
        }

        // Send notification to financial responsible
        $this->sendChargeNotification($boleto, $company);

        return $boleto->fresh();
    }

    /**
     * Generate boleto via Mercado Pago.
     */
    protected function generateBoleto(Boleto $boleto): array
    {
        $company = $boleto->company;
        
        // Create customer in Mercado Pago if needed
        $customerData = [
            'email' => $company->responsavel_financeiro_email ?? $company->email,
            'name' => $company->responsavel_financeiro_nome ?? $company->razao_social,
            'cpf_cnpj' => $company->cnpj,
            'phone' => $company->responsavel_financeiro_telefone ?? $company->telefone,
            'zip_code' => $company->cep,
            'street' => $company->endereco,
            'city' => $company->cidade,
            'state' => $company->estado,
        ];

        $customerResult = $this->paymentProvider->createCustomer($customerData);
        $customerId = $customerResult['customer_id'] ?? null;

        // Create charge via Mercado Pago
        $chargeData = [
            'type' => 'boleto',
            'amount' => $boleto->valor,
            'description' => $boleto->descricao ?? "Cobrança #{$boleto->id}",
            'due_date' => $boleto->vencimento->format('Y-m-d'),
            'customer_id' => $customerId,
            'payer_email' => $company->responsavel_financeiro_email ?? $company->email,
            'payer_name' => $company->responsavel_financeiro_nome ?? $company->razao_social,
            'payer_document' => $company->cnpj,
        ];

        $chargeResult = $this->paymentProvider->createCharge($chargeData);

        if ($chargeResult['success']) {
            return [
                'provider_id' => $chargeResult['charge_id'] ?? null,
                'url_pdf' => $chargeResult['url_pdf'] ?? null,
                'linha_digitavel' => $chargeResult['linha_digitavel'] ?? null,
                'codigo_barras' => $chargeResult['codigo_barras'] ?? null,
            ];
        }

        throw new \Exception('Erro ao gerar boleto: ' . ($chargeResult['error'] ?? 'Erro desconhecido'));
    }

    /**
     * Generate PIX via Mercado Pago.
     */
    protected function generatePix(Boleto $boleto): array
    {
        $company = $boleto->company;
        
        // Create customer in Mercado Pago if needed
        $customerData = [
            'email' => $company->responsavel_financeiro_email ?? $company->email,
            'name' => $company->responsavel_financeiro_nome ?? $company->razao_social,
            'cpf_cnpj' => $company->cnpj,
            'phone' => $company->responsavel_financeiro_telefone ?? $company->telefone,
        ];

        $customerResult = $this->paymentProvider->createCustomer($customerData);
        $customerId = $customerResult['customer_id'] ?? null;

        // Create PIX charge via Mercado Pago
        $chargeData = [
            'type' => 'pix',
            'amount' => $boleto->valor,
            'description' => $boleto->descricao ?? "Cobrança PIX #{$boleto->id}",
            'due_date' => $boleto->vencimento->format('Y-m-d'),
            'customer_id' => $customerId,
            'payer_email' => $company->responsavel_financeiro_email ?? $company->email,
            'payer_name' => $company->responsavel_financeiro_nome ?? $company->razao_social,
            'payer_document' => $company->cnpj,
        ];

        $chargeResult = $this->paymentProvider->createCharge($chargeData);

        if ($chargeResult['success']) {
            return [
                'provider_id' => $chargeResult['charge_id'] ?? null,
                'chave_pix' => $chargeResult['chave_pix'] ?? null,
                'qr_code_pix' => $chargeResult['qr_code_pix'] ?? null,
            ];
        }

        throw new \Exception('Erro ao gerar PIX: ' . ($chargeResult['error'] ?? 'Erro desconhecido'));
    }

    /**
     * Generate credit card payment link via Mercado Pago.
     */
    protected function generateCreditCard(Boleto $boleto, array $data): array
    {
        $company = $boleto->company;
        
        // Create customer in Mercado Pago if needed
        $customerData = [
            'email' => $company->responsavel_financeiro_email ?? $company->email,
            'name' => $company->responsavel_financeiro_nome ?? $company->razao_social,
            'cpf_cnpj' => $company->cnpj,
            'phone' => $company->responsavel_financeiro_telefone ?? $company->telefone,
        ];

        $customerResult = $this->paymentProvider->createCustomer($customerData);
        $customerId = $customerResult['customer_id'] ?? null;

        // Create preference (checkout) for credit card payment
        $chargeData = [
            'type' => 'credit_card',
            'amount' => $boleto->valor,
            'description' => $boleto->descricao ?? "Cobrança #{$boleto->id}",
            'due_date' => $boleto->vencimento->format('Y-m-d'),
            'customer_id' => $customerId,
            'payer_email' => $company->responsavel_financeiro_email ?? $company->email,
            'payer_name' => $company->responsavel_financeiro_nome ?? $company->razao_social,
            'payer_document' => $company->cnpj,
        ];

        $chargeResult = $this->paymentProvider->createCharge($chargeData);

        if ($chargeResult['success']) {
            return [
                'provider_id' => $chargeResult['charge_id'] ?? null,
                'link_pagamento' => $chargeResult['link_pagamento'] ?? null,
            ];
        }

        throw new \Exception('Erro ao gerar link de pagamento: ' . ($chargeResult['error'] ?? 'Erro desconhecido'));
    }


    /**
     * Send charge notification to financial responsible.
     */
    protected function sendChargeNotification(Boleto $boleto, Company $company): void
    {
        // Reload boleto to ensure we have all payment data
        $boleto->refresh();
        
        // Use company email or financial responsible email
        $email = $company->responsavel_financeiro_email ?? $company->email;
        
        // Send email if available
        if ($email) {
            try {
                $this->notificationService->sendEmail($email, [
                    'boleto' => $boleto,
                    'company' => $company,
                ]);
                Log::info('Charge notification email sent', [
                    'boleto_id' => $boleto->id,
                    'email' => $email,
                    'tipo_pagamento' => $boleto->tipo_pagamento,
                ]);
            } catch (\Exception $e) {
                Log::error('Error sending charge notification email', [
                    'boleto_id' => $boleto->id,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Send WhatsApp if available
        $whatsapp = $company->responsavel_financeiro_whatsapp ?? $company->telefone;
        if ($whatsapp) {
            try {
                $message = $this->buildChargeMessage($boleto, $company);
                $this->notificationService->sendWhatsApp($whatsapp, $message);
                Log::info('Charge notification WhatsApp sent', [
                    'boleto_id' => $boleto->id,
                    'whatsapp' => $whatsapp,
                ]);
            } catch (\Exception $e) {
                Log::error('Error sending charge notification WhatsApp', [
                    'boleto_id' => $boleto->id,
                    'whatsapp' => $whatsapp,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Build charge message for WhatsApp.
     */
    protected function buildChargeMessage(Boleto $boleto, Company $company): string
    {
        $valor = number_format($boleto->valor, 2, ',', '.');
        $vencimento = $boleto->vencimento->format('d/m/Y');
        $tipo = match ($boleto->tipo_pagamento) {
            'boleto' => 'Boleto Bancário',
            'pix' => 'PIX',
            'credit_card' => 'Cartão de Crédito',
            default => 'Cobrança',
        };

        $nomeResponsavel = $company->responsavel_financeiro_nome ?? $company->razao_social;
        $message = "Olá {$nomeResponsavel}!\n\n";
        $message .= "Uma nova cobrança foi criada para {$company->razao_social}:\n\n";
        $message .= "💰 Valor: R$ {$valor}\n";
        $message .= "📅 Vencimento: {$vencimento}\n";
        $message .= "💳 Forma de Pagamento: {$tipo}\n";
        if ($boleto->descricao) {
            $message .= "📝 Descrição: {$boleto->descricao}\n";
        }
        $message .= "\n";

        if ($boleto->tipo_pagamento === 'pix') {
            if ($boleto->chave_pix) {
                $message .= "🔑 Chave PIX (Copia e Cola):\n{$boleto->chave_pix}\n\n";
            }
            $message .= "📧 QR Code e mais detalhes disponíveis no email.\n";
        } elseif ($boleto->tipo_pagamento === 'boleto') {
            if ($boleto->linha_digitavel) {
                $message .= "📄 Linha Digitável: {$boleto->linha_digitavel}\n\n";
            }
            $message .= "📧 Boleto em PDF e mais detalhes disponíveis no email.\n";
        } elseif ($boleto->tipo_pagamento === 'credit_card') {
            if ($boleto->link_pagamento) {
                $message .= "🔗 Link para pagamento: {$boleto->link_pagamento}\n\n";
            }
            $message .= "📧 Mais detalhes disponíveis no email.\n";
        }

        $message .= "\n";
        $message .= "⚠️ Esta cobrança vence em {$vencimento}. Realize o pagamento até a data de vencimento.";

        return $message;
    }

    /**
     * Process payment and send notification (public method to be called from controller).
     */
    public function processPayment(Boleto $boleto): bool
    {
        try {
            $company = $boleto->company;
            $this->sendChargeNotification($boleto, $company);
            return true;
        } catch (\Exception $e) {
            Log::error('Error processing payment notification', [
                'boleto_id' => $boleto->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

