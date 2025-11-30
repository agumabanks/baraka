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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'credit_limit')) {
                $table->decimal('credit_limit', 10, 2)->default(0)->after('country');
            }
            if (!Schema::hasColumn('customers', 'payment_terms')) {
                $table->string('payment_terms', 50)->default('net_30')->after('credit_limit');
            }
            if (!Schema::hasColumn('customers', 'risk_score')) {
                $table->integer('risk_score')->default(0)->after('payment_terms');
            }
            if (!Schema::hasColumn('customers', 'kyc_status')) {
                $table->enum('kyc_status', ['pending', 'approved', 'rejected'])->default('pending')->after('risk_score');
            }
            if (!Schema::hasColumn('customers', 'notes')) {
                $table->text('notes')->nullable()->after('kyc_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['credit_limit', 'payment_terms', 'risk_score', 'kyc_status', 'notes']);
        });
    }
};
