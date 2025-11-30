<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Enhances the roles table to support 15-level hierarchy and granular permissions
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Role hierarchy and categorization
            $table->integer('role_level')->default(100)->after('slug'); // Lower number = higher privilege
            $table->enum('role_category', ['system', 'branch', 'field', 'client'])->default('branch')->after('role_level');
            $table->enum('branch_scope', ['all', 'region', 'single', 'none'])->default('single')->after('role_category');
            
            // Role inheritance
            $table->unsignedBigInteger('parent_role_id')->nullable()->after('branch_scope');
            
            // Capabilities JSON field for granular permissions
            $table->json('capabilities')->nullable()->after('permissions');
            
            // Additional metadata
            $table->text('description')->nullable()->after('capabilities');
            $table->boolean('is_system_role')->default(false)->after('description'); // Cannot be deleted
            $table->integer('max_users')->nullable()->after('is_system_role'); // Limit number of users with this role
            
            // Indexes
            $table->index('role_level');
            $table->index('role_category');
            $table->index('branch_scope');
            $table->index('is_system_role');
            
            // Foreign key
            $table->foreign('parent_role_id')->references('id')->on('roles')->nullOnDelete();
        });

        // Create role capabilities reference table
        Schema::create('role_capabilities', function (Blueprint $table) {
            $table->id();
            $table->string('capability_key')->unique(); // e.g., 'shipments.create', 'invoices.approve'
            $table->string('module'); // e.g., 'shipments', 'finance', 'warehouse'
            $table->string('capability_name'); // Human-readable name
            $table->text('description')->nullable();
            $table->integer('privilege_level')->default(50); // Required privilege level
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('module');
            $table->index(['capability_key', 'is_active']);
        });

        // User-specific permission overrides
        Schema::create('user_permission_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('capability_key');
            $table->enum('override_type', ['grant', 'revoke']); // grant = add permission, revoke = remove permission
            $table->unsignedBigInteger('granted_by')->nullable(); // Which admin granted this
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'capability_key']);
            $table->index(['user_id', 'override_type']);
            
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('granted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_permission_overrides');
        Schema::dropIfExists('role_capabilities');

        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['parent_role_id']);
            $table->dropIndex(['role_level']);
            $table->dropIndex(['role_category']);
            $table->dropIndex(['branch_scope']);
            $table->dropIndex(['is_system_role']);
            
            $table->dropColumn([
                'role_level',
                'role_category',
                'branch_scope',
                'parent_role_id',
                'capabilities',
                'description',
                'is_system_role',
                'max_users',
            ]);
        });
    }
};
