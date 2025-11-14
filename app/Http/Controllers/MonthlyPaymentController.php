<?php

namespace App\Http\Controllers;

use App\Models\MonthlyPayment;
use App\Services\MonthlyPaymentService;
use App\Services\LogService;
use Illuminate\Http\Request;

class MonthlyPaymentController extends Controller
{
    protected MonthlyPaymentService $monthlyPaymentService;
    protected LogService $logService;

    public function __construct(
        MonthlyPaymentService $monthlyPaymentService,
        LogService $logService
    ) {
        $this->monthlyPaymentService = $monthlyPaymentService;
        $this->logService = $logService;
    }

    /**
     * Display a listing of monthly payments.
     */
    public function index(Request $request)
    {
        $query = MonthlyPayment::with(['subscription', 'company']);

        if ($request->user()->isNormal()) {
            $query->where('company_id', $request->user()->company_id);
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('mes_referencia')) {
            $query->where('mes_referencia', $request->mes_referencia);
        }

        $payments = $query->latest('data_vencimento')->paginate(20);

        return response()->json($payments);
    }

    /**
     * Display statistics.
     */
    public function statistics(Request $request)
    {
        $query = MonthlyPayment::query();

        if ($request->user()->isNormal()) {
            $query->where('company_id', $request->user()->company_id);
        }

        if ($request->has('mes_referencia')) {
            $query->where('mes_referencia', $request->mes_referencia);
        }

        $stats = [
            'total' => $query->count(),
            'paid' => (clone $query)->where('status', 'paid')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'overdue' => (clone $query)->where('status', 'overdue')->count(),
            'by_method' => (clone $query)
                ->where('status', 'paid')
                ->selectRaw('metodo_pagamento, COUNT(*) as count')
                ->groupBy('metodo_pagamento')
                ->get()
                ->pluck('count', 'metodo_pagamento')
                ->toArray(),
            'by_company' => (clone $query)
                ->where('status', 'paid')
                ->with('company:id,razao_social,cnpj')
                ->selectRaw('company_id, COUNT(*) as count, SUM(valor) as total')
                ->groupBy('company_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'company_id' => $item->company_id,
                        'company_name' => $item->company->razao_social ?? '-',
                        'company_cnpj' => $item->company->cnpj ?? '-',
                        'count' => (int) $item->count,
                        'total' => (float) $item->total,
                    ];
                }),
        ];

        return response()->json($stats);
    }

    /**
     * Mark payment as paid.
     */
    public function markAsPaid(Request $request, MonthlyPayment $payment)
    {
        $this->monthlyPaymentService->markAsPaid($payment, [
            'metodo_pagamento' => $request->metodo_pagamento,
            'data_pagamento' => $request->data_pagamento ?? now(),
        ]);

        // Log the action
        $this->logService->logModelAction('updated', $payment, [
            'action' => 'marked_as_paid',
            'metodo_pagamento' => $request->metodo_pagamento,
        ]);

        return response()->json($payment->fresh(['subscription', 'company']));
    }

    /**
     * Display the specified monthly payment.
     */
    public function show(MonthlyPayment $payment)
    {
        return response()->json($payment->load(['subscription', 'company']));
    }
}

