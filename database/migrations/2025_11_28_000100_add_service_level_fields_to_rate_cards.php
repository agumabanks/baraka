<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rate_cards')) {
            return;
        }

        Schema::table('rate_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('rate_cards', 'service_level')) {
                $table->string('service_level')->default('standard')->after('dest_country')->index();
            }
            if (!Schema::hasColumn('rate_cards', 'currency')) {
                $table->string('currency', 3)->default('USD')->after('service_level');
            }
            if (!Schema::hasColumn('rate_cards', 'minimum_charge')) {
                $table->decimal('minimum_charge', 10, 2)->default(0)->after('currency');
            }
            if (!Schema::hasColumn('rate_cards', 'weight_breaks')) {
                $table->json('weight_breaks')->nullable()->after('weight_rules');
            }
            if (!Schema::hasColumn('rate_cards', 'origin_zones')) {
                $table->json('origin_zones')->nullable()->after('origin_country');
            }
            if (!Schema::hasColumn('rate_cards', 'dest_zones')) {
                $table->json('dest_zones')->nullable()->after('dest_country');
            }
            if (!Schema::hasColumn('rate_cards', 'security_surcharge')) {
                $table->decimal('security_surcharge', 10, 2)->default(0)->after('fuel_surcharge_percent');
            }
            if (!Schema::hasColumn('rate_cards', 'remote_area_surcharge')) {
                $table->decimal('remote_area_surcharge', 10, 2)->default(0)->after('security_surcharge');
            }
            if (!Schema::hasColumn('rate_cards', 'insurance_rate_percent')) {
                $table->decimal('insurance_rate_percent', 5, 2)->default(0)->after('remote_area_surcharge');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rate_cards')) {
            return;
        }

        Schema::table('rate_cards', function (Blueprint $table) {
            foreach ([
                'service_level',
                'currency',
                'minimum_charge',
                'weight_breaks',
                'origin_zones',
                'dest_zones',
                'security_surcharge',
                'remote_area_surcharge',
                'insurance_rate_percent',
            ] as $column) {
                if (Schema::hasColumn('rate_cards', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
