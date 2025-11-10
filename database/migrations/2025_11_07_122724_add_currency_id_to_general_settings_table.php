<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable()->after('currency');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
            $table->index('currency_id');
        });

        // Populate currency_id based on existing currency symbols
        DB::statement("
            UPDATE general_settings gs
            INNER JOIN currencies c ON gs.currency = c.symbol
            SET gs.currency_id = c.id
            WHERE gs.currency IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropIndex(['currency_id']);
            $table->dropColumn('currency_id');
        });
    }
};
