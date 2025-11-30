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
        Schema::create('login_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id', 100)->unique();
            $table->string('device_name')->nullable(); // iPhone 13, Chrome on Windows, etc.
            $table->string('device_type', 50)->nullable(); // mobile, desktop, tablet
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('location')->nullable(); // City, Country
            $table->timestamp('logged_in_at')->useCurrent();
            $table->timestamp('last_activity_at')->useCurrent();
            $table->timestamp('logged_out_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'logged_in_at']);
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_sessions');
    }
};
