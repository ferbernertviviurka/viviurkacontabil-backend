<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthlyPayment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'subscription_id',
        'company_id',
        'mes_referencia',
        'valor',
        'data_vencimento',
        'data_pagamento',
        'status',
        'metodo_pagamento',
        'dados_pagamento',
        'chave_pix',
        'qr_code_pix',
        'boleto_url',
        'link_pagamento',
        'email_enviado',
        'email_enviado_em',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_vencimento' => 'date',
            'data_pagamento' => 'date',
            'dados_pagamento' => 'array',
            'email_enviado' => 'boolean',
            'email_enviado_em' => 'datetime',
        ];
    }

    /**
     * Get the subscription that owns the monthly payment.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the company that owns the monthly payment.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

