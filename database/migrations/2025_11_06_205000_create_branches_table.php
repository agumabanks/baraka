<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('branches')) {
            return;
        }

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type', 40)->default('DESTINATION_BRANCH');
            $table->boolean('is_hub')->default(false);
            $table->unsignedBigInteger('parent_branch_id')->nullable();
            $table->string('address')->nullable();
            $table->string('country', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('time_zone', 64)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('geo_lat', 10, 8)->nullable();
            $table->decimal('geo_lng', 11, 8)->nullable();
            $table->json('operating_hours')->nullable();
            $table->json('capabilities')->nullable();
            $table->unsignedInteger('capacity_parcels_per_day')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();

            $table->index('country', 'branches_country_index');
            $table->index('city', 'branches_city_index');
            $table->index('time_zone', 'branches_time_zone_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
