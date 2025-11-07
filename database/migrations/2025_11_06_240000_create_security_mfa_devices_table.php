<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('security_mfa_devices')) {
            Schema::create('security_mfa_devices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('device_name');
                $table->enum('device_type', ['sms', 'email', 'totp', 'hardware', 'biometric'])->default('totp');
                $table->string('device_identifier')->nullable(); // Phone number, email, etc.
                $table->boolean('is_verified')->default(false);
                $table->boolean('is_primary')->default(false);
                $table->text('secret_key')->nullable(); // For TOTP
                $table->json('backup_codes')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index('user_id');
                $table->index('device_type');
                $table->index('is_verified');
                $table->index('is_primary');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_mfa_devices');
    }
};