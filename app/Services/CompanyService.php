<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CompanyService
{
    /**
     * Validate company data.
     *
     * @param array $data
     * @param int|null $companyId
     * @return array
     * @throws ValidationException
     */
    public function validateCompanyData(array $data, ?int $companyId = null): array
    {
        $rules = [
            'razao_social' => 'required|string|max:255',
            'nome_fantasia' => 'required|string|max:255',
            'cnpj' => [
                'required',
                'string',
                'max:18',
                'unique:companies,cnpj' . ($companyId ? ',' . $companyId : ''),
            ],
            'ie' => 'required|string|max:255',
            'im' => 'required|string|max:255',
            'endereco' => 'required|string',
            'cidade' => 'required|string|max:255',
            'estado' => 'required|string|size:2',
            'cep' => 'required|string|size:8|regex:/^[0-9]{8}$/',
            'email' => 'required|email|max:255',
            'telefone' => 'required|string|max:20',
            'whatsapp' => 'required|string|max:20',
            'regime_tributario' => 'required|in:simples_nacional,lucro_presumido,lucro_real',
            'cnaes' => 'required|array|min:1',
            'cnaes.*.code' => 'required|string|max:20',
            'cnaes.*.description' => 'nullable|string|max:255',
            'cnaes.*.principal' => 'nullable|boolean',
            'user_id' => 'nullable|exists:users,id',
            'responsavel_financeiro_nome' => 'required|string|max:255',
            'responsavel_financeiro_telefone' => 'required|string|max:20',
            'responsavel_financeiro_email' => 'required|email|max:255',
            'responsavel_financeiro_whatsapp' => 'required|string|max:20',
            'ativo' => 'nullable|boolean',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Format CNPJ for display.
     *
     * @param string $cnpj
     * @return string
     */
    public function formatCnpj(string $cnpj): string
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        if (strlen($cnpj) === 14) {
            return substr($cnpj, 0, 2) . '.' . 
                   substr($cnpj, 2, 3) . '.' .
                   substr($cnpj, 5, 3) . '/' .
                   substr($cnpj, 8, 4) . '-' .
                   substr($cnpj, 12, 2);
        }

        return $cnpj;
    }

    /**
     * Clean CNPJ (remove formatting).
     *
     * @param string $cnpj
     * @return string
     */
    public function cleanCnpj(string $cnpj): string
    {
        return preg_replace('/[^0-9]/', '', $cnpj);
    }

    /**
     * Check if user can access company.
     *
     * @param \App\Models\User $user
     * @param Company $company
     * @return bool
     */
    public function userCanAccessCompany($user, Company $company): bool
    {
        // Master can access all companies
        if ($user->isMaster()) {
            return true;
        }

        // Normal users can only access their own company
        return $user->company_id === $company->id;
    }
}

