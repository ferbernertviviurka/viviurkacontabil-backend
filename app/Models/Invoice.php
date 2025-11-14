<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'numero',
        'serie',
        'data_emissao',
        'valor',
        'status',
        'destinatario_dados',
        'itens',
        'observacoes',
        'xml_path',
        'pdf_path',
        'provider_id',
        'provider_response',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_emissao' => 'date',
            'destinatario_dados' => 'array',
            'itens' => 'array',
            'provider_response' => 'array',
        ];
    }

    /**
     * Get the company that owns the invoice.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
