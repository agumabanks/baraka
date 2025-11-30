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
        Schema::table('general_settings', function (Blueprint $table) {
            // Add tracking_id column as string (nullable) with index for performance
            $table->string('tracking_id')->nullable()->index()->after('phone');
            
            // Add details column as JSON (nullable) for storing structured settings
            $table->json('details')->nullable()->after('tracking_id');
        });

        // Backfill existing records with default values
        DB::statement("UPDATE general_settings SET tracking_id = NULL WHERE tracking_id IS NULL");
        DB::statement("UPDATE general_settings SET details = JSON_QUOTE('{}') WHERE details IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropIndex(['tracking_id']);
            $table->dropColumn(['tracking_id', 'details']);
        });
    }
};