<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        // ETL Batch tracking table
        Schema::create('etl_batches', function (Blueprint $table) {
            $table->string('batch_id', 50)->primary();
            $table->string('pipeline_name', 100);
            $table->string('status', 20)->default('PENDING'); // PENDING, RUNNING, COMPLETED, FAILED
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('records_processed')->default(0);
            $table->integer('records_successful')->default(0);
            $table->integer('records_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->json('execution_metrics')->nullable(); // Processing time, memory usage, etc.
            $table->string('triggered_by', 100)->nullable(); // User or system that triggered
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index(['pipeline_name', 'status']);
            $table->index('started_at');
        });

        // Audit trail for all data changes
        Schema::create('etl_audit_log', function (Blueprint $table) {
            $table->bigIncrements('audit_id');
            $table->string('table_name', 100);
            $table->bigInteger('record_id');
            $table->enum('operation', ['INSERT', 'UPDATE', 'DELETE']);
            $table->enum('change_type', ['MANUAL', 'ETL_BATCH', 'API_IMPORT', 'SYSTEM_UPDATE']);
            
            // Before and after values
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->json('changed_fields')->nullable();
            
            // Context
            $table->string('batch_id', 50)->nullable();
            $table->string('source_system', 50)->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Quality metrics
            $table->decimal('data_quality_score', 3, 2)->nullable();
            $table->json('validation_errors')->nullable();
            $table->json('anomaly_flags')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['table_name', 'record_id']);
            $table->index('batch_id');
            $table->index(['operation', 'created_at']);
        });

        // Data lineage tracking
        Schema::create('etl_data_lineage', function (Blueprint $table) {
            $table->bigIncrements('lineage_id');
            $table->string('source_table', 100);
            $table->bigInteger('source_record_id');
            $table->string('target_table', 100);
            $table->bigInteger('target_record_id');
            $table->json('transformation_rules')->nullable();
            $table->string('batch_id', 50);
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['source_table', 'source_record_id']);
            $table->index(['target_table', 'target_record_id']);
            $table->index('batch_id');
        });

        // Data quality rules configuration
        Schema::create('etl_data_quality_rules', function (Blueprint $table) {
            $table->bigIncrements('rule_id');
            $table->string('rule_name', 100);
            $table->string('table_name', 100);
            $table->string('rule_type', 50); // REQUIRED_FIELD, DATA_TYPE, BUSINESS_RULE, STATISTICAL
            $table->text('rule_definition');
            $table->string('severity', 20)->default('MEDIUM'); // LOW, MEDIUM, HIGH, CRITICAL
            $table->enum('action_on_violation', ['REJECT', 'FLAG', 'LOG', 'CORRECT'])->default('FLAG');
            $table->boolean('is_active')->default(true);
            $table->json('rule_parameters')->nullable(); // For statistical rules: thresholds, etc.
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index(['table_name', 'rule_type']);
            $table->index('is_active');
        });

        // Data quality violations tracking
        Schema::create('etl_data_quality_violations', function (Blueprint $table) {
            $table->bigIncrements('violation_id');
            $table->bigInteger('rule_id');
            $table->string('table_name', 100);
            $table->bigInteger('record_id');
            $table->string('violation_type', 50);
            $table->text('violation_description');
            $table->text('violation_details')->nullable(); // JSON with field names, values, etc.
            $table->string('severity', 20);
            $table->enum('status', ['OPEN', 'RESOLVED', 'IGNORED', 'ESCALATED'])->default('OPEN');
            $table->string('batch_id', 50);
            $table->bigInteger('resolved_by')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('rule_id');
            $table->index(['table_name', 'record_id']);
            $table->index(['status', 'severity']);
            $table->index('batch_id');
        });

        // Anomaly detection results
        Schema::create('etl_anomaly_detection', function (Blueprint $table) {
            $table->bigIncrements('anomaly_id');
            $table->string('table_name', 100);
            $table->bigInteger('record_id')->nullable();
            $table->string('anomaly_type', 50); // STATISTICAL, BUSINESS_RULE, PATTERN
            $table->string('anomaly_category', 100);
            $table->text('description');
            $table->decimal('severity_score', 5, 3); // 0.000 to 1.000
            $table->string('detection_method', 50); // Z_SCORE, IQR, ISOLATION_FOREST, etc.
            $table->json('anomaly_data')->nullable(); // Field values, expected ranges, etc.
            $table->json('context_data')->nullable(); // Related records, time period, etc.
            $table->string('batch_id', 50);
            $table->enum('status', ['DETECTED', 'INVESTIGATED', 'CONFIRMED', 'FALSE_POSITIVE'])->default('DETECTED');
            $table->text('investigation_notes')->nullable();
            $table->bigInteger('investigated_by')->nullable();
            $table->timestamp('investigated_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['table_name', 'anomaly_type']);
            $table->index(['severity_score', 'status']);
            $table->index('batch_id');
        });

        // ETL pipeline configuration
        Schema::create('etl_pipeline_configs', function (Blueprint $table) {
            $table->bigIncrements('config_id');
            $table->string('pipeline_name', 100);
            $table->string('pipeline_version', 20);
            $table->json('source_configurations'); // API endpoints, database connections, etc.
            $table->json('transformation_configurations'); // Business rules, calculations, etc.
            $table->json('destination_configurations'); // Target tables, load strategies
            $table->json('quality_rules'); // Applied data quality rules
            $table->string('schedule_expression')->nullable(); // Cron expression
            $table->boolean('is_active')->default(true);
            $table->json('execution_parameters')->nullable(); // Memory limits, timeout, etc.
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index('pipeline_name');
            $table->index('is_active');
        });

        // Report definitions and version control
        Schema::create('etl_report_definitions', function (Blueprint $table) {
            $table->bigIncrements('report_id');
            $table->string('report_name', 200);
            $table->string('report_code', 50)->unique();
            $table->text('description')->nullable();
            $table->string('report_type', 50); // DASHBOARD, OPERATIONAL, FINANCIAL, ANALYTICAL
            $table->json('sql_query'); // Main query definition
            $table->json('parameters')->nullable(); // Report parameters
            $table->json('visualization_config')->nullable(); // Chart types, colors, etc.
            $table->string('version', 20)->default('1.0.0');
            $table->string('status', 20)->default('DRAFT'); // DRAFT, ACTIVE, DEPRECATED
            $table->bigInteger('created_by');
            $table->bigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index('report_type');
            $table->index('status');
            $table->index('created_by');
        });

        // Report version history
        Schema::create('etl_report_version_history', function (Blueprint $table) {
            $table->bigIncrements('version_id');
            $table->bigInteger('report_id');
            $table->string('version', 20);
            $table->json('sql_query'); // Snapshot of query at this version
            $table->json('parameters')->nullable();
            $table->json('change_log')->nullable(); // What changed in this version
            $table->text('change_reason')->nullable();
            $table->bigInteger('version_created_by');
            $table->timestamp('version_created_at')->useCurrent();
            
            $table->index('report_id');
            $table->index(['report_id', 'version']);
        });

        // Add foreign key constraints
        Schema::table('etl_audit_log', function (Blueprint $table) {
            $table->foreign('batch_id')->references('batch_id')->on('etl_batches')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('etl_data_lineage', function (Blueprint $table) {
            $table->foreign('batch_id')->references('batch_id')->on('etl_batches')->onDelete('cascade');
        });

        Schema::table('etl_data_quality_violations', function (Blueprint $table) {
            $table->foreign('rule_id')->references('rule_id')->on('etl_data_quality_rules')->onDelete('cascade');
            $table->foreign('batch_id')->references('batch_id')->on('etl_batches')->onDelete('cascade');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('etl_anomaly_detection', function (Blueprint $table) {
            $table->foreign('batch_id')->references('batch_id')->on('etl_batches')->onDelete('cascade');
            $table->foreign('investigated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('etl_report_definitions', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('etl_report_version_history', function (Blueprint $table) {
            $table->foreign('report_id')->references('report_id')->on('etl_report_definitions')->onDelete('cascade');
            $table->foreign('version_created_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::dropIfExists('etl_report_version_history');
        Schema::dropIfExists('etl_report_definitions');
        Schema::dropIfExists('etl_pipeline_configs');
        Schema::dropIfExists('etl_anomaly_detection');
        Schema::dropIfExists('etl_data_quality_violations');
        Schema::dropIfExists('etl_data_quality_rules');
        Schema::dropIfExists('etl_data_lineage');
        Schema::dropIfExists('etl_audit_log');
        Schema::dropIfExists('etl_batches');
    }
};