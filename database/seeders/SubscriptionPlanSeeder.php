<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Básico',
                'slug' => 'basico',
                'description' => 'Plano ideal para pequenas empresas que estão começando',
                'price_monthly' => 99.00,
                'price_quarterly' => 267.30, // 10% desconto
                'price_semiannual' => 504.90, // 15% desconto
                'price_annual' => 950.40, // 20% desconto
                'features' => [
                    'Emissão de NFS-e',
                    'Até 10 notas fiscais/mês',
                    'Gestão de documentos',
                    'Suporte por email',
                    'Dashboard básico',
                ],
                'limits' => [
                    'max_invoices_per_month' => 10,
                    'max_companies' => 1,
                    'max_documents' => 50,
                    'ai_requests_per_month' => 50,
                ],
                'trial_days' => 7,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Profissional',
                'slug' => 'profissional',
                'description' => 'Para empresas em crescimento que precisam de mais recursos',
                'price_monthly' => 199.00,
                'price_quarterly' => 537.30,
                'price_semiannual' => 1014.90,
                'price_annual' => 1910.40,
                'features' => [
                    'Emissão de NFS-e ilimitada',
                    'Gestão completa de documentos',
                    'Cobranças recorrentes',
                    'Assinaturas e mensalidades',
                    'Suporte prioritário',
                    'Dashboard avançado',
                    'Relatórios detalhados',
                    'IA assistente (100 req/mês)',
                ],
                'limits' => [
                    'max_invoices_per_month' => -1, // Ilimitado
                    'max_companies' => 1,
                    'max_documents' => 200,
                    'ai_requests_per_month' => 100,
                ],
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Empresarial',
                'slug' => 'empresarial',
                'description' => 'Solução completa para grandes empresas',
                'price_monthly' => 399.00,
                'price_quarterly' => 1077.30,
                'price_semiannual' => 2034.90,
                'price_annual' => 3830.40,
                'features' => [
                    'Tudo do plano Profissional',
                    'Múltiplas empresas',
                    'IA assistente ilimitada',
                    'API completa',
                    'Suporte dedicado',
                    'Treinamento personalizado',
                    'Customizações',
                    'White label',
                ],
                'limits' => [
                    'max_invoices_per_month' => -1,
                    'max_companies' => -1, // Ilimitado
                    'max_documents' => -1,
                    'ai_requests_per_month' => -1,
                ],
                'trial_days' => 30,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::create($plan);
        }
    }
}

