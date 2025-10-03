<?php

use App\Enums\Status;
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
        Schema::create('unified_branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['HUB', 'REGIONAL', 'LOCAL'])->default('LOCAL');
            $table->boolean('is_hub')->default(false);
            $table->foreignId('parent_branch_id')->nullable()->constrained('unified_branches')->onDelete('set null');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('operating_hours')->nullable(); // Store hours as JSON
            $table->json('capabilities')->nullable(); // Services offered by this branch
            $table->json('metadata')->nullable(); // Additional flexible data
            $table->enum('status', [Status::ACTIVE, Status::INACTIVE])->default(Status::ACTIVE);
            $table->timestamps();

            // Indexes for performance
            $table->index(['type', 'status']);
            $table->index(['parent_branch_id']);
            $table->index(['latitude', 'longitude']);
            $table->index('is_hub');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unified_branches');
    }
};
