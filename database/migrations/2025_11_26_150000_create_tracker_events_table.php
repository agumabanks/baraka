<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tracker_events')) {
            return;
        }

        Schema::create('tracker_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->string('tracker_id')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('temperature_c', 6, 2)->nullable();
            $table->unsignedTinyInteger('battery_percent')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index(['shipment_id', 'recorded_at']);
            $table->index('tracker_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_events');
    }
};
