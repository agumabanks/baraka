<?php

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
        Schema::create('workflow_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', [
                'pending',
                'in_progress',
                'testing',
                'awaiting_feedback',
                'completed',
                'delayed',
            ])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('project_name')->nullable();
            $table->string('stage')->nullable();
            $table->string('status_label')->nullable();
            $table->string('client')->nullable();
            $table->string('tracking_number')->nullable()->index();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_status_at')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->json('time_tracking')->nullable();
            $table->json('dependencies')->nullable();
            $table->json('attachments')->nullable();
            $table->json('watchers')->nullable();
            $table->json('allowed_transitions')->nullable();
            $table->json('restricted_roles')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['assigned_to', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_tasks');
    }
};
