<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('security_user_roles')) {
            Schema::create('security_user_roles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('security_role_id');
                $table->json('scope_restrictions')->nullable(); // For limiting role scope
                $table->timestamp('assigned_at')->useCurrent();
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('security_role_id')->references('id')->on('security_roles')->onDelete('cascade');
                $table->unique(['user_id', 'security_role_id']);
                $table->index('assigned_at');
                $table->index('expires_at');
                $table->index('is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_user_roles');
    }
};