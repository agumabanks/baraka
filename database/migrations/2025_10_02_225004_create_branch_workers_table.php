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
        Schema::create('branch_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('unified_branches')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role')->default('worker'); // worker, supervisor, dispatcher, etc.
            $table->json('permissions')->nullable(); // Specific permissions for this assignment
            $table->json('work_schedule')->nullable(); // Working hours, shifts
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->date('assigned_at');
            $table->date('unassigned_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional flexible data
            $table->enum('status', [Status::ACTIVE, Status::INACTIVE])->default(Status::ACTIVE);
            $table->timestamps();

            // Unique constraint to prevent duplicate assignments
            $table->unique(['branch_id', 'user_id', 'assigned_at'], 'unique_branch_user_assignment');

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['branch_id', 'role']);
            $table->index(['assigned_at', 'unassigned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_workers');
    }
};
