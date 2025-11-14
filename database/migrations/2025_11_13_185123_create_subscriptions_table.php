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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->nullable()->constrained()->onDelete('set null');
            $table->string('plano'); // mensal, trimestral, anual
            $table->decimal('valor', 10, 2);
            $table->integer('dia_vencimento')->default(10); // Dia do mês para vencimento
            $table->string('status')->default('active'); // active, cancelled, suspended
            $table->date('proxima_cobranca')->nullable();
            $table->date('data_cancelamento')->nullable();
            $table->string('provider_subscription_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
