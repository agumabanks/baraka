<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('template_id')->nullable()->after('rate_card_id')->constrained('contract_templates')->onDelete('set null');
            $table->enum('contract_type', ['customer', 'carrier', '3pl'])->default('customer')->after('template_id');
            $table->integer('volume_commitment')->nullable()->after('contract_type');
            $table->enum('volume_commitment_period', ['monthly', 'quarterly', 'annually'])->nullable()->after('volume_commitment');
            $table->integer('current_volume')->default(0)->after('volume_commitment_period');
            $table->json('discount_tiers')->nullable()->after('current_volume');
            $table->json('service_level_commitments')->nullable()->after('discount_tiers');
            $table->json('auto_renewal_terms')->nullable()->after('service_level_commitments');
            $table->json('compliance_requirements')->nullable()->after('auto_renewal_terms');
            $table->json('notification_settings')->nullable()->after('compliance_requirements');
            
            $table->index(['contract_type', 'status']);
            $table->index(['volume_commitment', 'current_volume']);
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumns([
                'template_id', 'contract_type', 'volume_commitment', 
                'volume_commitment_period', 'current_volume', 'discount_tiers',
                'service_level_commitments', 'auto_renewal_terms', 
                'compliance_requirements', 'notification_settings'
            ]);
        });
    }
};