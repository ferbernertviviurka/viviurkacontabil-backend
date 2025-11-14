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
            $table->string('recorrencia')->default('mensal')->after('plano'); // mensal, trimestral, semestral, anual
            $table->boolean('pago_mes_atual')->default(false)->after('proxima_cobranca');
            $table->json('meses_pagos')->nullable()->after('pago_mes_atual'); // Array de meses pagos por cliente
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['recorrencia', 'pago_mes_atual', 'meses_pagos']);
        });
    }
};

