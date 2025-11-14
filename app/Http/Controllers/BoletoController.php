<?php

namespace App\Http\Controllers;

use App\Models\Boleto;
use App\Services\ChargeService;
use App\Services\LogService;
use Illuminate\Http\Request;

class BoletoController extends Controller
{
    protected ChargeService $chargeService;
    protected LogService $logService;

    public function __construct(ChargeService $chargeService, LogService $logService)
    {
        $this->chargeService = $chargeService;
        $this->logService = $logService;
    }

    /**
     * Display a listing of boletos.
     *
     * GET /api/boletos
     *
     * Example Response:
     * {
     *   "data": [
     *     {
     *       "id": 1,
     *       "company_id": 1,
     *       "valor": 500.00,
     *       "vencimento": "2025-02-10",
     *       "status": "pending"
     *     }
     *   ]
     * }
     */
    public function index(Request $request)
    {
        $query = Boleto::with('company');

        // Filter by company if user is not master
        if ($request->user()->isNormal()) {
            $query->whereHas('company', function ($q) use ($request) {
                $q->where('id', $request->user()->company_id);
            });
        }

        // Filter by company_id if provided
        if ($request->has('company_id') && $request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by tipo_pagamento if provided
        if ($request->has('tipo_pagamento') && $request->tipo_pagamento) {
            $query->where('tipo_pagamento', $request->tipo_pagamento);
        }

        $boletos = $query->latest()->get();

        return response()->json(['data' => $boletos]);
    }

    /**
     * Store a newly created charge (boleto, PIX, or credit card).
     *
     * POST /api/boletos
     *
     * Example Request:
     * {
     *   "company_id": 1,
     *   "tipo_pagamento": "pix",
     *   "valor": 500.00,
     *   "vencimento": "2025-02-10",
     *   "descricao": "Mensalidade Janeiro/2025"
     * }
     */
    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'tipo_pagamento' => 'required|in:boleto,pix,credit_card',
            'valor' => 'required|numeric|min:0',
            'vencimento' => 'required|date|after_or_equal:today',
            'descricao' => 'nullable|string|max:255',
        ]);

        $boleto = $this->chargeService->createCharge($request->all());

        // Log the action
        $this->logService->logModelAction('created', $boleto, [
            'company_id' => $boleto->company_id,
            'valor' => $boleto->valor,
            'tipo_pagamento' => $boleto->tipo_pagamento,
        ]);

        return response()->json($boleto->load('company'), 201);
    }

    /**
     * Display the specified boleto.
     *
     * GET /api/boletos/{id}
     */
    public function show(Request $request, Boleto $boleto)
    {
        // Check authorization
        if ($request->user()->isNormal()) {
            if ($boleto->company->user_id !== $request->user()->id) {
                abort(403, 'Unauthorized');
            }
        }

        return response()->json($boleto->load('company'));
    }

    /**
     * Update the specified boleto.
     *
     * PUT /api/boletos/{id}
     */
    public function update(Request $request, Boleto $boleto)
    {
        // Check authorization
        if ($request->user()->isNormal()) {
            if ($boleto->company->user_id !== $request->user()->id) {
                abort(403, 'Unauthorized');
            }
        }

        // Only allow updating description
        $boleto->update($request->only(['descricao']));

        return response()->json($boleto);
    }

    /**
     * Remove the specified boleto.
     *
     * DELETE /api/boletos/{id}
     */
    public function destroy(Request $request, Boleto $boleto)
    {
        // Only master can delete
        if ($request->user()->isNormal()) {
            abort(403, 'Unauthorized');
        }

        $boleto->delete();

        return response()->json([
            'message' => 'Boleto removido com sucesso',
        ]);
    }

    /**
     * Send charge notification (Process Payment).
     * 
     * POST /api/charges/{boleto}/send
     */
    public function sendCharge(Request $request, Boleto $boleto)
    {
        try {
            // Check authorization
            if ($request->user()->isNormal()) {
                if ($boleto->company_id !== $request->user()->company_id) {
                    abort(403, 'Unauthorized');
                }
            }

            // Process payment and send notification
            $success = $this->chargeService->processPayment($boleto);

            if ($success) {
                // Log the action
                $this->logService->logModelAction('viewed', $boleto, [
                    'action' => 'process_payment',
                    'company_id' => $boleto->company_id,
                ]);

                return response()->json([
                    'message' => 'Email de cobrança enviado com sucesso!',
                ]);
            }

            return response()->json([
                'message' => 'Erro ao enviar email de cobrança',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao processar pagamento: ' . $e->getMessage(),
            ], 500);
        }
    }
}
