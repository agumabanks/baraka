<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Notification templates
        if (!Schema::hasTable('notification_templates')) {
            Schema::create('notification_templates', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique(); // e.g., 'shipment_created', 'out_for_delivery'
                $table->string('name');
                $table->string('category'); // shipment, invoice, account, system
                $table->text('description')->nullable();
                
                // Email template
                $table->string('email_subject')->nullable();
                $table->longText('email_body_html')->nullable();
                $table->text('email_body_text')->nullable();
                
                // SMS template (short)
                $table->string('sms_body', 500)->nullable();
                
                // Push notification
                $table->string('push_title')->nullable();
                $table->string('push_body')->nullable();
                
                // WhatsApp template
                $table->string('whatsapp_template_id')->nullable();
                $table->json('whatsapp_variables')->nullable();
                
                // Available variables for this template
                $table->json('available_variables')->nullable();
                
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Notification preferences (per user/customer)
        if (!Schema::hasTable('notification_preferences')) {
            Schema::create('notification_preferences', function (Blueprint $table) {
                $table->id();
                $table->morphs('notifiable'); // user_id, customer_id, etc.
                
                // Channel preferences
                $table->boolean('email_enabled')->default(true);
                $table->boolean('sms_enabled')->default(true);
                $table->boolean('push_enabled')->default(true);
                $table->boolean('whatsapp_enabled')->default(false);
                
                // Event preferences (which events to receive)
                $table->json('enabled_events')->nullable(); // ['shipment_created', 'delivered', etc.]
                $table->json('disabled_events')->nullable();
                
                // Quiet hours
                $table->time('quiet_start')->nullable();
                $table->time('quiet_end')->nullable();
                $table->string('timezone')->default('UTC');
                
                // Frequency limits
                $table->integer('max_sms_per_day')->default(10);
                $table->integer('max_push_per_hour')->default(5);
                
                $table->timestamps();
                
                $table->unique(['notifiable_type', 'notifiable_id']);
            });
        }

        // Notification log (audit trail)
        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
                $table->id();
                $table->string('notification_id')->index(); // UUID for grouping multi-channel
                $table->string('template_code')->nullable();
                
                // Recipient
                $table->morphs('notifiable');
                $table->string('recipient_email')->nullable();
                $table->string('recipient_phone')->nullable();
                $table->string('recipient_device_token')->nullable();
                
                // Channel and content
                $table->string('channel'); // email, sms, push, whatsapp
                $table->string('subject')->nullable();
                $table->text('body')->nullable();
                
                // Status tracking
                $table->string('status')->default('pending'); // pending, sent, delivered, failed, bounced
                $table->text('error_message')->nullable();
                $table->string('provider_message_id')->nullable();
                
                // Timestamps
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                
                // Related entity
                $table->string('related_type')->nullable(); // shipment, invoice, etc.
                $table->unsignedBigInteger('related_id')->nullable();
                
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index(['channel', 'status']);
                $table->index(['related_type', 'related_id']);
                $table->index('created_at');
            });
        }

        // Device tokens for push notifications
        if (!Schema::hasTable('device_tokens')) {
            Schema::create('device_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable'); // user_id, customer_id
                $table->string('token')->unique();
                $table->string('platform'); // ios, android, web
                $table->string('device_name')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
                
                $table->index(['tokenable_type', 'tokenable_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_templates');
    }
};
