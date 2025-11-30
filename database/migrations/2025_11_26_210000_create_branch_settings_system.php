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
        // System-wide settings (admin controlled)
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 30)->default('string'); // string, integer, boolean, json
            $table->string('category', 50)->default('general'); // general, finance, operations, notifications
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false); // Can branches override?
            $table->timestamps();
            
            $table->index('category');
        });

        // Branch-specific setting overrides
        Schema::create('branch_setting_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('key', 100); // References system_settings.key
            $table->text('value')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();
            
            $table->unique(['branch_id', 'key']);
            $table->foreign('updated_by_user_id')->references('id')->on('users')->onDelete('set null');
            
            $table->index('branch_id');
        });

        // Seed default system settings
        DB::table('system_settings')->insert([
            // General settings
            [
                'key' => 'app_name',
                'value' => 'Baraka Courier ERP',
                'type' => 'string',
                'category' => 'general',
                'description' => 'Application name',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'default_currency',
                'value' => 'UGX',
                'type' => 'string',
                'category' => 'finance',
                'description' => 'Default currency code',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'default_timezone',
                'value' => 'Africa/Kampala',
                'type' => 'string',
                'category' => 'general',
                'description' => 'Default timezone',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'default_language',
                'value' => 'en',
                'type' => 'string',
                'category' => 'general',
                'description' => 'Default language code',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Finance settings
            [
                'key' => 'tax_rate',
                'value' => '18',
                'type' => 'decimal',
                'category' => 'finance',
                'description' => 'Default tax rate percentage',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'fuel_surcharge_rate',
                'value' => '5',
                'type' => 'decimal',
                'category' => 'finance',
                'description' => 'Fuel surcharge percentage',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'default_payment_terms',
                'value' => '30',
                'type' => 'integer',
                'category' => 'finance',
                'description' => 'Default payment terms in days',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Operations settings
            [
                'key' => 'sla_standard_hours',
                'value' => '48',
                'type' => 'integer',
                'category' => 'operations',
                'description' => 'Standard SLA in hours',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'sla_express_hours',
                'value' => '24',
                'type' => 'integer',
                'category' => 'operations',
                'description' => 'Express SLA in hours',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'auto_assign_shipments',
                'value' => 'true',
                'type' => 'boolean',
                'category' => 'operations',
                'description' => 'Automatically assign shipments to drivers',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Notification settings
            [
                'key' => 'enable_sms_notifications',
                'value' => 'true',
                'type' => 'boolean',
                'category' => 'notifications',
                'description' => 'Enable SMS notifications',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'enable_email_notifications',
                'value' => 'true',
                'type' => 'boolean',
                'category' => 'notifications',
                'description' => 'Enable email notifications',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_setting_overrides');
        Schema::dropIfExists('system_settings');
    }
};
