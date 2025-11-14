<?php

namespace App\Http\Controllers;

use App\Models\Boleto;
use App\Models\MonthlyPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BillingController extends Controller
{
    /**
     * Get billing report grouped by months.
     *
     * GET /api/billing/report
     */
    public function report(Request $request)
    {
        // Only master can access
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        $startDate = $request->input('start_date', Carbon::now()->subMonths(11)->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        // Get monthly payments grouped by month (SQLite compatible)
        $monthlyPayments = MonthlyPayment::select(
            DB::raw('strftime("%Y-%m", created_at) as mes'),
            DB::raw('SUM(CASE WHEN status = "paid" THEN valor ELSE 0 END) as total_pago'),
            DB::raw('SUM(CASE WHEN status = "pending" THEN valor ELSE 0 END) as total_pendente'),
            DB::raw('SUM(CASE WHEN status = "overdue" THEN valor ELSE 0 END) as total_vencido'),
            DB::raw('COUNT(*) as total_cobrancas')
        )
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('mes')
        ->orderBy('mes')
        ->get();

        // Get charges (boletos) grouped by month (SQLite compatible)
        $charges = Boleto::select(
            DB::raw('strftime("%Y-%m", created_at) as mes'),
            DB::raw('SUM(CASE WHEN status = "paid" THEN valor ELSE 0 END) as total_pago'),
            DB::raw('SUM(CASE WHEN status = "pending" THEN valor ELSE 0 END) as total_pendente'),
            DB::raw('SUM(CASE WHEN status = "overdue" THEN valor ELSE 0 END) as total_vencido'),
            DB::raw('COUNT(*) as total_cobrancas')
        )
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('mes')
        ->orderBy('mes')
        ->get();

        // Combine data
        $report = [];
        $allMonths = collect($monthlyPayments)->merge($charges)->pluck('mes')->unique()->sort();

        foreach ($allMonths as $mes) {
            $monthly = $monthlyPayments->firstWhere('mes', $mes);
            $charge = $charges->firstWhere('mes', $mes);

            $report[] = [
                'mes' => $mes,
                'mes_formatado' => Carbon::createFromFormat('Y-m', $mes)->format('M/Y'),
                'mensalidades' => [
                    'total_pago' => $monthly->total_pago ?? 0,
                    'total_pendente' => $monthly->total_pendente ?? 0,
                    'total_vencido' => $monthly->total_vencido ?? 0,
                    'total_cobrancas' => $monthly->total_cobrancas ?? 0,
                ],
                'cobrancas' => [
                    'total_pago' => $charge->total_pago ?? 0,
                    'total_pendente' => $charge->total_pendente ?? 0,
                    'total_vencido' => $charge->total_vencido ?? 0,
                    'total_cobrancas' => $charge->total_cobrancas ?? 0,
                ],
                'total_geral' => [
                    'pago' => ($monthly->total_pago ?? 0) + ($charge->total_pago ?? 0),
                    'pendente' => ($monthly->total_pendente ?? 0) + ($charge->total_pendente ?? 0),
                    'vencido' => ($monthly->total_vencido ?? 0) + ($charge->total_vencido ?? 0),
                ],
            ];
        }

        return response()->json($report);
    }

    /**
     * Get billing statistics.
     *
     * GET /api/billing/statistics
     */
    public function statistics(Request $request)
    {
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        $monthlyTotal = MonthlyPayment::where('status', 'paid')->sum('valor');
        $chargesTotal = Boleto::where('status', 'paid')->sum('valor');
        $totalRevenue = $monthlyTotal + $chargesTotal;

        $monthlyPending = MonthlyPayment::where('status', 'pending')->sum('valor');
        $chargesPending = Boleto::where('status', 'pending')->sum('valor');
        $totalPending = $monthlyPending + $chargesPending;

        $monthlyOverdue = MonthlyPayment::where('status', 'overdue')->sum('valor');
        $chargesOverdue = Boleto::where('status', 'overdue')->sum('valor');
        $totalOverdue = $monthlyOverdue + $chargesOverdue;

        // By payment method
        $byMethod = [
            'boleto' => Boleto::where('status', 'paid')->where('tipo_pagamento', 'boleto')->sum('valor'),
            'pix' => Boleto::where('status', 'paid')->where('tipo_pagamento', 'pix')->sum('valor'),
            'credit_card' => Boleto::where('status', 'paid')->where('tipo_pagamento', 'credit_card')->sum('valor'),
        ];

        return response()->json([
            'total_revenue' => $totalRevenue,
            'total_pending' => $totalPending,
            'total_overdue' => $totalOverdue,
            'by_method' => $byMethod,
            'monthly_payments' => [
                'total' => $monthlyTotal,
                'pending' => $monthlyPending,
                'overdue' => $monthlyOverdue,
            ],
            'charges' => [
                'total' => $chargesTotal,
                'pending' => $chargesPending,
                'overdue' => $chargesOverdue,
            ],
        ]);
    }

    /**
     * Get all payments (monthly payments + charges) with details.
     *
     * GET /api/billing/payments
     */
    public function payments(Request $request)
    {
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        $startDate = $request->input('start_date', Carbon::now()->subMonths(11)->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        // Get monthly payments
        $monthlyPayments = MonthlyPayment::with(['company', 'subscription'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'type' => 'monthly_payment',
                    'company_id' => $payment->company_id,
                    'company_name' => $payment->company->razao_social ?? null,
                    'company_cnpj' => $payment->company->cnpj ?? null,
                    'valor' => $payment->valor,
                    'status' => $payment->status,
                    'metodo_pagamento' => $payment->metodo_pagamento,
                    'data_vencimento' => $payment->data_vencimento,
                    'data_pagamento' => $payment->data_pagamento,
                    'mes_referencia' => $payment->mes_referencia,
                    'created_at' => $payment->created_at,
                ];
            });

        // Get charges
        $charges = Boleto::with('company')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->map(function ($charge) {
                return [
                    'id' => $charge->id,
                    'type' => 'charge',
                    'company_id' => $charge->company_id,
                    'company_name' => $charge->company->razao_social ?? null,
                    'company_cnpj' => $charge->company->cnpj ?? null,
                    'valor' => $charge->valor,
                    'status' => $charge->status,
                    'tipo_pagamento' => $charge->tipo_pagamento,
                    'vencimento' => $charge->vencimento,
                    'data_pagamento' => $charge->data_pagamento,
                    'descricao' => $charge->descricao,
                    'created_at' => $charge->created_at,
                ];
            });

        // Combine and sort
        $allPayments = $monthlyPayments->merge($charges)
            ->sortByDesc('created_at')
            ->values();

        return response()->json([
            'data' => $allPayments,
            'total' => $allPayments->count(),
        ]);
    }
}

