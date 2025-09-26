<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('carrier_services')) {
            Schema::create('carrier_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('carrier_id')->constrained('carriers');
                $table->string('code');
                $table->string('name');
                $table->boolean('requires_eawb')->default(false);
                $table->timestamps();
                $table->unique(['carrier_id','code']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_services');
    }
};

