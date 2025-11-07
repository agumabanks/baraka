<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('security_encryption_keys')) {
            Schema::create('security_encryption_keys', function (Blueprint $table) {
                $table->id();
                $table->string('key_name')->unique();
                $table->string('key_type'); // 'master', 'data_encryption', 'api_signing', 'token_signing'
                $table->text('key_value'); // Encrypted key value
                $table->string('algorithm')->default('AES-256-GCM');
                $table->unsignedBigInteger('key_length')->default(256);
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('rotated_at')->nullable();
                $table->unsignedBigInteger('rotated_by')->nullable();
                $table->string('status')->default('active'); // 'active', 'inactive', 'expired', 'compromised'
                $table->json('metadata')->nullable(); // Additional key metadata
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                
                $table->index('key_type');
                $table->index('status');
                $table->index('expires_at');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_encryption_keys');
    }
};