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
        Schema::create('monthly_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('mes_referencia'); // YYYY-MM (ex: 2025-01)
            $table->decimal('valor', 10, 2);
            $table->date('data_vencimento');
            $table->date('data_pagamento')->nullable();
            $table->string('status')->default('pending'); // pending, paid, overdue, cancelled
            $table->string('metodo_pagamento')->nullable(); // boleto, pix, credit_card
            $table->text('dados_pagamento')->nullable(); // JSON com dados específicos do método
            $table->string('chave_pix')->nullable(); // Chave PIX aleatória
            $table->text('qr_code_pix')->nullable(); // QR Code PIX em base64
            $table->string('boleto_url')->nullable(); // URL do boleto
            $table->string('link_pagamento')->nullable(); // Link para pagamento via cartão
            $table->boolean('email_enviado')->default(false);
            $table->timestamp('email_enviado_em')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'mes_referencia']);
            $table->index(['status', 'data_vencimento']);
            $table->unique(['subscription_id', 'mes_referencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_payments');
    }
};

