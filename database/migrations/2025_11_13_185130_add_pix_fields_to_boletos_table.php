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
        Schema::table('boletos', function (Blueprint $table) {
            $table->string('tipo_pagamento')->default('boleto')->after('company_id'); // boleto, pix, credit_card
            $table->string('chave_pix')->nullable()->after('url_pdf');
            $table->text('qr_code_pix')->nullable(); // Base64 QR Code
            $table->string('link_pagamento')->nullable(); // Link para pagamento via cartão
            $table->text('dados_pagamento')->nullable(); // JSON com dados específicos
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boletos', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_pagamento',
                'chave_pix',
                'qr_code_pix',
                'link_pagamento',
                'dados_pagamento',
            ]);
        });
    }
};

