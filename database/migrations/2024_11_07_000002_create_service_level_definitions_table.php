<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_level_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('base_multiplier', 4, 2)->default(1.0);
            $table->integer('min_delivery_hours')->nullable();
            $table->integer('max_delivery_hours')->nullable();
            $table->decimal('reliability_score', 4, 2)->default(95.0);
            $table->boolean('sla_claims_covered')->default(true);
            $table->timestamps();
        });

        // Insert default service levels
        DB::table('service_level_definitions')->insert([
            [
                'code' => 'STANDARD',
                'name' => 'Standard Service',
                'base_multiplier' => 1.00,
                'min_delivery_hours' => 24,
                'max_delivery_hours' => 72,
                'reliability_score' => 95.0,
                'sla_claims_covered' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'PRIORITY',
                'name' => 'Priority Service',
                'base_multiplier' => 1.50,
                'min_delivery_hours' => 12,
                'max_delivery_hours' => 24,
                'reliability_score' => 98.0,
                'sla_claims_covered' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'EXPRESS',
                'name' => 'Express Service',
                'base_multiplier' => 2.00,
                'min_delivery_hours' => 2,
                'max_delivery_hours' => 12,
                'reliability_score' => 99.5,
                'sla_claims_covered' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('service_level_definitions');
    }
};