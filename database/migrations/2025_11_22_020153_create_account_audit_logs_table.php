<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100); // login, logout, password_changed, email_changed, etc.
            $table->string('ip_address', 45); // Support IPv6
            $table->text('user_agent')->nullable();
            $table->json('changes')->nullable(); // Before/after values for updates
            $table->json('metadata')->nullable(); // Device, location, risk_score, etc.
            $table->timestamp('performed_at')->useCurrent();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'performed_at']);
            $table->index('action');
            $table->index('performed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_audit_logs');
    }
};
