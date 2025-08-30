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
        Schema::create('asset_assigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('delivery_man')->onUpdate('cascade')->onDelete('cascade');
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_assigns');
    }
};
