<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for accessibility compliance and audit trails.
     */
    public function up(): void
    {
        // Accessibility Compliance Logs Table
        Schema::create('accessibility_compliance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('test_id')->unique();
            $table->string('page_url');
            $table->string('test_type'); // 'automated', 'manual', 'user_testing'
            $table->json('wcag_version'); // e.g., ["2.1", "AA"]
            $table->json('test_results'); // Detailed test results
            $table->decimal('compliance_score', 5, 2); // 0-100 score
            $table->json('violations'); // List of WCAG violations
            $table->json('warnings'); // List of WCAG warnings
            $table->json('passes'); // List of WCAG passes
            $table->string('tested_by')->nullable(); // User ID or automated system
            $table->timestamp('tested_at');
            $table->json('metadata')->nullable(); // Additional test metadata
            $table->timestamps();
            
            $table->index(['page_url', 'tested_at']);
            $table->index('compliance_score');
        });

        // Comprehensive Audit Trail Logs Table
        Schema::create('audit_trail_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id')->nullable();
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            $table->string('action_type'); // 'create', 'read', 'update', 'delete', 'login', 'logout'
            $table->string('resource_type'); // 'user', 'parcel', 'contract', 'pricing', etc.
            $table->string('resource_id')->nullable();
            $table->string('module'); // 'admin', 'api', 'frontend', 'backend'
            $table->json('old_values')->nullable(); // Before state
            $table->json('new_values')->nullable(); // After state
            $table->json('changed_fields')->nullable(); // List of changed fields
            $table->string('severity')->default('info'); // 'info', 'warning', 'error', 'critical'
            $table->json('metadata')->nullable(); // Additional context
            $table->string('transaction_id')->nullable(); // For grouping related operations
            $table->timestamp('occurred_at');
            $table->boolean('is_reversible')->default(false);
            $table->json('reversal_data')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'occurred_at']);
            $table->index(['resource_type', 'resource_id']);
            $table->index('action_type');
            $table->index('severity');
            $table->index('module');
            $table->index('transaction_id');
        });

        // Compliance Violations Table
        Schema::create('compliance_violations', function (Blueprint $table) {
            $table->id();
            $table->string('violation_id')->unique();
            $table->string('compliance_framework'); // 'GDPR', 'SOX', 'WCAG', 'HIPAA', 'PCI-DSS'
            $table->string('violation_type');
            $table->string('severity'); // 'low', 'medium', 'high', 'critical'
            $table->text('description');
            $table->json('affected_records')->nullable(); // IDs of affected records
            $table->string('discovered_by'); // 'automated_scan', 'user_report', 'audit'
            $table->foreignId('discovered_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('discovered_at');
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->json('remediation_steps')->nullable();
            $table->boolean('is_false_positive')->default(false);
            $table->timestamps();
            
            $table->index(['compliance_framework', 'severity']);
            $table->index('discovered_at');
            $table->index('resolved_at');
        });

        // User Accessibility Preferences Table
        Schema::create('user_accessibility_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->boolean('high_contrast')->default(false);
            $table->boolean('large_text')->default(false);
            $table->boolean('reduced_motion')->default(false);
            $table->boolean('screen_reader_mode')->default(false);
            $table->boolean('keyboard_navigation_only')->default(false);
            $table->string('font_size')->default('medium'); // 'small', 'medium', 'large', 'extra-large'
            $table->string('color_scheme')->default('default'); // 'default', 'dark', 'high-contrast'
            $table->boolean('disable_animations')->default(false);
            $table->boolean('enable_focus_indicators')->default(true);
            $table->json('custom_css')->nullable(); // User's custom CSS
            $table->json('preferences_data')->nullable(); // Additional preferences
            $table->timestamps();
        });

        // Accessibility Testing Queue Table
        Schema::create('accessibility_test_queue', function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->unique();
            $table->string('page_url');
            $table->string('test_type')->default('automated');
            $table->json('test_config')->nullable(); // Test configuration
            $table->string('status')->default('pending'); // 'pending', 'running', 'completed', 'failed'
            $table->text('error_message')->nullable();
            $table->timestamp('scheduled_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('priority')->default(0); // Higher number = higher priority
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'scheduled_at']);
            $table->index('priority');
        });

        // Audit Report Configuration Table
        Schema::create('audit_report_configs', function (Blueprint $table) {
            $table->id();
            $table->string('config_name')->unique();
            $table->string('report_type'); // 'daily', 'weekly', 'monthly', 'on_demand'
            $table->json('included_modules'); // Modules to include in report
            $table->json('filters')->nullable(); // Report filters
            $table->json('recipients')->nullable(); // Email recipients
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_generated_at')->nullable();
            $table->string('format')->default('pdf'); // 'pdf', 'csv', 'json', 'html'
            $table->json('custom_config')->nullable();
            $table->timestamps();
        });

        // Compliance Monitoring Rules Table
        Schema::create('compliance_monitoring_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name')->unique();
            $table->string('compliance_framework');
            $table->string('rule_type'); // 'threshold', 'pattern', 'anomaly', 'real-time'
            $table->json('rule_definition'); // Rule logic and parameters
            $table->string('severity')->default('medium');
            $table->boolean('is_active')->default(true);
            $table->json('notification_settings')->nullable();
            $table->json('action_settings')->nullable(); // Auto-remediation actions
            $table->timestamp('last_evaluated_at')->nullable();
            $table->integer('evaluation_count')->default(0);
            $table->integer('violation_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_monitoring_rules');
        Schema::dropIfExists('audit_report_configs');
        Schema::dropIfExists('accessibility_test_queue');
        Schema::dropIfExists('user_accessibility_preferences');
        Schema::dropIfExists('compliance_violations');
        Schema::dropIfExists('audit_trail_logs');
        Schema::dropIfExists('accessibility_compliance_logs');
    }
};