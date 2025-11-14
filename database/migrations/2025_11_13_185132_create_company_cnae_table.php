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
        Schema::create('company_cnae', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('cnae_code'); // Código do CNAE (ex: 6201-5/00)
            $table->string('cnae_description')->nullable(); // Descrição do CNAE
            $table->boolean('principal')->default(false); // CNAE principal da empresa
            $table->timestamps();
            
            // Evitar duplicatas
            $table->unique(['company_id', 'cnae_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_cnae');
    }
};

