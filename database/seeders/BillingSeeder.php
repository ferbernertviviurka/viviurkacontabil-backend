<?php

namespace Database\Seeders;

use App\Models\Boleto;
use App\Models\MonthlyPayment;
use App\Models\Company;
use App\Models\Subscription;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BillingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();

        if ($companies->isEmpty()) {
            $this->command->warn('Nenhuma empresa encontrada. Execute CompanySeeder primeiro.');
            return;
        }

        // Generate monthly payments for last 12 months
        foreach ($companies as $company) {
            $subscription = Subscription::where('company_id', $company->id)->first();
            
            if (!$subscription) {
                continue;
            }

            for ($i = 0; $i < 12; $i++) {
                $date = Carbon::now()->subMonths($i);
                
                MonthlyPayment::create([
                    'subscription_id' => $subscription->id,
                    'company_id' => $company->id,
                    'mes_referencia' => $date->format('Y-m'),
                    'valor' => $subscription->valor,
                    'data_vencimento' => $date->copy()->day(10),
                    'status' => $i < 3 ? 'paid' : ($i < 6 ? 'pending' : 'overdue'),
                    'metodo_pagamento' => ['boleto', 'pix', 'credit_card'][rand(0, 2)],
                    'data_pagamento' => $i < 3 ? $date->copy()->day(5) : null,
                    'email_enviado' => true,
                    'email_enviado_em' => $date->copy()->subDays(5),
                    'created_at' => $date,
                ]);
            }
        }

        // Generate charges (boletos) for last 12 months
        foreach ($companies as $company) {
            for ($i = 0; $i < 6; $i++) {
                $date = Carbon::now()->subMonths($i);
                $valor = rand(100, 5000);
                $tipoPagamento = ['boleto', 'pix', 'credit_card'][rand(0, 2)];
                
                Boleto::create([
                    'company_id' => $company->id,
                    'tipo_pagamento' => $tipoPagamento,
                    'valor' => $valor,
                    'vencimento' => $date->copy()->day(15),
                    'status' => $i < 2 ? 'paid' : ($i < 4 ? 'pending' : 'overdue'),
                    'descricao' => "Cobrança adicional - " . $date->format('M/Y'),
                    'data_pagamento' => $i < 2 ? $date->copy()->day(12) : null,
                    'linha_digitavel' => $tipoPagamento === 'boleto' ? '34191.09008 01234.567890 12345.678901 2 12345678901234' : null,
                    'chave_pix' => $tipoPagamento === 'pix' ? 'pix-' . uniqid() . '@viviurka.com' : null,
                    'link_pagamento' => $tipoPagamento === 'credit_card' ? 'https://payment.link/' . uniqid() : null,
                    'created_at' => $date,
                ]);
            }
        }

        $this->command->info('Dados de faturamento criados com sucesso!');
    }
}

