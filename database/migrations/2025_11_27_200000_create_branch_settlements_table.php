<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_number')->unique();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            
            // Revenue
            $table->decimal('total_shipment_revenue', 15, 2)->default(0);
            $table->decimal('total_cod_collected', 15, 2)->default(0);
            $table->integer('shipment_count')->default(0);
            $table->integer('cod_shipment_count')->default(0);
            
            // Expenses
            $table->decimal('total_expenses', 15, 2)->default(0);
            $table->decimal('driver_payments', 15, 2)->default(0);
            $table->decimal('operational_costs', 15, 2)->default(0);
            
            // Net
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->decimal('amount_due_to_hq', 15, 2)->default(0);
            $table->decimal('amount_due_from_hq', 15, 2)->default(0);
            
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'settled'])->default('draft');
            
            // Approval workflow
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('settled_at')->nullable();
            
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            
            $table->json('breakdown')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();
            
            $table->index(['branch_id', 'period_start', 'period_end']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_settlements');
    }
};
