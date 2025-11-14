<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentProviderInterface;
use App\Models\Subscription;
use App\Models\Boleto;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
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
     * Handle payment provider webhooks.
     *
     * POST /api/webhooks/payment
     */
    public function handlePayment(Request $request)
    {
        try {
            $payload = $request->all();

            Log::info('Webhook received', ['payload' => $payload]);

            // Process webhook through provider
            $result = $this->paymentProvider->processWebhook($payload);

            if (!$result['success']) {
                return response()->json(['error' => 'Failed to process webhook'], 400);
            }

            // Handle different webhook events
            $event = $result['event'] ?? null;
            $data = $result['data'] ?? $payload;

            // Handle Mercado Pago webhook events
            // Events: payment.created, payment.updated, payment.approved, payment.rejected, etc.
            if (str_contains($event, 'payment.updated') || str_contains($event, 'payment.approved')) {
                // Check payment status from payload
                if (isset($data['status']) && $data['status'] === 'approved') {
                    $this->handlePaymentReceived($data);
                }
            } elseif (str_contains($event, 'payment.received') || str_contains($event, 'PAYMENT_RECEIVED')) {
                $this->handlePaymentReceived($data);
            } elseif (str_contains($event, 'payment.overdue') || str_contains($event, 'PAYMENT_OVERDUE')) {
                $this->handlePaymentOverdue($data);
            } elseif (str_contains($event, 'subscription.cancelled') || str_contains($event, 'SUBSCRIPTION_CANCELLED')) {
                $this->handleSubscriptionCancelled($data);
            } else {
                Log::info('Unhandled webhook event', ['event' => $event, 'data' => $data]);
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle payment received event.
     *
     * @param array $data
     * @return void
     */
    private function handlePaymentReceived(array $data): void
    {
        Log::info('Payment received', ['data' => $data]);

        // Handle Mercado Pago webhook structure
        // Mercado Pago sends: { "type": "payment", "action": "payment.updated", "data": { "id": "123456" } }
        $providerId = $data['payment_id'] ?? $data['id'] ?? ($data['data']['id'] ?? null);

        if ($providerId) {
            // Find boleto by provider_id
            $boleto = Boleto::where('provider_id', (string) $providerId)->first();

            if ($boleto) {
                $boleto->update([
                    'status' => 'paid',
                    'data_pagamento' => now(),
                ]);

                // Notify master users
                $this->notifyMasters('Cobrança Paga', [
                    'message' => "A cobrança #{$boleto->id} da empresa {$boleto->company->razao_social} foi paga.",
                    'boleto' => $boleto,
                    'company' => $boleto->company,
                ]);
            }
        }
    }

    /**
     * Handle payment overdue event.
     *
     * @param array $data
     * @return void
     */
    private function handlePaymentOverdue(array $data): void
    {
        Log::info('Payment overdue', ['data' => $data]);

        // Handle Mercado Pago webhook structure
        $providerId = $data['payment_id'] ?? $data['id'] ?? ($data['data']['id'] ?? null);

        if ($providerId) {
            // Find boleto by provider_id
            $boleto = Boleto::where('provider_id', (string) $providerId)->first();

            if ($boleto && $boleto->status !== 'paid') {
                $boleto->update([
                    'status' => 'overdue',
                ]);

                // Notify master users
                $this->notifyMasters('Cobrança Vencida', [
                    'message' => "A cobrança #{$boleto->id} da empresa {$boleto->company->razao_social} venceu.",
                    'boleto' => $boleto,
                    'company' => $boleto->company,
                ]);
            }
        }
    }

    /**
     * Notify all master users.
     */
    private function notifyMasters(string $subject, array $data): void
    {
        $masters = User::where('role', 'master')->get();

        foreach ($masters as $master) {
            if ($master->email) {
                $this->notificationService->sendEmail($master->email, [
                    'subject' => $subject,
                    'message' => $data['message'],
                    'boleto' => $data['boleto'] ?? null,
                    'company' => $data['company'] ?? null,
                ]);
            }
        }
    }

    /**
     * Handle subscription cancelled event.
     *
     * @param array $data
     * @return void
     */
    private function handleSubscriptionCancelled(array $data): void
    {
        Log::info('Subscription cancelled', ['data' => $data]);

        $providerId = $data['subscription_id'] ?? null;

        if ($providerId) {
            $subscription = Subscription::where('provider_subscription_id', $providerId)->first();

            if ($subscription) {
                $subscription->update([
                    'status' => 'cancelled',
                    'data_cancelamento' => now(),
                ]);
            }
        }
    }
}
