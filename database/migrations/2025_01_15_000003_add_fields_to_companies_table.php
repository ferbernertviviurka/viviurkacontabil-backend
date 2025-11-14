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
        Schema::table('companies', function (Blueprint $table) {
            // UUID - nullable first, then we'll populate and make it unique
            $table->string('uuid')->nullable()->after('id');
            
            // Responsável Financeiro
            $table->string('responsavel_financeiro_nome')->nullable()->after('whatsapp');
            $table->string('responsavel_financeiro_telefone')->nullable();
            $table->string('responsavel_financeiro_email')->nullable();
            $table->string('responsavel_financeiro_whatsapp')->nullable();
            
            // Status
            $table->boolean('ativo')->default(true)->after('user_id');
        });

        // Generate UUIDs for existing companies
        \App\Models\Company::whereNull('uuid')->chunk(100, function ($companies) {
            foreach ($companies as $company) {
                $company->update(['uuid' => (string) \Illuminate\Support\Str::uuid()]);
            }
        });

        // Now make UUID unique and not null
        Schema::table('companies', function (Blueprint $table) {
            $table->string('uuid')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'uuid',
                'responsavel_financeiro_nome',
                'responsavel_financeiro_telefone',
                'responsavel_financeiro_email',
                'responsavel_financeiro_whatsapp',
                'ativo',
            ]);
        });
    }
};

