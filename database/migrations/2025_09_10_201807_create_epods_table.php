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
        Schema::create('epods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stop_id')->constrained('stops')->onDelete('cascade');
            $table->string('signer_name')->nullable();
            $table->string('signature_image_path')->nullable();
            $table->json('photo_paths')->nullable(); // array of image paths
            $table->json('gps_point')->nullable(); // {"lat": float, "lng": float}
            $table->timestamp('completed_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('stop_id');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('epods');
    }
};
