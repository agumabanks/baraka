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
        Schema::create('pod_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('delivery_man')->onDelete('cascade');
            $table->string('signature')->nullable(); // Path to signature image
            $table->string('photo')->nullable(); // Path to delivery photo
            $table->string('otp_code', 6)->nullable(); // OTP for verification
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['shipment_id', 'driver_id']); // One POD per driver per shipment
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pod_proofs');
    }
};