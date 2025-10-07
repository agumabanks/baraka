<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('branch_manager');
            $table->timestamp('assigned_at')->useCurrent();
            $table->string('business_name')->nullable();
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->json('cod_charges')->nullable();
            $table->json('payment_info')->nullable();
            $table->json('settlement_config')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();

            $table->unique('branch_id');
            $table->unique(['branch_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_managers');
    }
};
