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
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'invoice_id')) {
                $table->unsignedBigInteger('invoice_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('shipments', 'is_fragile')) {
                $table->boolean('is_fragile')->default(false)->after('invoice_id');
            }
            if (!Schema::hasColumn('shipments', 'declared_value')) {
                $table->decimal('declared_value', 10, 2)->nullable()->after('is_fragile');
            }
            if (!Schema::hasColumn('shipments', 'cod_amount')) {
                $table->decimal('cod_amount', 10, 2)->default(0)->after('declared_value');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['invoice_id', 'is_fragile', 'declared_value', 'cod_amount']);
        });
    }
};
