<?php

namespace App\Services;

use App\Contracts\PaymentProviderInterface;
use App\Models\Subscription;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    protected PaymentProviderInterface $paymentProvider;

    public function __construct(PaymentProviderInterface $paymentProvider)
    {
        $this->paymentProvider = $paymentProvider;
    }

    /**
     * Validate subscription data.
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function validateSubscriptionData(array $data): array
    {
        $rules = [
            'company_id' => 'required|exists:companies,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'plano' => 'required|string',
            'recorrencia' => 'required|in:mensal,trimestral,semestral,anual',
            'valor' => 'required|numeric|min:0',
            'dia_vencimento' => 'required|integer|min:1|max:28',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Create a subscription.
     *
     * @param array $data
     * @return Subscription
     */
    public function createSubscription(array $data): Subscription
    {
        // Calculate next charge date
        $nextChargeDate = $this->calculateNextChargeDate($data['dia_vencimento']);

        // Create subscription in payment provider
        $providerResponse = $this->paymentProvider->createSubscription([
            'amount' => $data['valor'],
            'payment_type' => 'boleto', // or get from payment_method
            'next_due_date' => $nextChargeDate,
            'description' => $data['plano'],
        ]);

        // Create subscription record
        $subscription = Subscription::create([
            'company_id' => $data['company_id'],
            'payment_method_id' => $data['payment_method_id'],
            'plano' => $data['plano'],
            'recorrencia' => $data['recorrencia'] ?? 'mensal',
            'valor' => $data['valor'],
            'dia_vencimento' => $data['dia_vencimento'],
            'status' => 'active',
            'proxima_cobranca' => $nextChargeDate,
            'provider_subscription_id' => $providerResponse['subscription_id'] ?? null,
            'pago_mes_atual' => false,
            'meses_pagos' => [],
        ]);

        return $subscription;
    }

    /**
     * Cancel a subscription.
     *
     * @param Subscription $subscription
     * @return bool
     */
    public function cancelSubscription(Subscription $subscription): bool
    {
        if ($subscription->provider_subscription_id) {
            $this->paymentProvider->cancelSubscription($subscription->provider_subscription_id);
        }

        $subscription->update([
            'status' => 'cancelled',
            'data_cancelamento' => now(),
        ]);

        return true;
    }

    /**
     * Calculate next charge date based on due day.
     *
     * @param int $dueDay
     * @return string
     */
    private function calculateNextChargeDate(int $dueDay): string
    {
        $now = now();
        $nextCharge = $now->copy()->day($dueDay);

        // If the due day has passed this month, move to next month
        if ($nextCharge->isPast()) {
            $nextCharge->addMonth();
        }

        return $nextCharge->format('Y-m-d');
    }
}

