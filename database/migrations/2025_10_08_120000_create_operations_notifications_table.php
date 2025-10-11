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
        Schema::create('operations_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_uuid')->unique();
            
            // Notification metadata
            $table->string('type'); // exception.created, alert.capacity_warning, etc.
            $table->string('category')->default('operational'); // operational, system, alert
            $table->string('title');
            $table->text('message');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('priority', ['1', '2', '3', '4', '5'])->default('3');
            
            // Notification content
            $table->json('data')->nullable(); // Additional notification data
            $table->json('action_data')->nullable(); // Action buttons/links
            
            // Status tracking
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->boolean('requires_action')->default(false);
            $table->boolean('is_dismissed')->default(false);
            
            // Delivery tracking
            $table->json('channels')->nullable(); // ['websocket', 'push', 'email']
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            
            // Recipient information
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->string('recipient_role')->nullable(); // For broadcast by role
            
            // Related entities
            $table->foreignId('shipment_id')->nullable()->constrained('shipments')->onDelete('set null');
            $table->foreignId('worker_id')->nullable()->constrained('branch_workers')->onDelete('set null');
            $table->foreignId('asset_id')->nullable()->constrained('assets')->onDelete('set null');
            $table->string('related_entity_type')->nullable();
            $table->unsignedBigInteger('related_entity_id')->nullable();
            
            // Tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['user_id', 'status', 'created_at'], 'ops_notif_user_status_idx');
            $table->index(['branch_id', 'status'], 'ops_notif_branch_status_idx');
            $table->index(['type', 'created_at'], 'ops_notif_type_date_idx');
            $table->index(['severity', 'priority'], 'ops_notif_severity_priority_idx');
            $table->index('notification_uuid', 'ops_notif_uuid_idx');
            $table->index(['read_at', 'user_id'], 'ops_notif_read_user_idx');
            $table->index(['related_entity_type', 'related_entity_id'], 'ops_notif_entity_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operations_notifications');
    }
};
