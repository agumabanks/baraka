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
        Schema::table('delivery_man', function (Blueprint $table) {
            $table->string('current_location_lat')->nullable()->after('delivery_long');
            $table->string('current_location_long')->nullable()->after('current_location_lat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_man', function (Blueprint $table) {
            $table->dropColumn(['current_location_lat', 'current_location_long']);
        });
    }
};
