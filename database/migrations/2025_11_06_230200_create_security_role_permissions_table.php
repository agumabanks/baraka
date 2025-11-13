<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('security_role_permissions');

        Schema::create('security_role_permissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('security_role_id');
                $table->unsignedBigInteger('security_permission_id');
                $table->json('conditions')->nullable(); // For conditional permissions
                $table->timestamp('granted_at')->useCurrent();
                $table->unsignedBigInteger('granted_by')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->unsignedBigInteger('revoked_by')->nullable();
                $table->text('notes')->nullable();
                
                $table->foreign('security_role_id')->references('id')->on('security_roles')->onDelete('cascade');
                $table->foreign('security_permission_id')->references('id')->on('security_permissions')->onDelete('cascade');
                $table->unique(['security_role_id', 'security_permission_id'], 'security_role_permission_unique');
                $table->index('granted_at');
                $table->index('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_role_permissions');
    }
};