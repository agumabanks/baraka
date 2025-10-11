<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['HUB', 'REGIONAL', 'LOCAL'])->default('LOCAL');
            $table->boolean('is_hub')->default(false);
            $table->unsignedBigInteger('parent_branch_id')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('operating_hours')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('parent_branch_id');
            $table->index(['latitude', 'longitude']);
        });

        // Add foreign key constraint after table creation
        Schema::table('branches', function (Blueprint $table) {
            $table->foreign('parent_branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
