<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lanes')) {
            Schema::create('lanes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('origin_zone_id')->constrained('zones');
                $table->foreignId('dest_zone_id')->constrained('zones');
                $table->enum('mode', ['air', 'road']);
                $table->unsignedInteger('std_transit_days')->default(0);
                $table->unsignedInteger('dim_divisor')->default(5000);
                $table->boolean('eawb_required')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lanes');
    }
};
