<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SUPPORTED = ['en', 'fr', 'sw'];

    public function up(): void
    {
        if (! Schema::hasTable('customers') || ! Schema::hasColumn('customers', 'preferred_language')) {
            return;
        }

        DB::table('customers')
            ->whereNull('preferred_language')
            ->orWhere('preferred_language', '')
            ->update(['preferred_language' => 'en']);

        DB::table('customers')
            ->whereNotIn('preferred_language', self::SUPPORTED)
            ->update(['preferred_language' => 'en']);
    }

    public function down(): void
    {
        // No-op (data-only normalization).
    }
};

