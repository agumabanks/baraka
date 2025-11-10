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
        if (! Schema::hasTable('shipments')) {
            return;
        }

        Schema::table('shipments', function (Blueprint $table) {
            if (! Schema::hasColumn('shipments', 'mode')) {
                $table->string('mode', 20)
                    ->default('individual')
                    ->after('service_level')
                    ->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('shipments')) {
            return;
        }

        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'mode')) {
                $table->dropColumn('mode');
            }
        });
    }
};
