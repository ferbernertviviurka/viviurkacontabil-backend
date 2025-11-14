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
        if (!Schema::hasColumn('ai_requests', 'uuid')) {
            Schema::table('ai_requests', function (Blueprint $table) {
                $table->string('uuid')->nullable()->after('id');
            });
        }

        if (!Schema::hasColumn('ai_requests', 'conversation_uuid')) {
            Schema::table('ai_requests', function (Blueprint $table) {
                $table->string('conversation_uuid')->nullable()->after('uuid');
            });
        }

        if (!Schema::hasColumn('ai_requests', 'provider')) {
            Schema::table('ai_requests', function (Blueprint $table) {
                $table->string('provider')->default('clarifai')->after('model');
            });
        }

        if (!Schema::hasColumn('ai_requests', 'context')) {
            Schema::table('ai_requests', function (Blueprint $table) {
                $table->text('context')->nullable();
            });
        }

        // Generate UUIDs for existing records
        \App\Models\AiRequest::whereNull('uuid')->chunk(100, function ($requests) {
            foreach ($requests as $request) {
                $request->update(['uuid' => (string) \Illuminate\Support\Str::uuid()]);
            }
        });

        // Make UUID unique if not already
        if (!Schema::hasColumn('ai_requests', 'uuid') || !\Illuminate\Support\Facades\DB::select("SELECT name FROM sqlite_master WHERE type='index' AND name='ai_requests_uuid_unique'")) {
            Schema::table('ai_requests', function (Blueprint $table) {
                $table->string('uuid')->nullable(false)->change();
                $table->unique('uuid');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_requests', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'conversation_uuid', 'provider', 'context']);
        });
    }
};

