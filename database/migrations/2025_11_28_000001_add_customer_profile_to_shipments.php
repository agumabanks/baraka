<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shipments')) {
            return;
        }

        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'customer_profile_id')) {
                $column = $table->foreignId('customer_profile_id')->nullable()->after('customer_id');
                if (Schema::hasTable('customers')) {
                    $column->constrained('customers')->nullOnDelete();
                }
                $table->index('customer_profile_id', 'shipments_customer_profile_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('shipments')) {
            return;
        }

        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'customer_profile_id')) {
                $table->dropForeign(['customer_profile_id']);
                $table->dropIndex('shipments_customer_profile_idx');
                $table->dropColumn('customer_profile_id');
            }
        });
    }
};
