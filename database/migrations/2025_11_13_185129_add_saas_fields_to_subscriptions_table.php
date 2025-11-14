<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('company_id')->constrained('subscription_plans')->onDelete('set null');
            $table->date('trial_ends_at')->nullable()->after('proxima_cobranca');
            $table->date('current_period_start')->nullable();
            $table->date('current_period_end')->nullable();
            $table->string('billing_cycle')->default('monthly'); // monthly, quarterly, semiannual, annual
            $table->json('usage_limits')->nullable(); // Tracking de uso (invoices_count, etc)
            $table->boolean('is_trial')->default(false);
            $table->boolean('auto_renew')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn([
                'plan_id',
                'trial_ends_at',
                'current_period_start',
                'current_period_end',
                'billing_cycle',
                'usage_limits',
                'is_trial',
                'auto_renew',
            ]);
        });
    }
};

