<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\LogService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;
    protected LogService $logService;

    public function __construct(InvoiceService $invoiceService, LogService $logService)
    {
        $this->invoiceService = $invoiceService;
        $this->logService = $logService;
    }

    /**
     * Display a listing of invoices.
     *
     * GET /api/invoices
     *
     * Example Response:
     * {
     *   "data": [
     *     {
     *       "id": 1,
     *       "company_id": 1,
     *       "numero": "123",
     *       "valor": 500.00,
     *       "status": "emitida"
     *     }
     *   ]
     * }
     */
    public function index(Request $request)
    {
        $query = Invoice::with('company');

        // Filter by company if user is not master
        if ($request->user()->isNormal()) {
            $query->whereHas('company', function ($q) use ($request) {
                $q->where('id', $request->user()->company_id);
            });
        }

        // Filter by company_id if provided
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('data_emissao', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('data_emissao', '<=', $request->end_date);
        }

        $invoices = $query->latest()->paginate(15);

        return response()->json($invoices);
    }

    /**
     * Store a newly created invoice.
     *
     * POST /api/invoices
     *
     * Example Request:
     * {
     *   "company_id": 1,
     *   "data_emissao": "2025-01-15",
     *   "valor": 500.00,
     *   "destinatario_dados": {
     *     "nome": "Cliente XYZ",
     *     "cpf_cnpj": "12345678901"
     *   },
     *   "itens": [
     *     {
     *       "descricao": "Serviço Contábil",
     *       "valor": 500.00
     *     }
     *   ]
     * }
     */
    public function store(Request $request)
    {
        $validated = $this->invoiceService->validateInvoiceData($request->all());

        $invoice = $this->invoiceService->createInvoice($validated);

        // Log the action
        $this->logService->logModelAction('created', $invoice, [
            'company_id' => $invoice->company_id,
            'valor' => $invoice->valor,
        ]);

        return response()->json($invoice, 201);
    }

    /**
     * Display the specified invoice.
     *
     * GET /api/invoices/{id}
     */
    public function show(Request $request, Invoice $invoice)
    {
        // Check authorization
        if ($request->user()->isNormal()) {
            if ($invoice->company->user_id !== $request->user()->id) {
                abort(403, 'Unauthorized');
            }
        }

        return response()->json($invoice->load('company'));
    }

    /**
     * Update the specified invoice.
     *
     * PUT /api/invoices/{id}
     */
    public function update(Request $request, Invoice $invoice)
    {
        // Check authorization
        if ($request->user()->isNormal()) {
            if ($invoice->company->user_id !== $request->user()->id) {
                abort(403, 'Unauthorized');
            }
        }

        // Only allow updating certain fields
        $invoice->update($request->only(['observacoes']));

        return response()->json($invoice);
    }

    /**
     * Remove the specified invoice.
     *
     * DELETE /api/invoices/{id}
     */
    public function destroy(Request $request, Invoice $invoice)
    {
        // Only master can delete
        if ($request->user()->isNormal()) {
            abort(403, 'Unauthorized');
        }

        $invoice->delete();

        return response()->json([
            'message' => 'Nota fiscal removida com sucesso',
        ]);
    }

    /**
     * Check invoice status with provider.
     *
     * POST /api/invoices/{id}/check-status
     */
    public function checkStatus(Request $request, Invoice $invoice)
    {
        $response = $this->invoiceService->checkInvoiceStatus($invoice);

        return response()->json($response);
    }

    /**
     * Cancel invoice.
     *
     * POST /api/invoices/{id}/cancel
     */
    public function cancel(Request $request, Invoice $invoice)
    {
        // Only master can cancel
        if ($request->user()->isNormal()) {
            abort(403, 'Unauthorized');
        }

        $response = $this->invoiceService->cancelInvoice($invoice);

        return response()->json($response);
    }
}
