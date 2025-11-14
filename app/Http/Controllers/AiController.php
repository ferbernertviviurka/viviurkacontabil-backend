<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    protected AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Summarize text using AI.
     *
     * POST /api/ai/summarize
     * Body: { "text": "..." }
     */
    public function summarize(Request $request)
    {
        $request->validate([
            'text' => 'required|string|min:50',
        ]);

        $result = $this->aiService->processRequest(
            $request->user()->id,
            'summarize',
            $request->text
        );

        if (!$result['success']) {
            return response()->json(['error' => $result['error'] ?? 'Erro ao processar'], 500);
        }

        return response()->json([
            'summary' => $result['text'],
            'tokens_used' => $result['tokens_used'] ?? null,
        ]);
    }

    /**
     * Generate email using AI.
     *
     * POST /api/ai/generate-email
     * Body: { "context": "..." }
     */
    public function generateEmail(Request $request)
    {
        $request->validate([
            'context' => 'required|string',
        ]);

        $prompt = "Gere um email profissional para um cliente de contabilidade com base no seguinte contexto:\n\n{$request->context}";

        $result = $this->aiService->processRequest(
            $request->user()->id,
            'generate_email',
            $prompt
        );

        if (!$result['success']) {
            return response()->json(['error' => $result['error'] ?? 'Erro ao processar'], 500);
        }

        return response()->json([
            'email' => $result['text'],
            'tokens_used' => $result['tokens_used'] ?? null,
        ]);
    }

    /**
     * Get AI suggestions.
     *
     * POST /api/ai/suggestions
     * Body: { "context": "..." }
     */
    public function suggestions(Request $request)
    {
        $request->validate([
            'context' => 'required|string',
        ]);

        $result = $this->aiService->processRequest(
            $request->user()->id,
            'suggest',
            $request->context
        );

        if (!$result['success']) {
            return response()->json(['error' => $result['error'] ?? 'Erro ao processar'], 500);
        }

        return response()->json([
            'suggestions' => $result['text'],
            'tokens_used' => $result['tokens_used'] ?? null,
        ]);
    }

    /**
     * Get AI request history.
     *
     * GET /api/ai/history
     */
    public function history(Request $request)
    {
        $history = $this->aiService->getHistory($request->user()->id, 20);

        return response()->json($history);
    }

    /**
     * Chat with AI (improved with UUID and context).
     *
     * POST /api/ai/chat
     */
    public function chat(Request $request)
    {
        // Only master can access
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'message' => 'required|string',
            'conversation_uuid' => 'nullable|string',
            'previous_conversation_uuids' => 'nullable|array',
        ]);

        $result = $this->aiService->processRequest(
            $request->user()->id,
            'chat',
            $request->message,
            $request->conversation_uuid,
            $request->previous_conversation_uuids
        );

        if (!$result['success']) {
            return response()->json(['error' => $result['error'] ?? 'Erro ao processar'], 500);
        }

        return response()->json([
            'message' => $result['text'],
            'conversation_uuid' => $result['conversation_uuid'],
            'tokens_used' => $result['tokens_used'] ?? null,
            'provider' => $result['provider'] ?? 'clarifai',
            'model' => $result['model'] ?? null,
        ]);
    }

    /**
     * Get conversations list.
     *
     * GET /api/ai/conversations
     */
    public function conversations(Request $request)
    {
        // Only master can access
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        $conversations = $this->aiService->getConversations($request->user()->id);

        return response()->json($conversations);
    }

    /**
     * Get messages from a conversation.
     *
     * GET /api/ai/conversations/{uuid}
     */
    public function getConversation(Request $request, string $uuid)
    {
        // Only master can access
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        $messages = $this->aiService->getConversationMessages($uuid);

        return response()->json($messages);
    }
}
