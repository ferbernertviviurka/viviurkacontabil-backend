<?php

namespace App\Services;

use App\Contracts\NfProviderInterface;
use App\Models\Invoice;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    protected NfProviderInterface $nfProvider;

    public function __construct(NfProviderInterface $nfProvider)
    {
        $this->nfProvider = $nfProvider;
    }

    /**
     * Validate invoice data for NFS-e (Nota Fiscal de Serviço).
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function validateInvoiceData(array $data): array
    {
        $rules = [
            'company_id' => 'required|exists:companies,id',
            'data_emissao' => 'required|date',
            'valor' => 'required|numeric|min:0',
            'destinatario_dados' => 'required|array',
            'destinatario_dados.nome' => 'required|string',
            'destinatario_dados.cpf_cnpj' => 'required|string',
            'destinatario_dados.endereco' => 'nullable|string',
            'destinatario_dados.municipio' => 'nullable|string',
            'itens' => 'required|array|min:1',
            'itens.*.descricao' => 'required|string',
            'itens.*.valor' => 'required|numeric|min:0',
            'itens.*.codigo_servico' => 'nullable|string', // Código do serviço municipal
            'observacoes' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Create and emit NFS-e (Nota Fiscal de Serviço).
     *
     * @param array $data
     * @return Invoice
     */
    public function createInvoice(array $data): Invoice
    {
        // Create invoice record
        $invoice = Invoice::create([
            'company_id' => $data['company_id'],
            'data_emissao' => $data['data_emissao'],
            'valor' => $data['valor'],
            'destinatario_dados' => $data['destinatario_dados'],
            'itens' => $data['itens'],
            'observacoes' => $data['observacoes'] ?? null,
            'serie' => 'NFS', // Série específica para NFS-e
            'status' => 'processando',
        ]);

        // Emit through provider
        $this->emitInvoice($invoice);

        return $invoice->fresh();
    }

    /**
     * Emit NFS-e through provider.
     *
     * @param Invoice $invoice
     * @return void
     */
    public function emitInvoice(Invoice $invoice): void
    {
        // Preparar descrição dos serviços
        $descricaoServico = collect($invoice->itens)
            ->pluck('descricao')
            ->join('; ');

        $response = $this->nfProvider->emitir([
            'company_id' => $invoice->company_id,
            'data_emissao' => $invoice->data_emissao->format('Y-m-d'),
            'valor' => $invoice->valor,
            'destinatario' => $invoice->destinatario_dados,
            'itens' => $invoice->itens,
            'descricao_servico' => $descricaoServico,
            'observacoes' => $invoice->observacoes,
        ]);

        if ($response['success']) {
            $invoice->update([
                'provider_id' => $response['provider_id'] ?? null,
                'numero' => $response['numero'] ?? null,
                'serie' => $response['serie'] ?? 'NFS',
                'status' => $response['status'] ?? 'processando',
                'provider_response' => $response,
            ]);
        } else {
            $invoice->update([
                'status' => 'erro',
                'provider_response' => $response,
            ]);
        }
    }

    /**
     * Check invoice status with provider.
     *
     * @param Invoice $invoice
     * @return array
     */
    public function checkInvoiceStatus(Invoice $invoice): array
    {
        if (!$invoice->provider_id) {
            return [
                'success' => false,
                'error' => 'Invoice does not have a provider ID',
            ];
        }

        $response = $this->nfProvider->consultar($invoice->provider_id);

        if ($response['success']) {
            $invoice->update([
                'status' => $response['status'] ?? $invoice->status,
                'numero' => $response['numero'] ?? $invoice->numero,
                'xml_path' => $response['xml_path'] ?? $invoice->xml_path,
                'pdf_path' => $response['pdf_path'] ?? $invoice->pdf_path,
            ]);
        }

        return $response;
    }

    /**
     * Cancel invoice.
     *
     * @param Invoice $invoice
     * @return array
     */
    public function cancelInvoice(Invoice $invoice): array
    {
        if (!$invoice->provider_id) {
            return [
                'success' => false,
                'error' => 'Invoice does not have a provider ID',
            ];
        }

        $response = $this->nfProvider->cancelar($invoice->provider_id);

        if ($response['success']) {
            $invoice->update([
                'status' => 'cancelada',
            ]);
        }

        return $response;
    }
}

