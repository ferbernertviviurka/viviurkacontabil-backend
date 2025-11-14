<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_quarterly',
        'price_semiannual',
        'price_annual',
        'features',
        'limits',
        'trial_days',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'limits' => 'array',
            'price_monthly' => 'decimal:2',
            'price_quarterly' => 'decimal:2',
            'price_semiannual' => 'decimal:2',
            'price_annual' => 'decimal:2',
            'trial_days' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the subscriptions for this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    /**
     * Get price for billing cycle.
     */
    public function getPriceForCycle(string $cycle): float
    {
        return match ($cycle) {
            'monthly' => $this->price_monthly,
            'quarterly' => $this->price_quarterly ?? ($this->price_monthly * 3 * 0.9), // 10% discount
            'semiannual' => $this->price_semiannual ?? ($this->price_monthly * 6 * 0.85), // 15% discount
            'annual' => $this->price_annual ?? ($this->price_monthly * 12 * 0.8), // 20% discount
            default => $this->price_monthly,
        };
    }

    /**
     * Check if feature is included.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Get limit value.
     */
    public function getLimit(string $limitKey, $default = null)
    {
        return $this->limits[$limitKey] ?? $default;
    }
}

