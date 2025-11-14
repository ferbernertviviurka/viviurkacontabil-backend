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
        Schema::create('boletos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->decimal('valor', 10, 2);
            $table->date('vencimento');
            $table->string('status')->default('pending'); // pending, paid, overdue, cancelled
            $table->string('descricao')->nullable();
            $table->string('linha_digitavel')->nullable();
            $table->string('codigo_barras')->nullable();
            $table->string('url_pdf')->nullable();
            $table->string('provider_id')->nullable(); // ID do boleto no provider
            $table->text('provider_response')->nullable();
            $table->date('data_pagamento')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boletos');
    }
};
