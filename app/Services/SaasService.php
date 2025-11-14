<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SaasService
{
    /**
     * Create subscription with plan.
     */
    public function createSubscriptionWithPlan(Company $company, SubscriptionPlan $plan, array $data): Subscription
    {
        $billingCycle = $data['billing_cycle'] ?? 'monthly';
        $price = $plan->getPriceForCycle($billingCycle);
        
        // Calculate trial period
        $trialEndsAt = null;
        $isTrial = false;
        if ($plan->trial_days > 0) {
            $trialEndsAt = Carbon::now()->addDays($plan->trial_days);
            $isTrial = true;
        }

        // Calculate billing period
        $currentPeriodStart = Carbon::now();
        $currentPeriodEnd = match ($billingCycle) {
            'monthly' => $currentPeriodStart->copy()->addMonth(),
            'quarterly' => $currentPeriodStart->copy()->addMonths(3),
            'semiannual' => $currentPeriodStart->copy()->addMonths(6),
            'annual' => $currentPeriodStart->copy()->addYear(),
            default => $currentPeriodStart->copy()->addMonth(),
        };

        // If trial, extend period end
        if ($isTrial) {
            $currentPeriodEnd = $trialEndsAt->copy()->add($this->getBillingCycleInterval($billingCycle));
        }

        $subscription = Subscription::create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'plano' => $plan->name,
            'recorrencia' => $this->mapBillingCycleToRecorrencia($billingCycle),
            'valor' => $price,
            'dia_vencimento' => $data['dia_vencimento'] ?? 10,
            'status' => 'active',
            'proxima_cobranca' => $isTrial ? $trialEndsAt : $currentPeriodEnd,
            'trial_ends_at' => $trialEndsAt,
            'current_period_start' => $currentPeriodStart,
            'current_period_end' => $currentPeriodEnd,
            'billing_cycle' => $billingCycle,
            'usage_limits' => [],
            'is_trial' => $isTrial,
            'auto_renew' => $data['auto_renew'] ?? true,
            'pago_mes_atual' => false,
            'meses_pagos' => [],
        ]);

        return $subscription;
    }

    /**
     * Upgrade subscription to a new plan.
     */
    public function upgradeSubscription(Subscription $subscription, SubscriptionPlan $newPlan, ?string $billingCycle = null): Subscription
    {
        $billingCycle = $billingCycle ?? $subscription->billing_cycle;
        $newPrice = $newPlan->getPriceForCycle($billingCycle);
        
        // Calculate prorated amount (simplified)
        $daysRemaining = Carbon::now()->diffInDays($subscription->current_period_end);
        $totalDays = Carbon::parse($subscription->current_period_start)->diffInDays($subscription->current_period_end);
        $proratedAmount = ($newPrice / $totalDays) * $daysRemaining;

        $subscription->update([
            'plan_id' => $newPlan->id,
            'plano' => $newPlan->name,
            'valor' => $newPrice,
            'billing_cycle' => $billingCycle,
            'recorrencia' => $this->mapBillingCycleToRecorrencia($billingCycle),
        ]);

        Log::info('Subscription upgraded', [
            'subscription_id' => $subscription->id,
            'old_plan' => $subscription->plano,
            'new_plan' => $newPlan->name,
        ]);

        return $subscription->fresh();
    }

    /**
     * Downgrade subscription to a new plan.
     */
    public function downgradeSubscription(Subscription $subscription, SubscriptionPlan $newPlan, ?string $billingCycle = null): Subscription
    {
        // Downgrade takes effect at the end of current period
        $billingCycle = $billingCycle ?? $subscription->billing_cycle;
        $newPrice = $newPlan->getPriceForCycle($billingCycle);

        $subscription->update([
            'plan_id' => $newPlan->id,
            'plano' => $newPlan->name,
            'valor' => $newPrice,
            'billing_cycle' => $billingCycle,
            'recorrencia' => $this->mapBillingCycleToRecorrencia($billingCycle),
        ]);

        Log::info('Subscription downgraded', [
            'subscription_id' => $subscription->id,
            'old_plan' => $subscription->plano,
            'new_plan' => $newPlan->name,
        ]);

        return $subscription->fresh();
    }

    /**
     * Check if subscription can use feature.
     */
    public function canUseFeature(Subscription $subscription, string $feature): bool
    {
        if (!$subscription->plan) {
            return false;
        }

        return $subscription->plan->hasFeature($feature);
    }

    /**
     * Check if subscription is within limit.
     */
    public function isWithinLimit(Subscription $subscription, string $limitKey, int $currentUsage): bool
    {
        if (!$subscription->plan) {
            return false;
        }

        $limit = $subscription->plan->getLimit($limitKey, -1);
        
        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        return $currentUsage < $limit;
    }

    /**
     * Track usage for subscription.
     */
    public function trackUsage(Subscription $subscription, string $limitKey, int $amount = 1): void
    {
        $usage = $subscription->usage_limits ?? [];
        $usage[$limitKey] = ($usage[$limitKey] ?? 0) + $amount;
        
        $subscription->update([
            'usage_limits' => $usage,
        ]);
    }

    /**
     * Reset usage limits for new billing period.
     */
    public function resetUsageLimits(Subscription $subscription): void
    {
        $subscription->update([
            'usage_limits' => [],
        ]);
    }

    /**
     * Map billing cycle to recorrencia.
     */
    protected function mapBillingCycleToRecorrencia(string $billingCycle): string
    {
        return match ($billingCycle) {
            'monthly' => 'mensal',
            'quarterly' => 'trimestral',
            'semiannual' => 'semestral',
            'annual' => 'anual',
            default => 'mensal',
        };
    }

    /**
     * Get billing cycle interval.
     */
    protected function getBillingCycleInterval(string $billingCycle): \DateInterval
    {
        return match ($billingCycle) {
            'monthly' => new \DateInterval('P1M'),
            'quarterly' => new \DateInterval('P3M'),
            'semiannual' => new \DateInterval('P6M'),
            'annual' => new \DateInterval('P1Y'),
            default => new \DateInterval('P1M'),
        };
    }
}

