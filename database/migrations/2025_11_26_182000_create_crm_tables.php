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
        // Client Addresses
        Schema::create('client_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->enum('type', ['billing', 'pickup', 'delivery', 'other'])->default('delivery');
            $table->string('label')->nullable(); // e.g., "Main Office", "Warehouse"
            $table->text('address_line_1');
            $table->text('address_line_2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Saudi Arabia');
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // coordinates, delivery instructions, etc.
            $table->timestamps();
            
            $table->index(['customer_id', 'type']);
        });

        // CRM Activities
        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // who performed the activity
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->enum('activity_type', ['call', 'email', 'visit', 'meeting', 'note', 'task'])->default('note');
            $table->string('subject');
            $table->text('description')->nullable();
            $table->timestamp('occurred_at');
            $table->integer('duration_minutes')->nullable(); // for calls, meetings
            $table->enum('outcome', ['positive', 'neutral', 'negative', 'follow_up_required'])->nullable();
            $table->json('metadata')->nullable(); // call recording link, email thread, etc.
            $table->timestamps();
            
            $table->index(['customer_id', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
            $table->index('activity_type');
        });

        // CRM Reminders
        Schema::create('crm_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // assigned to
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('reminder_at');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'reminder_at', 'status']);
            $table->index(['customer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_reminders');
        Schema::dropIfExists('crm_activities');
        Schema::dropIfExists('client_addresses');
    }
};
