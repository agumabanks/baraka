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
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('channel'); // SMS, WHATSAPP, EMAIL
            $table->string('template'); // CREATED, IN_TRANSIT, etc.
            $table->string('to_address'); // phone or email
            $table->string('status')->default('PENDING'); // PENDING, SENT, DELIVERED, FAILED
            $table->string('provider_message_id')->nullable();
            $table->json('payload_json')->nullable(); // template data
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['channel', 'status']);
            $table->index('template');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
