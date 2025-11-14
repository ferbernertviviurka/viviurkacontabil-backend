<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DocumentService
{
    /**
     * Validate document data.
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function validateDocumentData(array $data): array
    {
        // Accept either company_id (numeric) or company_uuid (UUID string)
        $rules = [
            'company_id' => 'nullable|exists:companies,id',
            'company_uuid' => 'nullable|exists:companies,uuid',
            'categoria' => 'required|in:contrato_social,cnpj,contrato_assinado,contrato,certidao,balanco,outros',
            'file' => 'required|file|max:10240', // 10MB max
        ];

        // At least one company identifier is required
        if (empty($data['company_id']) && empty($data['company_uuid'])) {
            $rules['company_id'] = 'required';
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Upload and store a document.
     *
     * @param UploadedFile $file
     * @param array $data
     * @param int $userId
     * @return Document
     */
    public function uploadDocument(UploadedFile $file, array $data, int $userId): Document
    {
        // Get company - either by ID or UUID
        $company = null;
        if (!empty($data['company_uuid'])) {
            $company = Company::where('uuid', $data['company_uuid'])->firstOrFail();
        } elseif (!empty($data['company_id'])) {
            $company = Company::findOrFail($data['company_id']);
        } else {
            throw new \Exception('Company ID or UUID is required');
        }

        // Use UUID for directory structure
        $companyUuid = $company->uuid;
        
        // Sanitize filename - remove special characters but keep extension
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nameWithoutExt);
        $filename = time() . '_' . $sanitizedName . '.' . $extension;
        
        // Store file in directory structure: documents/{company_uuid}/{filename}
        $directory = 'documents/' . $companyUuid;
        
        // Ensure directory exists in storage/app/
        $fullDirectory = storage_path('app/' . $directory);
        if (!is_dir($fullDirectory)) {
            mkdir($fullDirectory, 0755, true);
        }
        
        // Store file directly in the correct location
        // Use storeAs with public disk or move manually
        $fullPath = $fullDirectory . '/' . $filename;
        
        // Move uploaded file to destination
        if (!move_uploaded_file($file->getRealPath(), $fullPath)) {
            // Fallback: copy if move fails
            copy($file->getRealPath(), $fullPath);
        }
        
        // Return relative path from storage/app/
        $path = $directory . '/' . $filename;

        // Check if it's a key document
        $keyDocuments = ['contrato_social', 'cnpj', 'contrato_assinado'];
        $isKeyDocument = in_array($data['categoria'], $keyDocuments);

        // Create document record
        $document = Document::create([
            'company_id' => $company->id,
            'uploaded_by' => $userId,
            'nome' => $originalName,
            'categoria' => $data['categoria'],
            'caminho' => $path,
            'tamanho' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'documento_chave' => $isKeyDocument,
        ]);

        return $document;
    }

    /**
     * Get document file path.
     *
     * @param Document $document
     * @return string
     */
    public function getDocumentPath(Document $document): string
    {
        // Load company relationship if not loaded
        if (!$document->relationLoaded('company')) {
            $document->load('company');
        }

        $path = $document->caminho;
        $fullPath = storage_path('app/' . $path);

        // Check if file exists
        if (file_exists($fullPath)) {
            return $fullPath;
        }

        // If file doesn't exist, check if it's using old format (company_id instead of uuid)
        // Try to find in UUID-based directory
        if ($document->company && $document->company->uuid) {
            $uuidPath = 'documents/' . $document->company->uuid . '/' . basename($path);
            $uuidFullPath = storage_path('app/' . $uuidPath);
            
            if (file_exists($uuidFullPath)) {
                // Update document path to new format
                $document->caminho = $uuidPath;
                $document->save();
                return $uuidFullPath;
            }
        }

        // Return original path even if file doesn't exist (let download method handle error)
        return $fullPath;
    }

    /**
     * Delete a document.
     *
     * @param Document $document
     * @return bool
     */
    public function deleteDocument(Document $document): bool
    {
        // Delete file from storage
        $path = $document->caminho;
        
        // Try standard location
        $fullPath = storage_path('app/' . $path);
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
        
        // Try private location (for migration)
        $privatePath = storage_path('app/private/' . $path);
        if (file_exists($privatePath)) {
            @unlink($privatePath);
        }

        // Delete record
        return $document->delete();
    }
}

