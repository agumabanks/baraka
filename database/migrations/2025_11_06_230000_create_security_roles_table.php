<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('security_roles')) {
            Schema::create('security_roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('parent_role_id')->nullable();
                $table->json('inherited_permissions')->nullable();
                $table->json('role_hierarchy_path')->nullable(); // For efficient hierarchical queries
                $table->enum('level', ['system', 'enterprise', 'department', 'functional', 'task'])
                      ->default('functional');
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                
                $table->foreign('parent_role_id')->references('id')->on('security_roles')->onDelete('cascade');
                $table->index(['parent_role_id', 'is_active']);
                $table->index('level');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_roles');
    }
};