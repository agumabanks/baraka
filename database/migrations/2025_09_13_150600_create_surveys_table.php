<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('surveys')) {
            Schema::create('surveys', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shipment_id')->nullable()->constrained('shipments');
                $table->unsignedTinyInteger('score')->comment('0..10');
                $table->text('comment')->nullable();
                $table->string('channel')->default('link');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};

