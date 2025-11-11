<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint');
            $table->string('method', 10);
            $table->integer('status_code')->nullable();
            $table->unsignedInteger('response_time_ms');
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('ip_address');
            $table->json('metadata')->nullable();
            $table->timestamp('requested_at');
            $table->timestamps();

            $table->index(['endpoint', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['status_code', 'created_at']);
            $table->index('response_time_ms');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};
