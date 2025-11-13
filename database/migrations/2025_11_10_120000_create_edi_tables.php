<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('edi_mappings')) {
            Schema::create('edi_mappings', function (Blueprint $table) {
                $table->id();
                $table->string('document_type', 10); // 850, 856, 997
                $table->string('version')->nullable();
                $table->json('field_map');
                $table->text('description')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->unique(['document_type', 'version']);
            });
        }

        if (!Schema::hasTable('edi_transactions')) {
            Schema::create('edi_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('provider_id')->nullable()->constrained('edi_providers')->nullOnDelete();
                $table->string('document_type', 10);
                $table->string('direction', 20)->default('inbound');
                $table->string('document_number')->nullable();
                $table->string('status', 40)->default('received');
                $table->string('external_reference')->nullable();
                $table->uuid('correlation_id')->nullable();
                $table->json('payload');
                $table->json('normalized_payload')->nullable();
                $table->json('ack_payload')->nullable();
                $table->timestamp('acknowledged_at')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['document_type', 'status']);
                $table->index(['document_number']);
                $table->index(['status', 'created_at']);
            });
        }

        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained();
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('tracking_number')->index();
            $table->string('action'); // inbound, outbound, delivery, exception
            $table->timestamp('timestamp');
            $table->text('notes')->nullable();
            $table->string('offline_sync_key')->nullable()->unique();
            $table->string('device_id')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->string('barcode_type', 20)->nullable();
            $table->string('batch_id', 100)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'timestamp']);
            $table->index(['device_id', 'synced_at']);
            $table->index(['batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scans');
        Schema::dropIfExists('edi_transactions');
        Schema::dropIfExists('edi_mappings');
    }
};
