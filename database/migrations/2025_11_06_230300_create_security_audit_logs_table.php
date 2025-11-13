<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('security_audit_logs')) {
            Schema::create('security_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('event_type'); // 'login', 'logout', 'permission_change', 'data_access', etc.
                $table->string('event_category')->default('security'); // 'security', 'financial', 'operational', 'privacy'
                $table->string('severity')->default('info'); // 'low', 'medium', 'high', 'critical'
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_type')->nullable(); // 'App\Models\User', etc.
                $table->string('session_id')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent')->nullable();
                $table->string('resource_type')->nullable(); // 'shipments', 'financial_data', etc.
                $table->unsignedBigInteger('resource_id')->nullable();
                $table->json('action_details')->nullable(); // Specific details of what was done
                $table->json('old_values')->nullable(); // Previous state (for updates)
                $table->json('new_values')->nullable(); // New state (for updates)
                $table->string('status')->default('success'); // 'success', 'failure', 'warning', 'blocked'
                $table->text('description')->nullable();
                $table->json('metadata')->nullable(); // Additional context
                $table->timestamps();
                
                $table->index('event_type');
                $table->index('event_category');
                $table->index('severity');
                $table->index('user_id');
                $table->index('resource_type');
                $table->index('status');
                $table->index('created_at');
                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_audit_logs');
    }
};
