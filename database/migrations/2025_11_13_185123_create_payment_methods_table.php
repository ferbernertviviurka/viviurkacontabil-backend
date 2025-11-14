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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('tipo'); // boleto, cartao
            $table->string('provider')->default('asaas'); // asaas, pagarme, etc
            $table->boolean('ativo')->default(true);
            $table->text('dados_provider')->nullable(); // JSON - dados específicos do provider
            $table->string('provider_payment_id')->nullable(); // ID do método de pagamento no provider
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
