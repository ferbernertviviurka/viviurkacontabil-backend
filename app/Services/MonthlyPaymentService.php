<?php

namespace App\Services;

use App\Models\MonthlyPayment;
use App\Models\Subscription;
use App\Services\NotificationService;
use App\Services\ChargeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MonthlyPaymentService
{
    protected NotificationService $notificationService;
    protected ChargeService $chargeService;

    public function __construct(
        NotificationService $notificationService,
        ChargeService $chargeService
    ) {
        $this->notificationService = $notificationService;
        $this->chargeService = $chargeService;
    }

    /**
     * Generate monthly payments for active subscriptions.
     */
    public function generateMonthlyPayments(): int
    {
        $count = 0;
        $currentMonth = Carbon::now()->format('Y-m');
        $nextMonth = Carbon::now()->addMonth()->format('Y-m');

        // Get active subscriptions
        $subscriptions = Subscription::with(['company', 'paymentMethod'])
            ->where('status', 'active')
            ->get();

        foreach ($subscriptions as $subscription) {
            // Check if payment already exists for next month
            $existingPayment = MonthlyPayment::where('subscription_id', $subscription->id)
                ->where('mes_referencia', $nextMonth)
                ->first();

            if ($existingPayment) {
                continue;
            }

            // Calculate due date based on subscription's dia_vencimento
            $dueDate = Carbon::now()->addMonth()->day($subscription->dia_vencimento);

            // Create monthly payment
            $payment = MonthlyPayment::create([
                'subscription_id' => $subscription->id,
                'company_id' => $subscription->company_id,
                'mes_referencia' => $nextMonth,
                'valor' => $subscription->valor,
                'data_vencimento' => $dueDate,
                'status' => 'pending',
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Send payment reminders (5 days before due date).
     */
    public function sendPaymentReminders(): int
    {
        $count = 0;
        $reminderDate = Carbon::now()->addDays(5)->format('Y-m-d');

        // Get pending payments due in 5 days
        $payments = MonthlyPayment::with([
            'subscription' => function ($query) {
                $query->with(['company', 'paymentMethod']);
            },
            'company'
        ])
            ->where('status', 'pending')
            ->whereDate('data_vencimento', $reminderDate)
            ->where('email_enviado', false)
            ->get();

        foreach ($payments as $payment) {
            try {
                $this->sendPaymentNotification($payment);
                $payment->update([
                    'email_enviado' => true,
                    'email_enviado_em' => now(),
                ]);
                $count++;
            } catch (\Exception $e) {
                Log::error('Error sending payment reminder', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Send payment notification with appropriate payment method.
     */
    protected function sendPaymentNotification(MonthlyPayment $payment): void
    {
        // Reload with relationships
        $payment->load(['subscription.company', 'subscription.paymentMethod', 'company']);
        
        $subscription = $payment->subscription;
        $company = $payment->company;
        $paymentMethod = $subscription->paymentMethod ?? null;

        if (!$paymentMethod) {
            throw new \Exception('Payment method not found for subscription');
        }

        $email = $company->email;
        if (!$email) {
            throw new \Exception('Company email not found');
        }

        // Generate payment based on method
        $paymentData = match ($paymentMethod->tipo) {
            'boleto' => $this->generateBoletoPayment($payment),
            'pix' => $this->generatePixPayment($payment),
            'credit_card' => $this->generateCreditCardPayment($payment),
            default => throw new \Exception('Unsupported payment method: ' . $paymentMethod->tipo),
        };

        // Update payment with generated data
        $updateData = [
            'metodo_pagamento' => $paymentMethod->tipo,
            'dados_pagamento' => $paymentData,
        ];

        if (isset($paymentData['chave_pix'])) {
            $updateData['chave_pix'] = $paymentData['chave_pix'];
        }
        if (isset($paymentData['qr_code_pix'])) {
            $updateData['qr_code_pix'] = $paymentData['qr_code_pix'];
        }
        if (isset($paymentData['boleto_url'])) {
            $updateData['boleto_url'] = $paymentData['boleto_url'];
        }
        if (isset($paymentData['link_pagamento'])) {
            $updateData['link_pagamento'] = $paymentData['link_pagamento'];
        }

        $payment->update($updateData);

        // Send email notification
        $this->notificationService->sendEmail($email, [
            'payment' => $payment,
            'company' => $company,
            'subscription' => $subscription,
            'payment_data' => array_merge($paymentData, [
                'metodo_pagamento' => $paymentMethod->tipo,
            ]),
        ]);
    }

    /**
     * Generate boleto payment.
     */
    protected function generateBoletoPayment(MonthlyPayment $payment): array
    {
        // Use ChargeService to create boleto via Mercado Pago
        $boleto = $this->chargeService->createCharge([
            'company_id' => $payment->company_id,
            'tipo_pagamento' => 'boleto',
            'valor' => $payment->valor,
            'vencimento' => $payment->data_vencimento->format('Y-m-d'),
            'descricao' => "Mensalidade {$payment->mes_referencia} - {$payment->subscription->plano}",
        ]);

        return [
            'boleto_id' => $boleto->id,
            'boleto_url' => $boleto->url_pdf,
            'linha_digitavel' => $boleto->linha_digitavel,
            'provider_id' => $boleto->provider_id,
        ];
    }

    /**
     * Generate PIX payment.
     */
    protected function generatePixPayment(MonthlyPayment $payment): array
    {
        // Use ChargeService to create PIX via Mercado Pago
        $charge = $this->chargeService->createCharge([
            'company_id' => $payment->company_id,
            'tipo_pagamento' => 'pix',
            'valor' => $payment->valor,
            'vencimento' => $payment->data_vencimento->format('Y-m-d'),
            'descricao' => "Mensalidade {$payment->mes_referencia} - {$payment->subscription->plano}",
        ]);

        return [
            'charge_id' => $charge->id,
            'chave_pix' => $charge->chave_pix,
            'qr_code_pix' => $charge->qr_code_pix,
            'provider_id' => $charge->provider_id,
        ];
    }

    /**
     * Generate credit card payment link.
     */
    protected function generateCreditCardPayment(MonthlyPayment $payment): array
    {
        // Use ChargeService to create credit card payment link via Mercado Pago
        $charge = $this->chargeService->createCharge([
            'company_id' => $payment->company_id,
            'tipo_pagamento' => 'credit_card',
            'valor' => $payment->valor,
            'vencimento' => $payment->data_vencimento->format('Y-m-d'),
            'descricao' => "Mensalidade {$payment->mes_referencia} - {$payment->subscription->plano}",
        ]);

        return [
            'charge_id' => $charge->id,
            'link_pagamento' => $charge->link_pagamento,
            'provider_id' => $charge->provider_id,
        ];
    }


    /**
     * Mark payment as paid.
     */
    public function markAsPaid(MonthlyPayment $payment, array $data = []): void
    {
        $payment->update([
            'status' => 'paid',
            'data_pagamento' => now(),
            'metodo_pagamento' => $data['metodo_pagamento'] ?? $payment->metodo_pagamento,
            'dados_pagamento' => array_merge($payment->dados_pagamento ?? [], $data),
        ]);

        // Update subscription's pago_mes_atual
        $subscription = $payment->subscription;
        $mesesPagos = $subscription->meses_pagos ?? [];
        if (!in_array($payment->mes_referencia, $mesesPagos)) {
            $mesesPagos[] = $payment->mes_referencia;
        }
        
        $subscription->update([
            'pago_mes_atual' => true,
            'meses_pagos' => $mesesPagos,
        ]);
    }
}

