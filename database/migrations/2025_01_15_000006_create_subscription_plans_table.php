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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Básico, Profissional, Empresarial
            $table->string('slug')->unique(); // basico, profissional, empresarial
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2);
            $table->decimal('price_quarterly', 10, 2)->nullable();
            $table->decimal('price_semiannual', 10, 2)->nullable();
            $table->decimal('price_annual', 10, 2)->nullable();
            $table->json('features')->nullable(); // Array de features incluídas
            $table->json('limits')->nullable(); // Limites do plano (ex: max_invoices, max_companies)
            $table->integer('trial_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};

