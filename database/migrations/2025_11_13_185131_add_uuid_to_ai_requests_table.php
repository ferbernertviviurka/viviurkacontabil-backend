<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('ai_requests', 'uuid')) {
            Schema::table('ai_requests', function (Blueprint $table) {
                // Use string for UUID in PostgreSQL (more compatible)
                $table->string('uuid', 36)->nullable()->after('id');
            });
        }

        if (!Schema::hasColumn('ai_requests', 'conversation_uuid')) {
            Schema::table('ai_requests', function (Blueprint $table) {
                // Use string for UUID in PostgreSQL (more compatible)
                $table->string('conversation_uuid', 36)->nullable()->after('uuid');
            });
        }

        if (!Schema::hasColumn('ai_requests', 'provider')) {
            Schema::table('ai_requests', function (Blueprint $table) {
                $table->string('provider')->default('clarifai')->after('model');
            });
        }

        if (!Schema::hasColumn('ai_requests', 'context')) {
            Schema::table('ai_requests', function (Blueprint $table) {
                // Use jsonb for PostgreSQL (better performance) or json
                $table->json('context')->nullable();
            });
        }

        // Generate UUIDs for existing records
        \App\Models\AiRequest::whereNull('uuid')->chunk(100, function ($requests) {
            foreach ($requests as $request) {
                $request->update(['uuid' => (string) \Illuminate\Support\Str::uuid()]);
            }
        });

        // Make UUID unique if not already (PostgreSQL compatible)
        try {
            if (Schema::hasColumn('ai_requests', 'uuid')) {
                // Check if unique index already exists (PostgreSQL)
                $indexExists = DB::select("
                    SELECT COUNT(*) as count
                    FROM pg_indexes 
                    WHERE tablename = 'ai_requests' 
                    AND indexname = 'ai_requests_uuid_unique'
                ");
                
                if (empty($indexExists) || $indexExists[0]->count == 0) {
                    Schema::table('ai_requests', function (Blueprint $table) {
                        $table->string('uuid', 36)->nullable(false)->change();
                        $table->unique('uuid', 'ai_requests_uuid_unique');
                    });
                } else {
                    // Index exists, just make sure uuid is not null
                    Schema::table('ai_requests', function (Blueprint $table) {
                        $table->string('uuid', 36)->nullable(false)->change();
                    });
                }
            }
        } catch (\Exception $e) {
            // Index might already exist or column doesn't exist, ignore
            echo "Warning: Could not create unique index on uuid: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop unique constraint if exists (not just the index)
        DB::statement('ALTER TABLE ai_requests DROP CONSTRAINT IF EXISTS ai_requests_uuid_unique');

        // Drop columns
        Schema::table('ai_requests', function (Blueprint $table) {
            $table->dropColumn([
                'uuid',
                'conversation_uuid',
                'provider',
                'context',
            ]);
        });
    }
};
