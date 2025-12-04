<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rate_cards')) {
            return;
        }

        Schema::table('rate_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('rate_cards', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_active')->index();
            }
            if (!Schema::hasColumn('rate_cards', 'transit_days')) {
                $table->integer('transit_days')->default(5)->after('is_default');
            }
            if (!Schema::hasColumn('rate_cards', 'express_surcharge')) {
                $table->decimal('express_surcharge', 10, 2)->default(15.00)->after('transit_days');
            }
            if (!Schema::hasColumn('rate_cards', 'priority_surcharge')) {
                $table->decimal('priority_surcharge', 10, 2)->default(30.00)->after('express_surcharge');
            }
            if (!Schema::hasColumn('rate_cards', 'urgent_surcharge')) {
                $table->decimal('urgent_surcharge', 10, 2)->default(50.00)->after('priority_surcharge');
            }
            if (!Schema::hasColumn('rate_cards', 'cod_fee_percent')) {
                $table->decimal('cod_fee_percent', 5, 2)->default(2.00)->after('urgent_surcharge');
            }
            if (!Schema::hasColumn('rate_cards', 'cod_min_fee')) {
                $table->decimal('cod_min_fee', 10, 2)->default(5.00)->after('cod_fee_percent');
            }
            if (!Schema::hasColumn('rate_cards', 'peak_season_surcharge')) {
                $table->decimal('peak_season_surcharge', 10, 2)->default(0)->after('cod_min_fee');
            }
            if (!Schema::hasColumn('rate_cards', 'oversize_surcharge')) {
                $table->decimal('oversize_surcharge', 10, 2)->default(50.00)->after('peak_season_surcharge');
            }
        });

        // Set default rate cards for each service level
        $serviceLevels = ['economy', 'standard', 'express', 'priority'];
        foreach ($serviceLevels as $level) {
            DB::table('rate_cards')
                ->where('service_level', $level)
                ->where('origin_country', 'XX')
                ->where('dest_country', 'XX')
                ->update(['is_default' => true]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('rate_cards')) {
            return;
        }

        Schema::table('rate_cards', function (Blueprint $table) {
            foreach ([
                'is_default',
                'transit_days',
                'express_surcharge',
                'priority_surcharge',
                'urgent_surcharge',
                'cod_fee_percent',
                'cod_min_fee',
                'peak_season_surcharge',
                'oversize_surcharge',
            ] as $column) {
                if (Schema::hasColumn('rate_cards', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
