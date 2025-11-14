<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'descricao',
        'tipo',
        'caminho_arquivo',
        'conteudo',
        'ativo',
        'ordem',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'ordem' => 'integer',
        ];
    }

    /**
     * Get content for AI context.
     */
    public function getContentForAi(): string
    {
        if ($this->tipo === 'pdf' && $this->caminho_arquivo) {
            // Em produção, extrair texto do PDF
            return "Documento: {$this->nome}";
        }

        return $this->conteudo ?? '';
    }
}

