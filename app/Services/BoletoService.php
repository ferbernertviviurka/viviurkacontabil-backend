<?php

namespace App\Services;

use App\Contracts\BoletoProviderInterface;
use App\Models\Boleto;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BoletoService
{
    protected BoletoProviderInterface $boletoProvider;
    protected NotificationService $notificationService;

    public function __construct(
        BoletoProviderInterface $boletoProvider,
        NotificationService $notificationService
    ) {
        $this->boletoProvider = $boletoProvider;
        $this->notificationService = $notificationService;
    }

    /**
     * Validate boleto data.
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function validateBoletoData(array $data): array
    {
        $rules = [
            'company_id' => 'required|exists:companies,id',
            'valor' => 'required|numeric|min:0',
            'vencimento' => 'required|date|after_or_equal:today',
            'descricao' => 'nullable|string|max:255',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Create and generate boleto.
     *
     * @param array $data
     * @return Boleto
     */
    public function createBoleto(array $data): Boleto
    {
        // Create boleto record
        $boleto = Boleto::create([
            'company_id' => $data['company_id'],
            'valor' => $data['valor'],
            'vencimento' => $data['vencimento'],
            'descricao' => $data['descricao'] ?? null,
            'status' => 'pending',
        ]);

        // Generate through provider
        $this->generateBoleto($boleto);

        return $boleto->fresh();
    }

    /**
     * Generate boleto through provider.
     *
     * @param Boleto $boleto
     * @return void
     */
    public function generateBoleto(Boleto $boleto): void
    {
        $response = $this->boletoProvider->gerar([
            'company_id' => $boleto->company_id,
            'valor' => $boleto->valor,
            'vencimento' => $boleto->vencimento->format('Y-m-d'),
            'descricao' => $boleto->descricao,
        ]);

        if ($response['success']) {
            $boleto->update([
                'provider_id' => $response['provider_id'] ?? null,
                'linha_digitavel' => $response['linha_digitavel'] ?? null,
                'codigo_barras' => $response['codigo_barras'] ?? null,
                'url_pdf' => $response['url_pdf'] ?? null,
                'status' => $response['status'] ?? 'pending',
                'provider_response' => $response,
            ]);
        } else {
            $boleto->update([
                'status' => 'error',
                'provider_response' => $response,
            ]);
        }
    }

    /**
     * Send boleto charge notification.
     *
     * @param Boleto $boleto
     * @param string $method
     * @param array $contact
     * @return bool
     */
    public function sendCharge(Boleto $boleto, string $method, array $contact): bool
    {
        $boletoData = [
            'valor' => $boleto->valor,
            'vencimento' => $boleto->vencimento->format('Y-m-d'),
            'linha_digitavel' => $boleto->linha_digitavel,
            'url_pdf' => $boleto->url_pdf,
        ];

        return $this->notificationService->sendBoletoNotification(
            $method,
            $contact,
            $boletoData
        );
    }

    /**
     * Check boleto status with provider.
     *
     * @param Boleto $boleto
     * @return array
     */
    public function checkBoletoStatus(Boleto $boleto): array
    {
        if (!$boleto->provider_id) {
            return [
                'success' => false,
                'error' => 'Boleto does not have a provider ID',
            ];
        }

        $response = $this->boletoProvider->consultar($boleto->provider_id);

        if ($response['success']) {
            $boleto->update([
                'status' => $response['status'] ?? $boleto->status,
            ]);
        }

        return $response;
    }

    /**
     * Cancel boleto.
     *
     * @param Boleto $boleto
     * @return array
     */
    public function cancelBoleto(Boleto $boleto): array
    {
        if (!$boleto->provider_id) {
            return [
                'success' => false,
                'error' => 'Boleto does not have a provider ID',
            ];
        }

        $response = $this->boletoProvider->cancelar($boleto->provider_id);

        if ($response['success']) {
            $boleto->update([
                'status' => 'cancelled',
            ]);
        }

        return $response;
    }
}

