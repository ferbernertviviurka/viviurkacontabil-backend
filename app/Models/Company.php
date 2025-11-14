<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (empty($company->uuid)) {
                $company->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'uuid',
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'ie',
        'im',
        'endereco',
        'cidade',
        'estado',
        'cep',
        'email',
        'telefone',
        'whatsapp',
        'regime_tributario',
        'user_id',
        'responsavel_financeiro_nome',
        'responsavel_financeiro_telefone',
        'responsavel_financeiro_email',
        'responsavel_financeiro_whatsapp',
        'ativo',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the company.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the invoices for the company.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the boletos for the company.
     */
    public function boletos(): HasMany
    {
        return $this->hasMany(Boleto::class);
    }

    /**
     * Get the documents for the company.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the payment methods for the company.
     */
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    /**
     * Get the subscriptions for the company.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get CNAEs as array from pivot table.
     */
    public function getCnaesListAttribute(): array
    {
        return \DB::table('company_cnae')
            ->where('company_id', $this->id)
            ->get()
            ->map(function ($item) {
                return [
                    'code' => $item->cnae_code,
                    'description' => $item->cnae_description,
                    'principal' => (bool) $item->principal,
                ];
            })
            ->toArray();
    }
}
