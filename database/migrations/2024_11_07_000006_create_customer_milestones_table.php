<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->enum('milestone_type', ['shipment_count', 'revenue_volume', 'tenure', 'tier_upgrade']);
            $table->integer('milestone_value');
            $table->timestamp('achieved_at');
            $table->string('reward_given', 100)->nullable();
            $table->json('reward_details')->nullable();
            $table->timestamps();
            
            $table->index(['customer_id', 'milestone_type']);
            $table->index('achieved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_milestones');
    }
};