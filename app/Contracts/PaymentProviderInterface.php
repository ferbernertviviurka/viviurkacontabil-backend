<?php

namespace App\Contracts;

interface PaymentProviderInterface
{
    /**
     * Criar um cliente no provedor de pagamento.
     *
     * @param array $data
     * @return array
     */
    public function createCustomer(array $data): array;

    /**
     * Criar um método de pagamento.
     *
     * @param array $data
     * @return array
     */
    public function createPaymentMethod(array $data): array;

    /**
     * Criar uma cobrança única.
     *
     * @param array $data
     * @return array
     */
    public function createCharge(array $data): array;

    /**
     * Criar uma assinatura recorrente.
     *
     * @param array $data
     * @return array
     */
    public function createSubscription(array $data): array;

    /**
     * Cancelar uma assinatura.
     *
     * @param string $subscriptionId
     * @return array
     */
    public function cancelSubscription(string $subscriptionId): array;

    /**
     * Consultar status de uma assinatura.
     *
     * @param string $subscriptionId
     * @return array
     */
    public function getSubscription(string $subscriptionId): array;

    /**
     * Processar webhook do provedor.
     *
     * @param array $payload
     * @return array
     */
    public function processWebhook(array $payload): array;
}

