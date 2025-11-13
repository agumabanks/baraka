<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('edi_transactions')) {
            return;
        }

        Schema::table('edi_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('edi_transactions', 'provider_id')) {
                $table->foreignId('provider_id')->nullable()->after('id')->constrained('edi_providers')->nullOnDelete();
            }
            if (!Schema::hasColumn('edi_transactions', 'document_type')) {
                $table->string('document_type', 10)->after('provider_id');
            }
            if (!Schema::hasColumn('edi_transactions', 'direction')) {
                $table->string('direction', 20)->default('inbound')->after('document_type');
            }
            if (!Schema::hasColumn('edi_transactions', 'document_number')) {
                $table->string('document_number')->nullable()->after('direction');
            }
            if (!Schema::hasColumn('edi_transactions', 'status')) {
                $table->string('status', 40)->default('received')->after('document_number');
            }
            if (!Schema::hasColumn('edi_transactions', 'external_reference')) {
                $table->string('external_reference')->nullable()->after('status');
            }
            if (!Schema::hasColumn('edi_transactions', 'correlation_id')) {
                $table->uuid('correlation_id')->nullable()->after('external_reference');
            }
            if (!Schema::hasColumn('edi_transactions', 'payload')) {
                $table->json('payload')->after('correlation_id');
            }
            if (!Schema::hasColumn('edi_transactions', 'normalized_payload')) {
                $table->json('normalized_payload')->nullable()->after('payload');
            }
            if (!Schema::hasColumn('edi_transactions', 'ack_payload')) {
                $table->json('ack_payload')->nullable()->after('normalized_payload');
            }
            if (!Schema::hasColumn('edi_transactions', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable()->after('ack_payload');
            }
            if (!Schema::hasColumn('edi_transactions', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('acknowledged_at');
            }
        });
    }

    public function down(): void
    {
        // No-op: columns should remain available for EDI processing.
    }
};
