<?php

namespace App\Services;

use App\Contracts\PaymentProviderInterface;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    protected PaymentProviderInterface $paymentProvider;

    public function __construct(PaymentProviderInterface $paymentProvider)
    {
        $this->paymentProvider = $paymentProvider;
    }

    /**
     * Validate payment method data.
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function validatePaymentMethodData(array $data): array
    {
        $rules = [
            'company_id' => 'required|exists:companies,id',
            'tipo' => 'required|in:boleto,credit_card,pix',
            'provider' => 'nullable|string',
            'dados_provider' => 'nullable|array',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Create a payment method.
     *
     * @param array $data
     * @return PaymentMethod
     */
    public function createPaymentMethod(array $data): PaymentMethod
    {
        // Create customer in payment provider if needed
        $customerData = $data['dados_provider'] ?? [];

        $providerResponse = $this->paymentProvider->createPaymentMethod([
            'type' => $data['tipo'],
            'customer_data' => $customerData,
        ]);

        // Create payment method record
        $paymentMethod = PaymentMethod::create([
            'company_id' => $data['company_id'],
            'tipo' => $data['tipo'],
            'provider' => $data['provider'] ?? 'asaas',
            'ativo' => true,
            'dados_provider' => $customerData,
            'provider_payment_id' => $providerResponse['payment_method_id'] ?? null,
        ]);

        return $paymentMethod;
    }

    /**
     * Create a single charge.
     *
     * @param array $data
     * @return array
     */
    public function createCharge(array $data): array
    {
        return $this->paymentProvider->createCharge($data);
    }

    /**
     * Get payment provider instance.
     *
     * @return PaymentProviderInterface
     */
    public function getProvider(): PaymentProviderInterface
    {
        return $this->paymentProvider;
    }
}

