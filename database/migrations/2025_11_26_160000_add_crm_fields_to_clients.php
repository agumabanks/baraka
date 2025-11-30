<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clients')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'credit_limit')) {
                $table->decimal('credit_limit', 12, 2)->nullable()->after('status');
            }
            if (! Schema::hasColumn('clients', 'risk_score')) {
                $table->unsignedTinyInteger('risk_score')->nullable()->after('credit_limit');
            }
            if (! Schema::hasColumn('clients', 'kyc_status')) {
                $table->string('kyc_status')->default('pending')->after('risk_score');
            }
            if (! Schema::hasColumn('clients', 'contacts')) {
                $table->json('contacts')->nullable()->after('kyc_data');
            }
            if (! Schema::hasColumn('clients', 'addresses')) {
                $table->json('addresses')->nullable()->after('contacts');
            }
            if (! Schema::hasColumn('clients', 'pipeline_stage')) {
                $table->string('pipeline_stage')->default('onboarding')->after('addresses');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('clients')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            foreach (['credit_limit', 'risk_score', 'kyc_status', 'contacts', 'addresses', 'pipeline_stage'] as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
