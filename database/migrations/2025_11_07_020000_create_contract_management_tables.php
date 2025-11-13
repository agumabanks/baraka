<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contract Service Level Commitments Table
        Schema::create('contract_service_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->string('service_level_code', 50);
            $table->integer('delivery_window_min_hours')->nullable();
            $table->integer('delivery_window_max_hours')->nullable();
            $table->decimal('reliability_threshold', 5, 2)->default(90.0);
            $table->decimal('sla_claim_ratio', 5, 2)->default(0.05);
            $table->integer('response_time_hours')->default(24);
            $table->json('penalty_conditions')->nullable();
            $table->json('compensation_rules')->nullable();
            $table->timestamps();
            
            $table->index(['contract_id', 'service_level_code']);
        });

        // Contract Volume Discounts Table
        Schema::create('contract_volume_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->string('tier_name', 50);
            $table->integer('volume_requirement');
            $table->decimal('discount_percentage', 5, 2);
            $table->json('benefits')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['contract_id', 'volume_requirement']);
            $table->unique(['contract_id', 'tier_name']);
        });

        // Contract Notifications Table
        Schema::create('contract_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('notification_type', 50);
            $table->string('title', 255);
            $table->text('message');
            $table->json('data')->nullable();
            $table->string('channel', 20)->default('email'); // email, sms, webhook
            $table->string('recipient')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed'])->default('pending');
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['contract_id', 'notification_type']);
            $table->index(['customer_id', 'notification_type']);
            $table->index(['status', 'scheduled_at']);
        });

        // Contract Audit Log Table
        Schema::create('contract_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->string('action', 50);
            $table->string('field_name', 100)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->json('additional_data')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['contract_id', 'action']);
            $table->index(['user_id', 'action']);
        });

        // Contract Amendments Table
        Schema::create('contract_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->integer('amendment_number');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->json('changes')->nullable(); // Store field changes
            $table->text('justification')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->timestamp('effective_date')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['contract_id', 'amendment_number']);
            $table->index(['contract_id', 'status']);
        });

        // Contract Compliance Table
        Schema::create('contract_compliances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->string('requirement_name', 255);
            $table->string('compliance_type', 50);
            $table->decimal('target_value', 10, 2);
            $table->decimal('actual_value', 10, 2)->default(0);
            $table->decimal('performance_percentage', 5, 2)->default(100.0);
            $table->enum('compliance_status', ['met', 'warning', 'breached'])->default('met');
            $table->boolean('is_critical')->default(false);
            $table->integer('consecutive_breaches')->default(0);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_breach_at')->nullable();
            $table->timestamp('next_check_due')->nullable();
            $table->timestamp('resolution_deadline')->nullable();
            $table->json('required_actions')->nullable();
            $table->integer('escalation_level')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['contract_id', 'compliance_type']);
            $table->index(['compliance_status', 'next_check_due']);
        });

        // Additional indexes for performance
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'original_contract_id')) {
                $table->foreignId('original_contract_id')
                    ->nullable()
                    ->constrained('contracts')
                    ->nullOnDelete();
            }

            if (Schema::hasColumn('contracts', 'status') && Schema::hasColumn('contracts', 'end_date')) {
                $table->index(['status', 'end_date']);
            }

            if (Schema::hasColumn('contracts', 'customer_id') && Schema::hasColumn('contracts', 'status')) {
                $table->index(['customer_id', 'status']);
            }

            if (Schema::hasColumn('contracts', 'current_volume') && Schema::hasColumn('contracts', 'volume_commitment')) {
                $table->index(['current_volume', 'volume_commitment']);
            }
        });

        // Customer milestones table enhancement (if not exists)
        if (!Schema::hasTable('customer_milestones')) {
            Schema::create('customer_milestones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained()->onDelete('cascade');
                $table->string('milestone_type', 50);
                $table->integer('milestone_value');
                $table->timestamp('achieved_at');
                $table->string('reward_given')->nullable();
                $table->json('reward_details')->nullable();
                $table->timestamps();
                
                $table->index(['customer_id', 'milestone_type']);
                $table->index(['milestone_type', 'milestone_value']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_compliances');
        Schema::dropIfExists('contract_amendments');
        Schema::dropIfExists('contract_audit_logs');
        Schema::dropIfExists('contract_notifications');
        Schema::dropIfExists('contract_volume_discounts');
        Schema::dropIfExists('contract_service_levels');
        Schema::dropIfExists('customer_milestones');
        
        // Remove indexes from contracts table
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'status') && Schema::hasColumn('contracts', 'end_date')) {
                $table->dropIndex('contracts_status_end_date_index');
            }

            if (Schema::hasColumn('contracts', 'customer_id') && Schema::hasColumn('contracts', 'status')) {
                $table->dropIndex('contracts_customer_id_status_index');
            }

            if (Schema::hasColumn('contracts', 'current_volume') && Schema::hasColumn('contracts', 'volume_commitment')) {
                $table->dropIndex('contracts_current_volume_volume_commitment_index');
            }

            if (Schema::hasColumn('contracts', 'original_contract_id')) {
                $table->dropForeign(['original_contract_id']);
                $table->dropColumn('original_contract_id');
            }
        });
    }
};
