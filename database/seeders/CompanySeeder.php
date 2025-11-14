<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users
        $users = \App\Models\User::whereIn('email', [
            'joao@example.com',
            'maria@example.com',
            'pedro@example.com'
        ])->get()->keyBy('email');

        // Create specific companies
        $company1 = Company::create([
            'razao_social' => 'Tech Solutions Brasil Ltda',
            'nome_fantasia' => 'Tech Solutions',
            'cnpj' => '12345678000190',
            'ie' => '123.456.789.012',
            'im' => '12345678',
            'endereco' => 'Av. Paulista, 1000',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'cep' => '01310-100',
            'email' => 'contato@techsolutions.com.br',
            'telefone' => '(11) 3000-0000',
            'whatsapp' => '(11) 99999-0000',
            'regime_tributario' => 'simples_nacional',
            'cnae' => '6201-5/00',
            'user_id' => null,
        ]);

        $company2 = Company::create([
            'razao_social' => 'Comercial Alimentos ABC Ltda',
            'nome_fantasia' => 'ABC Alimentos',
            'cnpj' => '98765432000101',
            'ie' => '987.654.321.098',
            'im' => '87654321',
            'endereco' => 'Rua do Comércio, 500',
            'cidade' => 'Rio de Janeiro',
            'estado' => 'RJ',
            'cep' => '20010-000',
            'email' => 'contato@abcalimentos.com.br',
            'telefone' => '(21) 2000-0000',
            'whatsapp' => '(21) 98888-0000',
            'regime_tributario' => 'lucro_presumido',
            'cnae' => '4711-3/02',
            'user_id' => null,
        ]);

        $company3 = Company::create([
            'razao_social' => 'Construtora Oliveira Engenharia Ltda',
            'nome_fantasia' => 'Oliveira Engenharia',
            'cnpj' => '11223344000155',
            'ie' => '112.233.445.566',
            'im' => '11223344',
            'endereco' => 'Av. das Construções, 2000',
            'cidade' => 'Belo Horizonte',
            'estado' => 'MG',
            'cep' => '30110-000',
            'email' => 'contato@oliveiraeng.com.br',
            'telefone' => '(31) 3500-0000',
            'whatsapp' => '(31) 97777-0000',
            'regime_tributario' => 'lucro_real',
            'cnae' => '4120-4/00',
            'user_id' => null,
        ]);

        // Update users with their company_id
        if ($users->has('joao@example.com')) {
            $users->get('joao@example.com')->update(['company_id' => $company1->id]);
        }
        if ($users->has('maria@example.com')) {
            $users->get('maria@example.com')->update(['company_id' => $company2->id]);
        }
        if ($users->has('pedro@example.com')) {
            $users->get('pedro@example.com')->update(['company_id' => $company3->id]);
        }

        // Create additional random companies
        Company::factory()->count(7)->create();
    }
}
