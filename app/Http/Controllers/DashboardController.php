<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Boleto;
use App\Models\Document;
use App\Models\Log;
use App\Models\Subscription;
use App\Models\MonthlyPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     *
     * GET /api/dashboard
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isMaster = $user->isMaster();

        // Get current month and previous month dates
        $now = Carbon::now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $previousMonthStart = $now->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // Base query for filtering by company if user is not master
        $companyQuery = Company::query();
        if (!$isMaster) {
            $companyQuery->where('id', $user->company_id);
        }

        // Get companies statistics
        $totalCompanies = (clone $companyQuery)->count();
        $activeCompanies = (clone $companyQuery)->where('ativo', true)->count();
        $inactiveCompanies = (clone $companyQuery)->where('ativo', false)->count();
        
        // Companies in current month vs previous month
        $companiesCurrentMonth = (clone $companyQuery)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->count();
        $companiesPreviousMonth = (clone $companyQuery)
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();
        $companiesChange = $this->calculatePercentageChange($companiesPreviousMonth, $companiesCurrentMonth);

        // Get invoices statistics
        $invoiceQuery = Invoice::query();
        if (!$isMaster) {
            $invoiceQuery->whereHas('company', function ($q) use ($user) {
                $q->where('id', $user->company_id);
            });
        }

        $totalInvoices = (clone $invoiceQuery)->count();
        $invoicesCurrentMonth = (clone $invoiceQuery)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->count();
        $invoicesPreviousMonth = (clone $invoiceQuery)
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();
        $invoicesChange = $this->calculatePercentageChange($invoicesPreviousMonth, $invoicesCurrentMonth);

        // Get charges (boletos) statistics
        $chargeQuery = Boleto::query();
        if (!$isMaster) {
            $chargeQuery->whereHas('company', function ($q) use ($user) {
                $q->where('id', $user->company_id);
            });
        }

        $totalCharges = (clone $chargeQuery)->count();
        $chargesCurrentMonth = (clone $chargeQuery)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->count();
        $chargesPreviousMonth = (clone $chargeQuery)
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();
        $chargesChange = $this->calculatePercentageChange($chargesPreviousMonth, $chargesCurrentMonth);

        // Get monthly revenue (from paid charges and monthly payments)
        $revenueQuery = Boleto::query()
            ->where('status', 'paid')
            ->whereBetween('data_pagamento', [$currentMonthStart, $currentMonthEnd]);
        
        if (!$isMaster) {
            $revenueQuery->whereHas('company', function ($q) use ($user) {
                $q->where('id', $user->company_id);
            });
        }
        
        $monthlyRevenue = (clone $revenueQuery)->sum('valor');
        
        // Add monthly payments revenue
        $monthlyPaymentsQuery = MonthlyPayment::query()
            ->where('status', 'paid')
            ->whereBetween('data_pagamento', [$currentMonthStart, $currentMonthEnd]);
        
        if (!$isMaster) {
            $monthlyPaymentsQuery->where('company_id', $user->company_id);
        }
        
        $monthlyPaymentsRevenue = (clone $monthlyPaymentsQuery)->sum('valor');
        $totalMonthlyRevenue = $monthlyRevenue + $monthlyPaymentsRevenue;

        // Previous month revenue
        $previousRevenueQuery = Boleto::query()
            ->where('status', 'paid')
            ->whereBetween('data_pagamento', [$previousMonthStart, $previousMonthEnd]);
        
        if (!$isMaster) {
            $previousRevenueQuery->whereHas('company', function ($q) use ($user) {
                $q->where('id', $user->company_id);
            });
        }
        
        $previousMonthRevenue = (clone $previousRevenueQuery)->sum('valor');
        
        $previousMonthPaymentsQuery = MonthlyPayment::query()
            ->where('status', 'paid')
            ->whereBetween('data_pagamento', [$previousMonthStart, $previousMonthEnd]);
        
        if (!$isMaster) {
            $previousMonthPaymentsQuery->where('company_id', $user->company_id);
        }
        
        $previousMonthPaymentsRevenue = (clone $previousMonthPaymentsQuery)->sum('valor');
        $totalPreviousMonthRevenue = $previousMonthRevenue + $previousMonthPaymentsRevenue;
        
        $revenueChange = $this->calculatePercentageChange($totalPreviousMonthRevenue, $totalMonthlyRevenue);

        // Get pending tasks
        $pendingTasks = [];
        
        // Companies without key documents
        // Key documents are: contrato_social, cnpj, contrato_assinado
        $keyDocumentCategories = ['contrato_social', 'cnpj', 'contrato_assinado'];
        
        $companiesWithoutKeyDocsQuery = Company::query();
        if (!$isMaster) {
            $companiesWithoutKeyDocsQuery->where('id', $user->company_id);
        }
        
        $companiesWithoutKeyDocs = $companiesWithoutKeyDocsQuery->get()->filter(function ($company) use ($keyDocumentCategories) {
            $companyDocuments = $company->documents()
                ->where('documento_chave', true)
                ->whereIn('categoria', $keyDocumentCategories)
                ->pluck('categoria')
                ->toArray();
            
            // Check if company has all 3 key documents
            $hasAll = count(array_intersect($keyDocumentCategories, $companyDocuments)) === count($keyDocumentCategories);
            return !$hasAll;
        })->count();
        
        if ($companiesWithoutKeyDocs > 0) {
            $pendingTasks[] = [
                'id' => 'key_documents',
                'label' => 'Empresas sem documentos chave',
                'count' => $companiesWithoutKeyDocs,
                'route' => '/admin/companies',
            ];
        }

        // Charges due soon (next 7 days)
        $chargesDueSoonQuery = Boleto::query()
            ->where('status', 'pending')
            ->whereBetween('vencimento', [Carbon::now(), Carbon::now()->addDays(7)]);
        
        if (!$isMaster) {
            $chargesDueSoonQuery->whereHas('company', function ($q) use ($user) {
                $q->where('id', $user->company_id);
            });
        }
        
        $chargesDueSoon = $chargesDueSoonQuery->count();
        if ($chargesDueSoon > 0) {
            $pendingTasks[] = [
                'id' => 'charges_due',
                'label' => 'Cobranças a vencer (7 dias)',
                'count' => $chargesDueSoon,
                'route' => '/admin/cobrancas',
            ];
        }

        // Overdue charges
        $overdueChargesQuery = Boleto::query()
            ->where('status', 'pending')
            ->where('vencimento', '<', Carbon::now());
        
        if (!$isMaster) {
            $overdueChargesQuery->whereHas('company', function ($q) use ($user) {
                $q->where('id', $user->company_id);
            });
        }
        
        $overdueCharges = $overdueChargesQuery->count();
        if ($overdueCharges > 0) {
            $pendingTasks[] = [
                'id' => 'overdue_charges',
                'label' => 'Cobranças vencidas',
                'count' => $overdueCharges,
                'route' => '/admin/cobrancas',
            ];
        }

        // Pending invoices
        $pendingInvoicesQuery = Invoice::query()
            ->where('status', '!=', 'emitida')
            ->where('status', '!=', 'cancelada');
        
        if (!$isMaster) {
            $pendingInvoicesQuery->whereHas('company', function ($q) use ($user) {
                $q->where('id', $user->company_id);
            });
        }
        
        $pendingInvoices = $pendingInvoicesQuery->count();
        if ($pendingInvoices > 0) {
            $pendingTasks[] = [
                'id' => 'pending_invoices',
                'label' => 'Notas fiscais pendentes',
                'count' => $pendingInvoices,
                'route' => '/admin/invoices',
            ];
        }

        // Get recent activities (logs)
        $logQuery = Log::query()
            ->with('user')
            ->latest()
            ->limit(10);
        
        if (!$isMaster) {
            // For non-master users, show logs from users in the same company
            $logQuery->whereHas('user', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }
        
        $recentLogs = $logQuery->get()->map(function ($log) {
            return [
                'id' => $log->id,
                'title' => $this->formatLogTitle($log),
                'description' => $this->formatLogDescription($log),
                'time' => $log->created_at->diffForHumans(),
                'icon' => $this->getLogIcon($log->action),
                'bgColor' => $this->getLogBgColor($log->action),
                'iconColor' => $this->getLogIconColor($log->action),
            ];
        })->values();

        // Get recent companies (for non-master users, show only their company)
        $recentCompaniesQuery = Company::query()
            ->latest()
            ->limit(5);
        
        if (!$isMaster) {
            $recentCompaniesQuery->where('id', $user->company_id);
        }
        
        $recentCompanies = $recentCompaniesQuery->get();

        // Get revenue chart data (last 6 months)
        $revenueChartData = [];
        $monthNames = [
            1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'
        ];
        
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            
            $monthRevenueQuery = Boleto::query()
                ->where('status', 'paid')
                ->whereBetween('data_pagamento', [$monthStart, $monthEnd]);
            
            if (!$isMaster) {
                $monthRevenueQuery->whereHas('company', function ($q) use ($user) {
                    $q->where('id', $user->company_id);
                });
            }
            
            $monthRevenue = (clone $monthRevenueQuery)->sum('valor') ?? 0;
            
            $monthPaymentsQuery = MonthlyPayment::query()
                ->where('status', 'paid')
                ->whereBetween('data_pagamento', [$monthStart, $monthEnd]);
            
            if (!$isMaster) {
                $monthPaymentsQuery->where('company_id', $user->company_id);
            }
            
            $monthPaymentsRevenue = (clone $monthPaymentsQuery)->sum('valor') ?? 0;
            
            $monthName = $monthNames[$month->month] ?? $month->format('M');
            $revenueChartData[] = [
                'month' => $monthName . '/' . $month->format('y'),
                'revenue' => (float) ($monthRevenue + $monthPaymentsRevenue),
            ];
        }

        return response()->json([
            'stats' => [
                [
                    'label' => 'Total de Empresas',
                    'value' => $totalCompanies,
                    'change' => $companiesChange['percentage'],
                    'changeType' => $companiesChange['type'],
                    'icon' => 'i-heroicons-building-office-2',
                    'bgColor' => 'bg-blue-100',
                    'iconColor' => 'text-blue-600',
                    'details' => [
                        'active' => $activeCompanies,
                        'inactive' => $inactiveCompanies,
                    ],
                ],
                [
                    'label' => 'Notas Fiscais',
                    'value' => $totalInvoices,
                    'change' => $invoicesChange['percentage'],
                    'changeType' => $invoicesChange['type'],
                    'icon' => 'i-heroicons-document-text',
                    'bgColor' => 'bg-green-100',
                    'iconColor' => 'text-green-600',
                ],
                [
                    'label' => 'Cobranças',
                    'value' => $totalCharges,
                    'change' => $chargesChange['percentage'],
                    'changeType' => $chargesChange['type'],
                    'icon' => 'i-heroicons-banknotes',
                    'bgColor' => 'bg-purple-100',
                    'iconColor' => 'text-purple-600',
                ],
                [
                    'label' => 'Receita Mensal',
                    'value' => $totalMonthlyRevenue,
                    'change' => $revenueChange['percentage'],
                    'changeType' => $revenueChange['type'],
                    'icon' => 'i-heroicons-chart-bar',
                    'bgColor' => 'bg-cyan-100',
                    'iconColor' => 'text-cyan-600',
                ],
            ],
            'pendingTasks' => $pendingTasks,
            'recentActivities' => $recentLogs,
            'revenueChartData' => $revenueChartData,
            'recentCompanies' => $recentCompanies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'uuid' => $company->uuid,
                    'razao_social' => $company->razao_social,
                    'cnpj' => $company->cnpj,
                    'ativo' => $company->ativo,
                    'created_at' => $company->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ]);
    }

    /**
     * Calculate percentage change between two values.
     */
    private function calculatePercentageChange(float $oldValue, float $newValue): array
    {
        // Handle zero values
        if ($oldValue == 0) {
            if ($newValue > 0) {
                return [
                    'percentage' => '+100%',
                    'type' => 'increase',
                ];
            }
            return [
                'percentage' => '0%',
                'type' => 'neutral',
            ];
        }

        $change = (($newValue - $oldValue) / abs($oldValue)) * 100;
        $formattedChange = $change >= 0 
            ? '+' . number_format($change, 1, '.', '') 
            : number_format($change, 1, '.', '');
        
        return [
            'percentage' => $formattedChange . '%',
            'type' => $change > 0 ? 'increase' : ($change < 0 ? 'decrease' : 'neutral'),
        ];
    }

    /**
     * Format log title.
     */
    private function formatLogTitle($log): string
    {
        $actionLabels = [
            'created' => 'Criado',
            'updated' => 'Atualizado',
            'deleted' => 'Excluído',
            'viewed' => 'Visualizado',
        ];

        $resourceLabels = [
            'App\\Models\\Company' => 'Empresa',
            'App\\Models\\Invoice' => 'Nota Fiscal',
            'App\\Models\\Boleto' => 'Cobrança',
            'App\\Models\\Document' => 'Documento',
            'App\\Models\\Subscription' => 'Assinatura',
        ];

        $action = $actionLabels[$log->action] ?? $log->action;
        $resource = $resourceLabels[$log->resource_type] ?? $log->resource_type;

        return "{$action} {$resource}";
    }

    /**
     * Format log description.
     */
    private function formatLogDescription($log): string
    {
        $data = $log->data ?? [];
        
        if (isset($data['razao_social'])) {
            return $data['razao_social'];
        }
        
        if (isset($data['nome'])) {
            return $data['nome'];
        }
        
        if (isset($data['numero'])) {
            return "NFS-e #{$data['numero']}";
        }
        
        if (isset($data['valor'])) {
            return 'R$ ' . number_format($data['valor'], 2, ',', '.');
        }

        return 'Ação realizada no sistema';
    }

    /**
     * Get log icon based on action.
     */
    private function getLogIcon(string $action): string
    {
        return match ($action) {
            'created' => 'i-heroicons-plus-circle',
            'updated' => 'i-heroicons-pencil-square',
            'deleted' => 'i-heroicons-trash',
            default => 'i-heroicons-information-circle',
        };
    }

    /**
     * Get log background color based on action.
     */
    private function getLogBgColor(string $action): string
    {
        return match ($action) {
            'created' => 'bg-blue-100',
            'updated' => 'bg-yellow-100',
            'deleted' => 'bg-red-100',
            default => 'bg-gray-100',
        };
    }

    /**
     * Get log icon color based on action.
     */
    private function getLogIconColor(string $action): string
    {
        return match ($action) {
            'created' => 'text-blue-600',
            'updated' => 'text-yellow-600',
            'deleted' => 'text-red-600',
            default => 'text-gray-600',
        };
    }
}

