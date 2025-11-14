<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'razao_social' => fake()->company() . ' Ltda',
            'nome_fantasia' => fake()->company(),
            'cnpj' => $this->generateCnpj(),
            'ie' => fake()->numerify('###.###.###.###'),
            'im' => fake()->numerify('########'),
            'endereco' => fake()->streetAddress(),
            'cidade' => fake()->city(),
            'estado' => fake()->stateAbbr(),
            'cep' => fake()->postcode(),
            'email' => fake()->companyEmail(),
            'telefone' => fake()->phoneNumber(),
            'whatsapp' => fake()->phoneNumber(),
            'regime_tributario' => fake()->randomElement(['simples_nacional', 'lucro_presumido', 'lucro_real']),
            'cnae' => fake()->numerify('####-#/##'),
        ];
    }

    /**
     * Generate a valid CNPJ format (not validated, just format).
     */
    private function generateCnpj(): string
    {
        return fake()->numerify('##############');
    }
}
