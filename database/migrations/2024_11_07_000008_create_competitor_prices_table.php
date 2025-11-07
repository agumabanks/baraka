<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_prices', function (Blueprint $table) {
            $table->id();
            $table->string('carrier_name', 100);
            $table->char('origin_country', 2);
            $table->char('destination_country', 2);
            $table->string('service_level', 50);
            $table->decimal('price', 10, 2);
            $table->char('currency', 3)->default('USD');
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->enum('source_type', ['api', 'manual', 'web_scraping']);
            $table->timestamp('collected_at')->default(now());
            $table->timestamps();
            
            $table->index(['origin_country', 'destination_country', 'service_level']);
            $table->index(['collected_at']);
            $table->index(['carrier_name', 'source_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_prices');
    }
};