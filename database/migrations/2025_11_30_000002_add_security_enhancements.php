<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add MFA fields to users table if missing
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'mfa_enabled')) {
                    $table->boolean('mfa_enabled')->default(false)->after('remember_token');
                }
                if (!Schema::hasColumn('users', 'mfa_method')) {
                    $table->string('mfa_method', 20)->nullable()->after('mfa_enabled');
                }
                if (!Schema::hasColumn('users', 'mfa_secret')) {
                    $table->string('mfa_secret')->nullable()->after('mfa_method');
                }
                if (!Schema::hasColumn('users', 'mfa_backup_codes')) {
                    $table->json('mfa_backup_codes')->nullable()->after('mfa_secret');
                }
                if (!Schema::hasColumn('users', 'mfa_verified_at')) {
                    $table->timestamp('mfa_verified_at')->nullable()->after('mfa_backup_codes');
                }
                if (!Schema::hasColumn('users', 'is_locked')) {
                    $table->boolean('is_locked')->default(false)->after('mfa_verified_at');
                }
                if (!Schema::hasColumn('users', 'failed_login_attempts')) {
                    $table->integer('failed_login_attempts')->default(0)->after('is_locked');
                }
                if (!Schema::hasColumn('users', 'locked_at')) {
                    $table->timestamp('locked_at')->nullable()->after('failed_login_attempts');
                }
                if (!Schema::hasColumn('users', 'password_changed_at')) {
                    $table->timestamp('password_changed_at')->nullable()->after('locked_at');
                }
                if (!Schema::hasColumn('users', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable()->after('password_changed_at');
                }
                if (!Schema::hasColumn('users', 'last_login_ip')) {
                    $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
                }
            });
        }

        // Create MFA devices table if not exists
        if (!Schema::hasTable('mfa_devices')) {
            Schema::create('mfa_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('type', 20); // totp, sms, email
                $table->string('name', 100)->nullable();
                $table->string('identifier', 255)->nullable(); // phone or email
                $table->string('secret', 255)->nullable();
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_verified')->default(false);
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'type']);
            });
        }

        // Create security settings table if not exists
        if (!Schema::hasTable('security_settings')) {
            Schema::create('security_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->text('value')->nullable();
                $table->string('type', 20)->default('string');
                $table->text('description')->nullable();
                $table->timestamps();
            });

            // Insert default security settings
            $defaults = [
                ['key' => 'mfa_required_roles', 'value' => json_encode(['admin', 'super-admin']), 'type' => 'json', 'description' => 'Roles that require MFA'],
                ['key' => 'mfa_required_all', 'value' => 'false', 'type' => 'boolean', 'description' => 'Require MFA for all users'],
                ['key' => 'session_timeout_minutes', 'value' => '120', 'type' => 'integer', 'description' => 'Session timeout in minutes'],
                ['key' => 'max_login_attempts', 'value' => '5', 'type' => 'integer', 'description' => 'Max failed login attempts before lockout'],
                ['key' => 'lockout_duration_minutes', 'value' => '30', 'type' => 'integer', 'description' => 'Account lockout duration'],
                ['key' => 'password_expiry_days', 'value' => '90', 'type' => 'integer', 'description' => 'Password expiry in days (0 = disabled)'],
                ['key' => 'min_password_length', 'value' => '12', 'type' => 'integer', 'description' => 'Minimum password length'],
                ['key' => 'require_special_char', 'value' => 'true', 'type' => 'boolean', 'description' => 'Require special character in password'],
                ['key' => 'api_rate_limit_per_minute', 'value' => '60', 'type' => 'integer', 'description' => 'API requests per minute'],
            ];

            foreach ($defaults as $setting) {
                \DB::table('security_settings')->insert(array_merge($setting, ['created_at' => now(), 'updated_at' => now()]));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mfa_devices');
        Schema::dropIfExists('security_settings');

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                foreach (['mfa_enabled', 'mfa_method', 'mfa_secret', 'mfa_backup_codes', 'mfa_verified_at', 'is_locked', 'failed_login_attempts', 'locked_at', 'password_changed_at', 'last_login_at', 'last_login_ip'] as $col) {
                    if (Schema::hasColumn('users', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
