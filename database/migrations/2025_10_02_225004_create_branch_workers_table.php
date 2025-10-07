<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role');
            $table->json('permissions')->nullable();
            $table->json('work_schedule')->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('unassigned_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();

            $table->unique(['branch_id', 'user_id']);
            $table->index(['branch_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_workers');
    }
};
