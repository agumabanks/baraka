<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('edi_providers')) {
            Schema::create('edi_providers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->enum('type', ['airline', 'broker', 'mock']);
                $table->json('config')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('edi_providers');
    }
};
