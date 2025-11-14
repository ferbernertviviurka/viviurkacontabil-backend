<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\CompanyService;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CompanyController extends Controller
{
    protected CompanyService $companyService;
    protected LogService $logService;

    public function __construct(CompanyService $companyService, LogService $logService)
    {
        $this->companyService = $companyService;
        $this->logService = $logService;
    }

    /**
     * Display a listing of companies.
     *
     * GET /api/companies
     * 
     * Example Response:
     * {
     *   "data": [
     *     {
     *       "id": 1,
     *       "razao_social": "Empresa XYZ Ltda",
     *       "nome_fantasia": "XYZ",
     *       "cnpj": "12.345.678/0001-90",
     *       "email": "contato@xyz.com"
     *     }
     *   ]
     * }
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Company::class);

        $query = Company::query();

        // Filter by user's company if not master
        if ($request->user()->isNormal()) {
            $query->where('id', $request->user()->company_id);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('razao_social', 'like', "%{$search}%")
                  ->orWhere('nome_fantasia', 'like', "%{$search}%")
                  ->orWhere('cnpj', 'like', "%{$search}%");
            });
        }

        $companies = $query->latest()->paginate(15);

        return response()->json($companies);
    }

    /**
     * Store a newly created company.
     *
     * POST /api/companies
     * 
     * Example Request:
     * {
     *   "razao_social": "Empresa XYZ Ltda",
     *   "nome_fantasia": "XYZ",
     *   "cnpj": "12.345.678/0001-90",
     *   "email": "contato@xyz.com",
     *   "telefone": "(11) 99999-9999"
     * }
     * 
     * Example Response:
     * {
     *   "id": 1,
     *   "razao_social": "Empresa XYZ Ltda",
     *   ...
     * }
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Company::class);

        $validated = $this->companyService->validateCompanyData($request->all());

        // Clean CNPJ before storing
        if (isset($validated['cnpj'])) {
            $validated['cnpj'] = $this->companyService->cleanCnpj($validated['cnpj']);
        }

        // Extract CNAEs from validated data
        $cnaes = $validated['cnaes'] ?? [];
        unset($validated['cnaes']);

        $company = Company::create($validated);

        // Save CNAEs
        if (!empty($cnaes)) {
            foreach ($cnaes as $cnae) {
                \DB::table('company_cnae')->insert([
                    'company_id' => $company->id,
                    'cnae_code' => $cnae['code'],
                    'cnae_description' => $cnae['description'] ?? null,
                    'principal' => $cnae['principal'] ?? false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Log the action
        $this->logService->logModelAction('created', $company, [
            'razao_social' => $company->razao_social,
            'cnpj' => $company->cnpj,
        ]);

        // Load CNAEs for response
        $company->cnaes_list = \DB::table('company_cnae')
            ->where('company_id', $company->id)
            ->get()
            ->map(function ($item) {
                return [
                    'code' => $item->cnae_code,
                    'description' => $item->cnae_description,
                    'principal' => (bool) $item->principal,
                ];
            })
            ->toArray();

        return response()->json($company, 201);
    }

    /**
     * Display the specified company.
     *
     * GET /api/companies/{uuid}
     * 
     * Example Response:
     * {
     *   "id": 1,
     *   "uuid": "550e8400-e29b-41d4-a716-446655440000",
     *   "razao_social": "Empresa XYZ Ltda",
     *   "nome_fantasia": "XYZ",
     *   "cnpj": "12.345.678/0001-90",
     *   ...
     * }
     */
    public function show(Request $request, Company $company)
    {
        Gate::authorize('view', $company);

        // Load key documents status
        $company->loadMissing('documents');
        $keyDocuments = $company->documents()
            ->where('documento_chave', true)
            ->get()
            ->pluck('categoria')
            ->toArray();
        
        $requiredDocuments = ['contrato_social', 'cnpj', 'contrato_assinado'];
        $missingDocuments = array_diff($requiredDocuments, $keyDocuments);
        
        $company->missing_key_documents = $missingDocuments;
        $company->has_all_key_documents = empty($missingDocuments);

        // Load CNAEs
        $company->cnaes_list = \DB::table('company_cnae')
            ->where('company_id', $company->id)
            ->get()
            ->map(function ($item) {
                return [
                    'code' => $item->cnae_code,
                    'description' => $item->cnae_description,
                    'principal' => (bool) $item->principal,
                ];
            })
            ->toArray();

        return response()->json($company);
    }

    /**
     * Update the specified company.
     *
     * PUT/PATCH /api/companies/{uuid}
     * 
     * Example Request:
     * {
     *   "razao_social": "Empresa XYZ Ltda - Updated",
     *   "email": "novo@xyz.com"
     * }
     * 
     * Example Response:
     * {
     *   "id": 1,
     *   "uuid": "550e8400-e29b-41d4-a716-446655440000",
     *   "razao_social": "Empresa XYZ Ltda - Updated",
     *   ...
     * }
     */
    public function update(Request $request, Company $company)
    {
        Gate::authorize('update', $company);

        $validated = $this->companyService->validateCompanyData(
            $request->all(),
            $company->id
        );

        // Clean CNPJ before updating
        if (isset($validated['cnpj'])) {
            $validated['cnpj'] = $this->companyService->cleanCnpj($validated['cnpj']);
        }

        // Extract CNAEs from validated data
        $cnaes = $validated['cnaes'] ?? null;
        unset($validated['cnaes']);

        $company->update($validated);

        // Update CNAEs if provided
        if ($cnaes !== null) {
            // Delete existing CNAEs
            \DB::table('company_cnae')->where('company_id', $company->id)->delete();

            // Insert new CNAEs
            if (!empty($cnaes)) {
                foreach ($cnaes as $cnae) {
                    \DB::table('company_cnae')->insert([
                        'company_id' => $company->id,
                        'cnae_code' => $cnae['code'],
                        'cnae_description' => $cnae['description'] ?? null,
                        'principal' => $cnae['principal'] ?? false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Log the action
        $this->logService->logModelAction('updated', $company, [
            'razao_social' => $company->razao_social,
        ]);

        // Load CNAEs for response
        $company->cnaes_list = \DB::table('company_cnae')
            ->where('company_id', $company->id)
            ->get()
            ->map(function ($item) {
                return [
                    'code' => $item->cnae_code,
                    'description' => $item->cnae_description,
                    'principal' => (bool) $item->principal,
                ];
            })
            ->toArray();

        return response()->json($company->fresh());
    }

    /**
     * Toggle company active status.
     *
     * POST /api/companies/{uuid}/toggle-status
     */
    public function toggleStatus(Request $request, Company $company)
    {
        Gate::authorize('update', $company);

        $company->update([
            'ativo' => !$company->ativo,
        ]);

        return response()->json([
            'message' => $company->ativo ? 'Empresa ativada com sucesso' : 'Empresa desativada com sucesso',
            'ativo' => $company->ativo,
        ]);
    }

    /**
     * Remove the specified company.
     *
     * DELETE /api/companies/{uuid}
     * 
     * Example Response:
     * {
     *   "message": "Empresa removida com sucesso"
     * }
     */
    public function destroy(Request $request, Company $company)
    {
        Gate::authorize('delete', $company);

        // Log before deletion
        $this->logService->logModelAction('deleted', $company, [
            'razao_social' => $company->razao_social,
            'cnpj' => $company->cnpj,
        ]);

        $company->delete();

        return response()->json([
            'message' => 'Empresa removida com sucesso',
        ]);
    }

    /**
     * Get key documents status for a company.
     *
     * GET /api/companies/{uuid}/key-documents
     */
    public function keyDocuments(Request $request, Company $company)
    {
        Gate::authorize('view', $company);

        $requiredDocuments = ['contrato_social', 'cnpj', 'contrato_assinado'];
        $uploadedDocuments = $company->documents()
            ->where('documento_chave', true)
            ->pluck('categoria')
            ->toArray();
        
        $missingDocuments = array_diff($requiredDocuments, $uploadedDocuments);
        
        return response()->json([
            'required' => $requiredDocuments,
            'uploaded' => $uploadedDocuments,
            'missing' => array_values($missingDocuments),
            'complete' => empty($missingDocuments),
        ]);
    }
}
