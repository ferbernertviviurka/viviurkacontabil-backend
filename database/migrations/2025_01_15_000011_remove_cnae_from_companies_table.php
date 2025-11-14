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
        // Migrate existing CNAE data to pivot table before dropping
        $companies = \App\Models\Company::whereNotNull('cnae')->get();
        foreach ($companies as $company) {
            \DB::table('company_cnae')->insert([
                'company_id' => $company->id,
                'cnae_code' => $company->cnae,
                'cnae_description' => null,
                'principal' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('cnae');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('cnae')->nullable()->after('regime_tributario');
        });
        
        // Migrate back from pivot table
        $companyCnaes = \DB::table('company_cnae')->where('principal', true)->get();
        foreach ($companyCnaes as $companyCnae) {
            \DB::table('companies')
                ->where('id', $companyCnae->company_id)
                ->update(['cnae' => $companyCnae->cnae_code]);
        }
    }
};

