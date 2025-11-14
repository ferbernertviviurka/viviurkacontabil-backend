<?php

namespace App\Services;

use App\Contracts\AiProviderInterface;
use App\Models\AiRequest;
use App\Models\KnowledgeBase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AiService
{
    protected AiProviderInterface $aiProvider;

    public function __construct(AiProviderInterface $aiProvider)
    {
        $this->aiProvider = $aiProvider;
    }

    /**
     * Process AI request with context from knowledge bases and previous conversations.
     *
     * @param int $userId
     * @param string $type
     * @param string $prompt
     * @param string|null $conversationUuid
     * @param array|null $previousConversationUuids
     * @return array
     */
    public function processRequest(
        int $userId,
        string $type,
        string $prompt,
        ?string $conversationUuid = null,
        ?array $previousConversationUuids = null
    ): array {
        // Build context from knowledge bases
        $knowledgeContext = $this->buildKnowledgeBaseContext();
        
        // Build context from previous conversations
        $conversationContext = $this->buildConversationContext($previousConversationUuids ?? []);
        
        // Combine all context
        $fullPrompt = $this->buildFullPrompt($prompt, $knowledgeContext, $conversationContext);
        
        // Generate conversation UUID if not provided
        if (!$conversationUuid) {
            $conversationUuid = (string) Str::uuid();
        }

        // Call AI provider
        $result = match ($type) {
            'summarize' => $this->aiProvider->summarize($fullPrompt),
            'suggest' => $this->aiProvider->suggest($fullPrompt),
            default => $this->aiProvider->generate($fullPrompt),
        };

        // Save request to database
        if ($result['success']) {
            AiRequest::create([
                'user_id' => $userId,
                'conversation_uuid' => $conversationUuid,
                'tipo' => $type,
                'prompt' => $prompt,
                'response' => $result['text'],
                'tokens_used' => $result['tokens_used'] ?? null,
                'model' => $result['model'] ?? null,
                'provider' => $result['provider'] ?? 'clarifai',
                'cost' => $this->calculateCost($result['tokens_used'] ?? 0),
                'context' => [
                    'knowledge_bases_used' => count($knowledgeContext),
                    'previous_conversations_used' => count($previousConversationUuids ?? []),
                ],
            ]);
        }

        return array_merge($result, [
            'conversation_uuid' => $conversationUuid,
        ]);
    }

    /**
     * Build context from active knowledge bases.
     */
    protected function buildKnowledgeBaseContext(): array
    {
        $bases = KnowledgeBase::where('ativo', true)
            ->orderBy('ordem')
            ->get();

        return $bases->map(function ($base) {
            return $base->getContentForAi();
        })->toArray();
    }

    /**
     * Build context from database (companies, documents, invoices, etc.).
     */
    protected function buildDatabaseContext(): string
    {
        $context = [];

        // Get companies summary
        $companies = \App\Models\Company::select('id', 'uuid', 'razao_social', 'nome_fantasia', 'cnpj', 'email', 'ativo')
            ->withCount(['documents', 'invoices', 'subscriptions'])
            ->get();

        if ($companies->count() > 0) {
            $companiesSummary = "EMPRESAS CADASTRADAS:\n";
            foreach ($companies as $company) {
                $companiesSummary .= sprintf(
                    "- %s (CNPJ: %s, Email: %s, Status: %s, Documentos: %d, Notas Fiscais: %d, Assinaturas: %d)\n",
                    $company->razao_social,
                    $company->cnpj,
                    $company->email ?? 'N/A',
                    $company->ativo ? 'Ativa' : 'Inativa',
                    $company->documents_count,
                    $company->invoices_count,
                    $company->subscriptions_count
                );
            }
            $context[] = $companiesSummary;
        }

        // Get documents summary
        $documents = \App\Models\Document::select('id', 'company_id', 'categoria', 'documento_chave', 'created_at')
            ->with('company:id,razao_social,cnpj')
            ->latest()
            ->limit(20)
            ->get();

        if ($documents->count() > 0) {
            $documentsSummary = "DOCUMENTOS RECENTES:\n";
            foreach ($documents as $doc) {
                $documentsSummary .= sprintf(
                    "- %s da empresa %s (Categoria: %s, Chave: %s, Data: %s)\n",
                    $doc->categoria,
                    $doc->company->razao_social ?? 'N/A',
                    $doc->categoria,
                    $doc->documento_chave ? 'Sim' : 'Não',
                    $doc->created_at->format('d/m/Y')
                );
            }
            $context[] = $documentsSummary;
        }

        // Get invoices summary
        $invoices = \App\Models\Invoice::select('id', 'company_id', 'numero', 'status', 'valor', 'data_emissao')
            ->with('company:id,razao_social,cnpj')
            ->latest()
            ->limit(20)
            ->get();

        if ($invoices->count() > 0) {
            $invoicesSummary = "NOTAS FISCAIS RECENTES:\n";
            foreach ($invoices as $invoice) {
                $invoicesSummary .= sprintf(
                    "- NFS-e %s da empresa %s (Valor: R$ %s, Status: %s, Data: %s)\n",
                    $invoice->numero,
                    $invoice->company->razao_social ?? 'N/A',
                    number_format($invoice->valor, 2, ',', '.'),
                    $invoice->status,
                    $invoice->data_emissao ? \Carbon\Carbon::parse($invoice->data_emissao)->format('d/m/Y') : 'N/A'
                );
            }
            $context[] = $invoicesSummary;
        }

        // Get charges summary
        $charges = \App\Models\Boleto::select('id', 'company_id', 'valor', 'status', 'tipo_pagamento', 'vencimento')
            ->with('company:id,razao_social,cnpj')
            ->latest()
            ->limit(20)
            ->get();

        if ($charges->count() > 0) {
            $chargesSummary = "COBRANÇAS RECENTES:\n";
            foreach ($charges as $charge) {
                $chargesSummary .= sprintf(
                    "- Cobrança de R$ %s da empresa %s (Tipo: %s, Status: %s, Vencimento: %s)\n",
                    number_format($charge->valor, 2, ',', '.'),
                    $charge->company->razao_social ?? 'N/A',
                    $charge->tipo_pagamento,
                    $charge->status,
                    $charge->vencimento ? \Carbon\Carbon::parse($charge->vencimento)->format('d/m/Y') : 'N/A'
                );
            }
            $context[] = $chargesSummary;
        }

        return implode("\n\n", $context);
    }

    /**
     * Build context from previous conversations.
     */
    protected function buildConversationContext(array $conversationUuids): array
    {
        if (empty($conversationUuids)) {
            return [];
        }

        $conversations = AiRequest::whereIn('conversation_uuid', $conversationUuids)
            ->orderBy('created_at')
            ->get();

        return $conversations->map(function ($request) {
            return "Pergunta: {$request->prompt}\nResposta: {$request->response}";
        })->toArray();
    }

    /**
     * Build full prompt with context.
     */
    protected function buildFullPrompt(string $prompt, array $knowledgeContext, array $conversationContext): string
    {
        $systemPrompt = "Você é um especialista em contabilidade com acesso ao banco de dados do sistema Viviurka Contábil. Use o contexto fornecido para responder às perguntas de forma precisa e profissional.\n\n";
        
        // Add database context
        $databaseContext = $this->buildDatabaseContext();
        if (!empty($databaseContext)) {
            $systemPrompt .= "CONTEXTO DO BANCO DE DADOS:\n" . $databaseContext . "\n\n";
        }
        
        if (!empty($knowledgeContext)) {
            $systemPrompt .= "CONTEXTO DE BASES DE CONHECIMENTO:\n" . implode("\n\n", $knowledgeContext) . "\n\n";
        }
        
        if (!empty($conversationContext)) {
            $systemPrompt .= "CONVERSAS ANTERIORES:\n" . implode("\n\n", $conversationContext) . "\n\n";
        }
        
        $systemPrompt .= "PERGUNTA ATUAL: {$prompt}";
        
        return $systemPrompt;
    }

    /**
     * Get user's AI request history.
     *
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHistory(int $userId, int $limit = 10)
    {
        return AiRequest::where('user_id', $userId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get conversations (grouped by conversation_uuid).
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getConversations(int $userId)
    {
        // Get all conversations with aggregated data
        $conversations = AiRequest::where('user_id', $userId)
            ->whereNotNull('conversation_uuid')
            ->select('conversation_uuid')
            ->selectRaw('MAX(created_at) as last_message_at')
            ->selectRaw('COUNT(*) as message_count')
            ->selectRaw('SUM(COALESCE(tokens_used, 0)) as total_tokens')
            ->groupBy('conversation_uuid')
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function ($conv) {
                // Get first prompt for title (more efficient: get all at once)
                $firstRequest = AiRequest::where('conversation_uuid', $conv->conversation_uuid)
                    ->orderBy('created_at')
                    ->first(['prompt']);
                
                $conv->first_prompt = $firstRequest->prompt ?? null;
                $conv->total_tokens = (int) ($conv->total_tokens ?? 0);
                
                return $conv;
            });

        return $conversations;
    }

    /**
     * Get messages from a specific conversation.
     *
     * @param string $conversationUuid
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getConversationMessages(string $conversationUuid)
    {
        return AiRequest::where('conversation_uuid', $conversationUuid)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Calculate cost based on tokens used.
     * 
     * @param int $tokens
     * @return float
     */
    private function calculateCost(int $tokens): float
    {
        // Example pricing: $0.002 per 1K tokens
        return ($tokens / 1000) * 0.002;
    }
}

