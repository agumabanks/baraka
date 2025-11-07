<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('security_privacy_consents')) {
            Schema::create('security_privacy_consents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('consent_type'); // 'marketing', 'analytics', 'necessary', 'third_party'
                $table->boolean('consent_given');
                $table->string('consent_source')->nullable(); // 'web_form', 'api', 'email', 'phone'
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('consent_data')->nullable(); // Additional consent metadata
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('withdrawn_at')->nullable();
                $table->string('withdrawal_method')->nullable();
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index('user_id');
                $table->index('consent_type');
                $table->index('consent_given');
                $table->index('expires_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_privacy_consents');
    }
};