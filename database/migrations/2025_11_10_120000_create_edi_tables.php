<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edi_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('edi_type'); // 850, 856, 997
            $table->string('sender_code');
            $table->json('transformations');
            $table->timestamps();

            $table->unique(['edi_type', 'sender_code']);
            $table->index('edi_type');
        });

        Schema::create('edi_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('edi_type');
            $table->string('sender_code');
            $table->string('receiver_code');
            $table->string('reference')->unique();
            $table->longText('raw_document');
            $table->json('processed_data')->nullable();
            $table->enum('status', ['received', 'processing', 'processed', 'failed'])->default('received');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('sender_code');
            $table->index('edi_type');
        });

        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained();
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('action'); // inbound, outbound, delivery, exception
            $table->timestamp('timestamp');
            $table->text('notes')->nullable();
            $table->string('offline_sync_key')->nullable()->unique();
            $table->string('device_id')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'timestamp']);
            $table->index(['device_id', 'synced_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scans');
        Schema::dropIfExists('edi_transactions');
        Schema::dropIfExists('edi_mappings');
    }
};
