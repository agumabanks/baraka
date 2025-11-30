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
        if (!Schema::hasTable('impersonation_logs')) {
            Schema::create('impersonation_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admin_id');
                $table->unsignedBigInteger('impersonated_user_id');
                $table->string('reason')->nullable();
                $table->string('status', 20)->default('started'); // started, stopped
                $table->timestamp('started_at');
                $table->timestamp('ended_at')->nullable();
                $table->string('ip', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
                
                $table->foreign('admin_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('impersonated_user_id')->references('id')->on('users')->onDelete('cascade');
                
                $table->index(['admin_id', 'status']);
                $table->index('started_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impersonation_logs');
    }
};
