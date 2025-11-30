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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_password_change_at')->nullable()->after('password');
            $table->timestamp('password_expires_at')->nullable()->after('last_password_change_at');
            $table->boolean('force_password_change')->default(false)->after('password_expires_at');
            $table->timestamp('last_login_at')->nullable()->after('force_password_change');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_password_change_at',
                'password_expires_at',  
                'force_password_change',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};
