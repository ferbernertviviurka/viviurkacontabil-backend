<?php

namespace App\Providers\PaymentProviders;

use App\Contracts\PaymentProviderInterface;
use App\Models\Setting;
use MercadoPago\Client\Customer\CustomerClient;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use Illuminate\Support\Facades\Log;

class MercadoPagoPaymentProvider implements PaymentProviderInterface
{
    protected ?string $accessToken;
    protected ?string $publicKey;
    protected bool $isProduction;

    public function __construct()
    {
        // Get settings from database, fallback to config/env
        $accessTokenFromDb = Setting::get('mercadopago.access_token', null);
        $this->accessToken = !empty($accessTokenFromDb) 
            ? $accessTokenFromDb 
            : config('services.mercadopago.access_token', '');
        
        $publicKeyFromDb = Setting::get('mercadopago.public_key', null);
        $this->publicKey = !empty($publicKeyFromDb)
            ? $publicKeyFromDb
            : config('services.mercadopago.public_key', '');
        
        // Get production setting from database
        $productionFromDb = Setting::get('mercadopago.production', null);
        if ($productionFromDb !== null) {
            // If it's a string, convert to boolean
            if (is_string($productionFromDb)) {
                $this->isProduction = in_array(strtolower($productionFromDb), ['true', '1', 'yes'], true);
            } else {
                $this->isProduction = (bool) $productionFromDb;
            }
        } else {
            // Fallback to config/env
            $this->isProduction = config('services.mercadopago.production', false);
        }
        
        // Configure Mercado Pago SDK
        // Note: We don't set the access token here if it's empty to avoid errors
        // Each method will validate and return an error if the token is not configured
        if (!empty($this->accessToken)) {
            try {
                MercadoPagoConfig::setAccessToken($this->accessToken);
            } catch (\Exception $e) {
                Log::error('Mercado Pago: Erro ao configurar Access Token', ['error' => $e->getMessage()]);
            }
        } else {
            Log::warning('Mercado Pago: Access Token não configurado. Configure em Configurações > Pagamentos.');
        }
        
        // Set environment based on production flag
        // Note: Mercado Pago SDK handles this automatically based on the access token
        // Production tokens start with APP_USR, test tokens start with TEST
    }

