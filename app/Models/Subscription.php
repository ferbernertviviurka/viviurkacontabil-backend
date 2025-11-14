<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'plan_id',
        'payment_method_id',
        'plano',
        'recorrencia',
        'valor',
        'dia_vencimento',
        'status',
        'proxima_cobranca',
        'data_cancelamento',
        'provider_subscription_id',
        'pago_mes_atual',
        'meses_pagos',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'billing_cycle',
        'usage_limits',
        'is_trial',
        'auto_renew',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'proxima_cobranca' => 'date',
            'data_cancelamento' => 'date',
            'trial_ends_at' => 'date',
            'current_period_start' => 'date',
            'current_period_end' => 'date',
            'pago_mes_atual' => 'boolean',
            'meses_pagos' => 'array',
            'usage_limits' => 'array',
            'is_trial' => 'boolean',
            'auto_renew' => 'boolean',
        ];
    }

    /**
     * Get the company that owns the subscription.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the plan for the subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Get the payment method for the subscription.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the monthly payments for the subscription.
     */
    public function monthlyPayments(): HasMany
    {
        return $this->hasMany(MonthlyPayment::class);
    }
}
