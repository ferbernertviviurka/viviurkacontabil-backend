<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KnowledgeBaseController extends Controller
{
    /**
     * Display a listing of knowledge bases.
     *
     * GET /api/knowledge-bases
     */
    public function index(Request $request)
    {
        // Only master can access
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        $bases = KnowledgeBase::orderBy('ordem')->orderBy('nome')->get();

        return response()->json($bases);
    }

    /**
     * Store a newly created knowledge base.
     *
     * POST /api/knowledge-bases
     */
    public function store(Request $request)
    {
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'tipo' => 'required|in:pdf,texto,regra,contexto',
            'arquivo' => 'required_if:tipo,pdf|file|mimes:pdf|max:10240', // 10MB max
            'conteudo' => 'required_if:tipo,texto,regra,contexto|string',
            'ativo' => 'boolean',
            'ordem' => 'integer|min:0',
        ]);

        $data = [
            'nome' => $request->nome,
            'descricao' => $request->descricao,
            'tipo' => $request->tipo,
            'ativo' => $request->boolean('ativo', true),
            'ordem' => $request->integer('ordem', 0),
        ];

        // Handle file upload for PDF
        if ($request->tipo === 'pdf' && $request->hasFile('arquivo')) {
            $file = $request->file('arquivo');
            $path = $file->store('knowledge-bases', 'public');
            $data['caminho_arquivo'] = $path;
        } else {
            $data['conteudo'] = $request->conteudo;
        }

        $knowledgeBase = KnowledgeBase::create($data);

        return response()->json($knowledgeBase, 201);
    }

    /**
     * Update the specified knowledge base.
     *
     * PUT /api/knowledge-bases/{id}
     */
    public function update(Request $request, KnowledgeBase $knowledgeBase)
    {
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'nome' => 'sometimes|string|max:255',
            'descricao' => 'nullable|string',
            'tipo' => 'sometimes|in:pdf,texto,regra,contexto',
            'arquivo' => 'sometimes|file|mimes:pdf|max:10240',
            'conteudo' => 'sometimes|string',
            'ativo' => 'boolean',
            'ordem' => 'integer|min:0',
        ]);

        if ($request->has('nome')) {
            $knowledgeBase->nome = $request->nome;
        }
        if ($request->has('descricao')) {
            $knowledgeBase->descricao = $request->descricao;
        }
        if ($request->has('ativo')) {
            $knowledgeBase->ativo = $request->boolean('ativo');
        }
        if ($request->has('ordem')) {
            $knowledgeBase->ordem = $request->integer('ordem');
        }

        // Handle file upload
        if ($request->hasFile('arquivo')) {
            // Delete old file
            if ($knowledgeBase->caminho_arquivo) {
                Storage::disk('public')->delete($knowledgeBase->caminho_arquivo);
            }
            $file = $request->file('arquivo');
            $path = $file->store('knowledge-bases', 'public');
            $knowledgeBase->caminho_arquivo = $path;
        }

        if ($request->has('conteudo')) {
            $knowledgeBase->conteudo = $request->conteudo;
        }

        $knowledgeBase->save();

        return response()->json($knowledgeBase);
    }

    /**
     * Toggle active status.
     *
     * POST /api/knowledge-bases/{id}/toggle
     */
    public function toggle(Request $request, KnowledgeBase $knowledgeBase)
    {
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        $knowledgeBase->ativo = !$knowledgeBase->ativo;
        $knowledgeBase->save();

        return response()->json([
            'message' => 'Status atualizado',
            'ativo' => $knowledgeBase->ativo,
        ]);
    }

    /**
     * Remove the specified knowledge base.
     *
     * DELETE /api/knowledge-bases/{id}
     */
    public function destroy(Request $request, KnowledgeBase $knowledgeBase)
    {
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        // Delete file if exists
        if ($knowledgeBase->caminho_arquivo) {
            Storage::disk('public')->delete($knowledgeBase->caminho_arquivo);
        }

        $knowledgeBase->delete();

        return response()->json(['message' => 'Base de conhecimento excluída']);
    }

    /**
     * Get active knowledge bases for AI context.
     *
     * GET /api/knowledge-bases/active
     */
    public function active(Request $request)
    {
        // Only master can access
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        $bases = KnowledgeBase::where('ativo', true)
            ->orderBy('ordem')
            ->get()
            ->map(function ($base) {
                return [
                    'id' => $base->id,
                    'nome' => $base->nome,
                    'conteudo' => $base->getContentForAi(),
                ];
            });

        return response()->json($bases);
    }
}

