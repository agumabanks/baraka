<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->string('trigger', 50)->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $performedBy = $table->foreignId('performed_by')->nullable();
            if (Schema::hasTable('users')) {
                $performedBy->constrained('users')->nullOnDelete();
            }
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'created_at']);
            $table->index('to_status');
            $table->index('trigger');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_transitions');
    }
};
