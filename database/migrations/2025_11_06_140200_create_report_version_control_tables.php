<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportVersionControlTables extends Migration
{
    public function up()
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::dropIfExists('report_sharing');
        Schema::dropIfExists('report_execution_history');
        Schema::dropIfExists('report_tags');
        Schema::dropIfExists('report_definitions_version');
        Schema::dropIfExists('report_definitions');

        // Main report definitions table
        Schema::create('report_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->enum('type', ['dashboard', 'operational', 'financial', 'performance', 'custom']);
            $table->string('category', 100)->nullable();
            $table->json('parameters')->nullable();
            $table->longText('query_definition');
            $table->enum('output_format', ['json', 'csv', 'xlsx', 'pdf'])->default('json');
            $table->boolean('is_public')->default(false);
            $table->string('created_by', 100);
            $table->string('updated_by', 100)->nullable();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('type');
            $table->index('category');
            $table->index('created_by');
            $table->index(['type', 'category']);
        });

        // Report versions table
        Schema::create('report_definitions_version', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id');
            $table->string('version', 20); // e.g., v1.0, v1.1
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->enum('type', ['dashboard', 'operational', 'financial', 'performance', 'custom']);
            $table->string('category', 100)->nullable();
            $table->json('parameters')->nullable();
            $table->longText('query_definition');
            $table->enum('output_format', ['json', 'csv', 'xlsx', 'pdf'])->default('json');
            $table->text('change_log')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('updated_by', 100);
            $table->timestamps();

            $table->unique(['report_id', 'version']);
            $table->index('report_id');
            $table->index('is_active');
            $table->index(['report_id', 'is_active']);
            $table->index('version');
        });

        // Report tags table
        Schema::create('report_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id');
            $table->string('tag', 50);
            $table->timestamps();

            $table->unique(['report_id', 'tag']);
            $table->index('tag');
        });

        // Report execution history table
        Schema::create('report_execution_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id');
            $table->unsignedBigInteger('version_id');
            $table->string('executed_by', 100);
            $table->json('parameters_used')->nullable();
            $table->json('query_results')->nullable();
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->integer('execution_time_ms')->nullable();
            $table->integer('rows_returned')->nullable();
            $table->string('output_file_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('report_id');
            $table->index('version_id');
            $table->index('executed_by');
            $table->index('status');
            $table->index('created_at');
            $table->index(['report_id', 'status']);
        });

        // Report sharing/permissions table
        Schema::create('report_sharing', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id');
            $table->string('shared_with_type', 20); // 'user', 'role', 'group'
            $table->string('shared_with_identifier', 100); // user_id, role_name, group_id
            $table->enum('permission', ['view', 'edit', 'execute', 'admin']);
            $table->string('granted_by', 100);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['report_id', 'shared_with_type', 'shared_with_identifier', 'permission'], 'report_sharing_acl_unique');
            $table->index('report_id');
            $table->index(['shared_with_type', 'shared_with_identifier']);
            $table->index('permission');
        });

        // Add foreign key constraints
        Schema::table('report_definitions_version', function (Blueprint $table) {
            $table->foreign('report_id')->references('id')->on('report_definitions')->onDelete('cascade');
        });

        Schema::table('report_tags', function (Blueprint $table) {
            $table->foreign('report_id')->references('id')->on('report_definitions')->onDelete('cascade');
        });

        Schema::table('report_execution_history', function (Blueprint $table) {
            $table->foreign('report_id')->references('id')->on('report_definitions')->onDelete('cascade');
            $table->foreign('version_id')->references('id')->on('report_definitions_version')->onDelete('cascade');
        });

        Schema::table('report_sharing', function (Blueprint $table) {
            $table->foreign('report_id')->references('id')->on('report_definitions')->onDelete('cascade');
        });

        Schema::table('report_definitions', function (Blueprint $table) {
            $table->foreign('current_version_id')->references('id')->on('report_definitions_version')->onDelete('set null');
        });
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::dropIfExists('report_sharing');
        Schema::dropIfExists('report_execution_history');
        Schema::dropIfExists('report_tags');
        Schema::dropIfExists('report_definitions_version');
        Schema::dropIfExists('report_definitions');
    }
}