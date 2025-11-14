<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CepController extends Controller
{
    /**
     * Search CEP via ViaCEP API.
     *
     * GET /api/cep/{cep}
     */
    public function search(string $cep)
    {
        // Remove formatting
        $cep = preg_replace('/[^0-9]/', '', $cep);

        if (strlen($cep) !== 8) {
            return response()->json(['error' => 'CEP inválido'], 400);
        }

        try {
            $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$cep}/json/");

            if ($response->failed() || isset($response->json()['erro'])) {
                return response()->json(['error' => 'CEP não encontrado'], 404);
            }

            $data = $response->json();

            return response()->json([
                'cep' => $data['cep'] ?? $cep,
                'logradouro' => $data['logradouro'] ?? '',
                'complemento' => $data['complemento'] ?? '',
                'bairro' => $data['bairro'] ?? '',
                'localidade' => $data['localidade'] ?? '',
                'uf' => $data['uf'] ?? '',
                'ibge' => $data['ibge'] ?? '',
                'gia' => $data['gia'] ?? '',
                'ddd' => $data['ddd'] ?? '',
                'siafi' => $data['siafi'] ?? '',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao buscar CEP'], 500);
        }
    }
}

