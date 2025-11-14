<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentService;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    protected DocumentService $documentService;
    protected LogService $logService;

    public function __construct(DocumentService $documentService, LogService $logService)
    {
        $this->documentService = $documentService;
        $this->logService = $logService;
    }

    /**
     * Display a listing of documents.
     *
     * GET /api/documents
     */
    public function index(Request $request)
    {
        $query = Document::with(['company', 'uploader']);

        // Filter by company if user is not master
        if ($request->user()->isNormal()) {
            $query->whereHas('company', function ($q) use ($request) {
                $q->where('id', $request->user()->company_id);
            });
        }

        // Filter by company_id (numeric) if provided
        if ($request->has('company_id')) {
            $companyIds = is_array($request->company_id) ? $request->company_id : [$request->company_id];
            $query->whereIn('company_id', $companyIds);
        }

        // Filter by company_uuid if provided
        if ($request->has('company_uuid')) {
            $companyUuids = is_array($request->company_uuid) ? $request->company_uuid : [$request->company_uuid];
            $query->whereHas('company', function ($q) use ($companyUuids) {
                $q->whereIn('uuid', $companyUuids);
            });
        }

        // Filter by categories (array)
        if ($request->has('categorias')) {
            $categorias = is_array($request->categorias) ? $request->categorias : explode(',', $request->categorias);
            $query->whereIn('categoria', $categorias);
        } elseif ($request->has('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $documents = $query->latest()->paginate(20);

        return response()->json($documents);
    }

    /**
     * Store a newly created document.
     *
     * POST /api/documents
     *
     * Form Data:
     * - file: The document file
     * - company_id: Company ID (numeric) - optional if company_uuid is provided
     * - company_uuid: Company UUID - optional if company_id is provided
     * - categoria: Document category
     */
    public function store(Request $request)
    {
        // If user is not master, automatically use their company
        if ($request->user()->isNormal() && $request->user()->company_id) {
            $company = \App\Models\Company::find($request->user()->company_id);
            if ($company) {
                $request->merge(['company_uuid' => $company->uuid]);
            }
        }

        $validated = $this->documentService->validateDocumentData([
            'company_id' => $request->company_id,
            'company_uuid' => $request->company_uuid,
            'categoria' => $request->categoria,
            'file' => $request->file('file'),
        ]);

        $document = $this->documentService->uploadDocument(
            $request->file('file'),
            $validated,
            $request->user()->id
        );

        // Log the action
        $this->logService->logModelAction('created', $document, [
            'company_id' => $document->company_id,
            'company_uuid' => $document->company->uuid ?? null,
            'categoria' => $document->categoria,
            'nome' => $document->nome,
        ]);

        return response()->json($document->load(['company', 'uploader']), 201);
    }

    /**
     * Display the specified document.
     *
     * GET /api/documents/{id}
     */
    public function show(Request $request, Document $document)
    {
        // Check authorization
        if ($request->user()->isNormal()) {
            if ($document->company->user_id !== $request->user()->id) {
                abort(403, 'Unauthorized');
            }
        }

        return response()->json($document->load(['company', 'uploader']));
    }

    /**
     * Download the specified document.
     *
     * GET /api/documents/{id}/download
     */
    public function download(Request $request, Document $document)
    {
        // Check authorization
        if ($request->user()->isNormal()) {
            if ($document->company->user_id !== $request->user()->id) {
                abort(403, 'Unauthorized');
            }
        }

        $path = $this->documentService->getDocumentPath($document);

        if (!file_exists($path)) {
            abort(404, 'Arquivo não encontrado');
        }

        return response()->download($path, $document->nome);
    }

    /**
     * Remove the specified document.
     *
     * DELETE /api/documents/{id}
     */
    public function destroy(Request $request, Document $document)
    {
        // Check authorization
        if ($request->user()->isNormal()) {
            if ($document->company->user_id !== $request->user()->id) {
                abort(403, 'Unauthorized');
            }
        }

        $this->documentService->deleteDocument($document);

        return response()->json([
            'message' => 'Documento removido com sucesso',
        ]);
    }
}
