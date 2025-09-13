<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('address'); // phone_e164 or email
            $table->string('channel'); // sms|whatsapp|email
            $table->string('code', 10);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['address', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};

