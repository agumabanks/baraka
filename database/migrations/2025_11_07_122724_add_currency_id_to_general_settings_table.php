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
            if (!Schema::hasColumn('general_settings', 'currency_id')) {
                $table->unsignedBigInteger('currency_id')->nullable()->after('currency');
                $table->index('currency_id');
            }
        });

        if (Schema::hasColumn('general_settings', 'currency_id')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
            });
        }

        // Populate currency_id based on existing currency symbols in a driver-friendly way
        if (Schema::hasTable('currencies') && Schema::hasTable('general_settings')) {
            $currencyMap = DB::table('currencies')->pluck('id', 'symbol');

            DB::table('general_settings')
                ->whereNotNull('currency')
                ->orderBy('id')
                ->chunkById(100, function ($settings) use ($currencyMap) {
                    foreach ($settings as $setting) {
                        $currencySymbol = $setting->currency;
                        if (! $currencySymbol) {
                            continue;
                        }

                        $currencyId = $currencyMap[$currencySymbol] ?? null;
                        if ($currencyId) {
                            DB::table('general_settings')
                                ->where('id', $setting->id)
                                ->update(['currency_id' => $currencyId]);
                        }
                    }
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (Schema::hasColumn('general_settings', 'currency_id')) {
                $table->dropForeign(['currency_id']);
                $table->dropIndex(['currency_id']);
                $table->dropColumn('currency_id');
            }
        });
    }
};