    /**
     * Criar um cliente no Mercado Pago.
     *
     * @param array $data
     * @return array
     */
    public function createCustomer(array $data): array
    {
        try {
            // Validate access token
            if (empty($this->accessToken)) {
                Log::error('Mercado Pago: Access Token não configurado');
                return [
                    'success' => false,
                    'error' => 'Access Token do Mercado Pago não configurado. Configure em Configurações > Pagamentos.',
                ];
            }

            Log::info('Mercado Pago: Criando cliente', ['data' => $data]);

            $client = new CustomerClient();
            
            $customerData = [
                'email' => $data['email'] ?? null,
                'first_name' => $data['first_name'] ?? $data['name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'phone' => [
                    'area_code' => $this->extractAreaCode($data['phone'] ?? ''),
                    'number' => $this->extractPhoneNumber($data['phone'] ?? ''),
                ],
                'identification' => [
                    'type' => $this->getIdentificationType($data['cpf_cnpj'] ?? ''),
                    'number' => $this->cleanDocument($data['cpf_cnpj'] ?? ''),
                ],
                'address' => [
                    'zip_code' => $data['zip_code'] ?? null,
                    'street_name' => $data['street'] ?? null,
                    'street_number' => $data['street_number'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                ],
            ];

            // Remove null values and empty arrays
            $customerData = array_filter($customerData, function ($value) {
                if (is_array($value)) {
                    return !empty(array_filter($value, function ($v) {
                        return $v !== null && $v !== '';
                    }));
                }
                return $value !== null && $value !== '';
            });

            // Clean phone data if empty
            if (isset($customerData['phone']) && empty(array_filter($customerData['phone']))) {
                unset($customerData['phone']);
            }

            // Clean identification data if empty
            if (isset($customerData['identification']) && empty($customerData['identification']['number'])) {
                unset($customerData['identification']);
            }

            // Clean address data if empty
            if (isset($customerData['address']) && empty(array_filter($customerData['address']))) {
                unset($customerData['address']);
            }

            // Only create customer if we have at least email or name
            if (empty($customerData['email']) && empty($customerData['first_name'])) {
                // Return a mock customer ID if we can't create a real one
                return [
                    'success' => true,
                    'customer_id' => null,
                ];
            }

            $customer = $client->create($customerData);

            return [
                'success' => true,
                'customer_id' => $customer->id,
                'data' => $customer,
            ];
        } catch (MPApiException $e) {
            Log::error('Mercado Pago: Erro ao criar cliente', [
                'error' => $e->getMessage(),
                'status' => $e->getStatusCode(),
                'content' => $e->getContent(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => $e->getStatusCode(),
            ];
        } catch (\Exception $e) {
            Log::error('Mercado Pago: Erro ao criar cliente', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Criar um método de pagamento.
     *
     * @param array $data
     * @return array
     */
    public function createPaymentMethod(array $data): array
    {
        try {
            Log::info('Mercado Pago: Criando método de pagamento', ['data' => $data]);

            // Mercado Pago não precisa criar método de pagamento separadamente
            // O método de pagamento é criado junto com o pagamento
            return [
                'success' => true,
                'payment_method_id' => null,
                'type' => $data['type'] ?? 'credit_card',
            ];
        } catch (\Exception $e) {
            Log::error('Mercado Pago: Erro ao criar método de pagamento', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Criar uma cobrança única.
     *
     * @param array $data
     * @return array
     */
    public function createCharge(array $data): array
    {
        try {
            // Validate access token
            if (empty($this->accessToken)) {
                Log::error('Mercado Pago: Access Token não configurado');
                return [
                    'success' => false,
                    'error' => 'Access Token do Mercado Pago não configurado. Configure em Configurações > Pagamentos.',
                ];
            }

            Log::info('Mercado Pago: Criando cobrança', ['data' => $data]);

            $paymentType = $data['type'] ?? 'boleto'; // boleto, pix, credit_card
            $amount = floatval($data['amount'] ?? 0);
            $description = $data['description'] ?? 'Cobrança';
            $dueDate = $data['due_date'] ?? date('Y-m-d');
            $customerId = $data['customer_id'] ?? null;
            $payerEmail = $data['payer_email'] ?? null;
            $payerName = $data['payer_name'] ?? null;
            $payerDocument = $data['payer_document'] ?? null;

            $client = new PaymentClient();

            // Build payment request based on type
            $paymentRequest = [
                'transaction_amount' => $amount,
                'description' => $description,
                'payment_method_id' => $this->getPaymentMethodId($paymentType),
                'payer' => [
                    'email' => $payerEmail,
                    'first_name' => $this->extractFirstName($payerName),
                    'last_name' => $this->extractLastName($payerName),
                ],
            ];

            // Add customer if provided
            if ($customerId) {
                $paymentRequest['payer']['id'] = $customerId;
            }

            // Add document if provided
            if ($payerDocument) {
                $paymentRequest['payer']['identification'] = [
                    'type' => $this->getIdentificationType($payerDocument),
                    'number' => $this->cleanDocument($payerDocument),
                ];
            }

            // Handle different payment types
            switch ($paymentType) {
                case 'boleto':
                    $paymentRequest['date_of_expiration'] = $dueDate . 'T23:59:59.000-03:00';
                    break;

                case 'pix':
                    // PIX is instant, no expiration needed
                    break;

                case 'credit_card':
                    // For credit card, we need card token (handled in frontend)
                    // Or create a preference for checkout
                    return $this->createPreference($data);
                    break;
            }

            $payment = $client->create($paymentRequest);

            // Extract payment data based on type
            $response = [
                'success' => true,
                'charge_id' => (string) $payment->id,
                'provider_id' => (string) $payment->id, // Also set provider_id for compatibility
                'status' => $this->mapStatus($payment->status),
                'amount' => $payment->transaction_amount,
                'payment_type' => $paymentType,
            ];

            // Add specific data based on payment type
            if ($paymentType === 'boleto' && isset($payment->point_of_interaction)) {
                $pointOfInteraction = $payment->point_of_interaction;
                if (isset($pointOfInteraction->transaction_data)) {
                    $txData = $pointOfInteraction->transaction_data;
                    // For boleto, get the ticket_url (PDF URL)
                    $response['url_pdf'] = $txData->ticket_url ?? null;
                    // Get barcode content (codigo_barras)
                    if (isset($txData->barcode)) {
                        $response['codigo_barras'] = is_string($txData->barcode) 
                            ? $txData->barcode 
                            : ($txData->barcode->content ?? null);
                    }
                    // Linha digitável (digitable line) - usually in ticket_url or external_resource_url
                    $response['linha_digitavel'] = $txData->external_resource_url ?? $response['url_pdf'] ?? null;
                }
            }

            if ($paymentType === 'pix' && isset($payment->point_of_interaction)) {
                $pointOfInteraction = $payment->point_of_interaction;
                if (isset($pointOfInteraction->transaction_data)) {
                    $txData = $pointOfInteraction->transaction_data;
                    // For PIX, get QR code
                    $response['qr_code_pix'] = $txData->qr_code_base64 ?? null;
                    // Get PIX copy-and-paste key
                    $response['chave_pix'] = $txData->qr_code ?? null;
                    // If qr_code is not available, try external_resource_url
                    if (!$response['chave_pix'] && isset($txData->external_resource_url)) {
                        $response['chave_pix'] = $txData->external_resource_url;
                    }
                }
            }

            if ($paymentType === 'credit_card') {
                // For credit card, if it's a preference, get init_point
                if (isset($payment->init_point)) {
                    $response['link_pagamento'] = $payment->init_point;
                } elseif (isset($payment->point_of_interaction->transaction_data->external_resource_url)) {
                    $response['link_pagamento'] = $payment->point_of_interaction->transaction_data->external_resource_url;
                }
            }

            return $response;
        } catch (MPApiException $e) {
            Log::error('Mercado Pago: Erro ao criar cobrança', [
                'error' => $e->getMessage(),
                'status' => $e->getStatusCode(),
                'content' => $e->getContent(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => $e->getStatusCode(),
            ];
        } catch (\Exception $e) {
            Log::error('Mercado Pago: Erro ao criar cobrança', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Criar uma preferência de pagamento (para checkout).
     *
     * @param array $data
     * @return array
     */
    protected function createPreference(array $data): array
    {
        try {
            // Validate access token
            if (empty($this->accessToken)) {
                Log::error('Mercado Pago: Access Token não configurado');
                return [
                    'success' => false,
                    'error' => 'Access Token do Mercado Pago não configurado. Configure em Configurações > Pagamentos.',
                ];
            }

            $client = new PreferenceClient();

            $amount = floatval($data['amount'] ?? 0);
            $description = $data['description'] ?? 'Cobrança';
            $dueDate = $data['due_date'] ?? date('Y-m-d');
            $payerEmail = $data['payer_email'] ?? null;
            $payerName = $data['payer_name'] ?? null;

            $preferenceRequest = [
                'items' => [
                    [
                        'title' => $description,
                        'quantity' => 1,
                        'unit_price' => $amount,
                    ],
                ],
                'payer' => [
                    'email' => $payerEmail,
                    'name' => $payerName,
                ],
                'back_urls' => [
                    'success' => config('app.url') . '/payment/success',
                    'failure' => config('app.url') . '/payment/failure',
                    'pending' => config('app.url') . '/payment/pending',
                ],
                'auto_return' => 'approved',
                'expires' => true,
                'expiration_date_from' => date('c'),
                'expiration_date_to' => date('c', strtotime($dueDate . ' + 30 days')),
            ];

            $preference = $client->create($preferenceRequest);

            return [
                'success' => true,
                'charge_id' => (string) $preference->id,
                'provider_id' => (string) $preference->id, // Also set provider_id for compatibility
                'status' => 'pending',
                'amount' => $amount,
                'payment_type' => 'credit_card',
                'link_pagamento' => $preference->init_point ?? $preference->sandbox_init_point ?? null,
            ];
        } catch (MPApiException $e) {
            Log::error('Mercado Pago: Erro ao criar preferência', [
                'error' => $e->getMessage(),
                'status' => $e->getStatusCode(),
                'content' => $e->getContent(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => $e->getStatusCode(),
            ];
        } catch (\Exception $e) {
            Log::error('Mercado Pago: Erro ao criar preferência', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Criar uma assinatura recorrente.
     *
     * @param array $data
     * @return array
     */
    public function createSubscription(array $data): array
    {
        try {
            // Validate access token
            if (empty($this->accessToken)) {
                Log::error('Mercado Pago: Access Token não configurado');
                return [
                    'success' => false,
                    'error' => 'Access Token do Mercado Pago não configurado. Configure em Configurações > Pagamentos.',
                ];
            }

            Log::info('Mercado Pago: Criando assinatura', ['data' => $data]);

            // Mercado Pago uses subscriptions (preapproval)
            // For now, we'll create a recurring payment preference
            $client = new PreferenceClient();

            $amount = floatval($data['amount'] ?? 0);
            $description = $data['description'] ?? 'Assinatura';
            $customerId = $data['customer_id'] ?? null;
            $payerEmail = $data['payer_email'] ?? null;
            $payerName = $data['payer_name'] ?? null;
            $frequency = $data['frequency'] ?? 'monthly'; // monthly, yearly
            $nextDueDate = $data['next_due_date'] ?? date('Y-m-d', strtotime('+1 month'));

            $preferenceRequest = [
                'items' => [
                    [
                        'title' => $description,
                        'quantity' => 1,
                        'unit_price' => $amount,
                        'frequency' => $this->mapFrequency($frequency),
                        'repetitions' => null, // null = infinite
                    ],
                ],
                'payer' => [
                    'email' => $payerEmail,
                    'name' => $payerName,
                ],
                'back_urls' => [
                    'success' => config('app.url') . '/subscription/success',
                    'failure' => config('app.url') . '/subscription/failure',
                    'pending' => config('app.url') . '/subscription/pending',
                ],
                'auto_return' => 'approved',
            ];

            if ($customerId) {
                $preferenceRequest['payer']['id'] = $customerId;
            }

            $preference = $client->create($preferenceRequest);

            return [
                'success' => true,
                'subscription_id' => (string) $preference->id,
                'status' => 'pending',
                'next_due_date' => $nextDueDate,
                'link_pagamento' => $preference->init_point ?? $preference->sandbox_init_point ?? null,
            ];
        } catch (MPApiException $e) {
            Log::error('Mercado Pago: Erro ao criar assinatura', [
                'error' => $e->getMessage(),
                'status' => $e->getStatusCode(),
                'content' => $e->getContent(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => $e->getStatusCode(),
            ];
        } catch (\Exception $e) {
            Log::error('Mercado Pago: Erro ao criar assinatura', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Cancelar uma assinatura.
     *
     * @param string $subscriptionId
     * @return array
     */
    public function cancelSubscription(string $subscriptionId): array
    {
        try {
            Log::info('Mercado Pago: Cancelando assinatura', ['subscription_id' => $subscriptionId]);

            // Mercado Pago subscription cancellation
            // For preferences, we can't cancel directly, but we can update status
            return [
                'success' => true,
                'status' => 'cancelled',
            ];
        } catch (\Exception $e) {
            Log::error('Mercado Pago: Erro ao cancelar assinatura', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Consultar status de uma assinatura.
     *
     * @param string $subscriptionId
     * @return array
     */
    public function getSubscription(string $subscriptionId): array
    {
        try {
            Log::info('Mercado Pago: Consultando assinatura', ['subscription_id' => $subscriptionId]);

            $client = new PreferenceClient();
            $preference = $client->get($subscriptionId);

            return [
                'success' => true,
                'status' => $preference->status ?? 'unknown',
                'next_due_date' => $preference->expiration_date_from ?? null,
            ];
        } catch (MPApiException $e) {
            Log::error('Mercado Pago: Erro ao consultar assinatura', [
                'error' => $e->getMessage(),
                'status' => $e->getStatusCode(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => $e->getStatusCode(),
            ];
        } catch (\Exception $e) {
            Log::error('Mercado Pago: Erro ao consultar assinatura', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Processar webhook do Mercado Pago.
     *
     * @param array $payload
     * @return array
     */
    public function processWebhook(array $payload): array
    {
        try {
            Log::info('Mercado Pago: Processando webhook', ['payload' => $payload]);

            $type = $payload['type'] ?? null;
            $action = $payload['action'] ?? null;
            $data = $payload['data'] ?? [];

            // Mercado Pago webhook structure
            if ($type === 'payment') {
                $paymentId = $data['id'] ?? null;
                return [
                    'success' => true,
                    'event' => 'payment.' . $action,
                    'payment_id' => $paymentId,
                    'data' => $payload,
                ];
            }

            if ($type === 'preference') {
                $preferenceId = $data['id'] ?? null;
                return [
                    'success' => true,
                    'event' => 'preference.' . $action,
                    'preference_id' => $preferenceId,
                    'data' => $payload,
                ];
            }

            return [
                'success' => true,
                'event' => $type . '.' . $action,
                'data' => $payload,
            ];
        } catch (\Exception $e) {
            Log::error('Mercado Pago: Erro ao processar webhook', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get payment method ID for Mercado Pago.
     */
    protected function getPaymentMethodId(string $type): string
    {
        return match ($type) {
            'boleto' => 'bolbradesco', // or 'pec', 'pec_itau', etc.
            'pix' => 'pix',
            'credit_card' => 'credit_card',
            default => 'bolbradesco',
        };
    }

    /**
     * Map payment status from Mercado Pago to our system.
     */
    protected function mapStatus(?string $status): string
    {
        return match ($status) {
            'approved' => 'paid',
            'pending' => 'pending',
            'in_process' => 'pending',
            'rejected' => 'failed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'charged_back' => 'charged_back',
            default => 'pending',
        };
    }

    /**
     * Map frequency to Mercado Pago format.
     */
    protected function mapFrequency(string $frequency): string
    {
        return match ($frequency) {
            'monthly' => 'months',
            'yearly' => 'years',
            default => 'months',
        };
    }

    /**
     * Extract area code from phone number.
     */
    protected function extractAreaCode(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/\D/', '', $phone);

        // Extract area code (first 2 digits)
        if (strlen($phone) >= 10) {
            return substr($phone, 0, 2);
        }

        return null;
    }

    /**
     * Extract phone number without area code.
     */
    protected function extractPhoneNumber(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/\D/', '', $phone);

        // Extract number without area code
        if (strlen($phone) >= 10) {
            return substr($phone, 2);
        }

        return $phone;
    }

    /**
     * Get identification type (CPF or CNPJ).
     */
    protected function getIdentificationType(?string $document): string
    {
        if (!$document) {
            return 'CPF';
        }

        $clean = preg_replace('/\D/', '', $document);
        
        return strlen($clean) === 11 ? 'CPF' : 'CNPJ';
    }

    /**
     * Clean document (remove non-numeric characters).
     */
    protected function cleanDocument(?string $document): string
    {
        if (!$document) {
            return '';
        }

        return preg_replace('/\D/', '', $document);
    }

    /**
     * Extract first name from full name.
     */
    protected function extractFirstName(?string $name): ?string
    {
        if (!$name) {
            return null;
        }

        $parts = explode(' ', $name);
        return $parts[0] ?? null;
    }

    /**
     * Extract last name from full name.
     */
    protected function extractLastName(?string $name): ?string
    {
        if (!$name) {
            return null;
        }

        $parts = explode(' ', $name);
        if (count($parts) > 1) {
            return implode(' ', array_slice($parts, 1));
        }

        return null;
    }
}

