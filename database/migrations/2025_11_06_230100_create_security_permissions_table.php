<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('security_permissions')) {
            Schema::create('security_permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('resource'); // e.g., 'shipments', 'financial_data', 'user_management'
                $table->string('action'); // e.g., 'read', 'write', 'delete', 'approve'
                $table->json('conditions')->nullable(); // For conditional permissions
                $table->enum('data_classification', ['public', 'internal', 'confidential', 'restricted'])
                      ->default('internal');
                $table->boolean('requires_approval')->default(false);
                $table->unsignedBigInteger('approval_role_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                
                $table->foreign('approval_role_id')->references('id')->on('security_roles')->onDelete('set null');
                $table->index(['resource', 'action']);
                $table->index('data_classification');
                $table->index('is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_permissions');
    }
};