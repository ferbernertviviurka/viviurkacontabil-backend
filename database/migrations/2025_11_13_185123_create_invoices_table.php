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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('numero')->nullable();
            $table->string('serie')->nullable();
            $table->date('data_emissao');
            $table->decimal('valor', 10, 2);
            $table->string('status')->default('processando'); // processando, emitida, cancelada, erro
            $table->text('destinatario_dados')->nullable(); // JSON com dados do destinatario
            $table->text('itens')->nullable(); // JSON com itens da nota
            $table->text('observacoes')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('provider_id')->nullable(); // ID da NF no provedor externo
            $table->text('provider_response')->nullable(); // Response do provider
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
