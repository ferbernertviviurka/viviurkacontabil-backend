<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Boleto extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'tipo_pagamento',
        'valor',
        'vencimento',
        'status',
        'descricao',
        'linha_digitavel',
        'codigo_barras',
        'url_pdf',
        'provider_id',
        'provider_response',
        'data_pagamento',
        'chave_pix',
        'qr_code_pix',
        'link_pagamento',
        'dados_pagamento',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vencimento' => 'date',
            'data_pagamento' => 'date',
            'provider_response' => 'array',
            'dados_pagamento' => 'array',
        ];
    }

    /**
     * Get the company that owns the boleto.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
