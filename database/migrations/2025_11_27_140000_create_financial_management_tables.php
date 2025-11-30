<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // COD Collections tracking
        if (!Schema::hasTable('cod_collections')) {
            Schema::create('cod_collections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shipment_id');
                $table->unsignedBigInteger('collected_by')->nullable(); // driver/agent
                $table->unsignedBigInteger('branch_id')->nullable();
                
                $table->decimal('expected_amount', 15, 2);
                $table->decimal('collected_amount', 15, 2)->nullable();
                $table->string('currency', 3)->default('USD');
                $table->decimal('exchange_rate', 10, 6)->default(1);
                
                $table->string('collection_method')->nullable(); // cash, mobile_money, card
                $table->string('payment_reference')->nullable();
                
                $table->string('status')->default('pending'); // pending, collected, verified, remitted, disputed
                $table->timestamp('collected_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('remitted_at')->nullable();
                
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
                $table->foreign('collected_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
                
                $table->index(['status', 'branch_id']);
                $table->index('collected_at');
            });
        }

        // Merchant Settlements
        if (!Schema::hasTable('merchant_settlements')) {
            Schema::create('merchant_settlements', function (Blueprint $table) {
                $table->id();
                $table->string('settlement_number')->unique();
                $table->unsignedBigInteger('merchant_id'); // customer acting as merchant
                $table->unsignedBigInteger('branch_id')->nullable();
                
                $table->date('period_start');
                $table->date('period_end');
                
                $table->integer('shipment_count')->default(0);
                $table->decimal('total_shipping_fees', 15, 2)->default(0);
                $table->decimal('total_cod_collected', 15, 2)->default(0);
                $table->decimal('total_deductions', 15, 2)->default(0);
                $table->decimal('net_payable', 15, 2)->default(0);
                $table->string('currency', 3)->default('USD');
                
                $table->string('status')->default('draft'); // draft, pending_approval, approved, processing, paid, cancelled
                
                $table->string('payment_method')->nullable();
                $table->string('payment_reference')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('paid_at')->nullable();
                
                $table->json('breakdown')->nullable(); // detailed breakdown
                $table->text('notes')->nullable();
                $table->timestamps();
                
                $table->foreign('merchant_id')->references('id')->on('customers')->cascadeOnDelete();
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
                $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
                
                $table->index(['merchant_id', 'status']);
                $table->index(['period_start', 'period_end']);
            });
        }

        // Settlement Line Items
        if (!Schema::hasTable('settlement_items')) {
            Schema::create('settlement_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('settlement_id');
                $table->unsignedBigInteger('shipment_id');
                
                $table->decimal('shipping_fee', 10, 2)->default(0);
                $table->decimal('cod_amount', 10, 2)->default(0);
                $table->decimal('insurance_fee', 10, 2)->default(0);
                $table->decimal('other_charges', 10, 2)->default(0);
                $table->decimal('deductions', 10, 2)->default(0);
                $table->decimal('net_amount', 10, 2)->default(0);
                
                $table->string('deduction_reason')->nullable();
                $table->timestamps();
                
                $table->foreign('settlement_id')->references('id')->on('merchant_settlements')->cascadeOnDelete();
                $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
            });
        }

        // Exchange Rates
        if (!Schema::hasTable('exchange_rates')) {
            Schema::create('exchange_rates', function (Blueprint $table) {
                $table->id();
                $table->string('base_currency', 3);
                $table->string('target_currency', 3);
                $table->decimal('rate', 15, 6);
                $table->date('effective_date');
                $table->string('source')->default('manual'); // manual, api
                $table->timestamps();
                
                $table->unique(['base_currency', 'target_currency', 'effective_date'], 'exchange_rates_unique');
                $table->index('effective_date');
            });
        }

        // Financial Transactions Log
        if (!Schema::hasTable('financial_transactions')) {
            Schema::create('financial_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_id')->unique();
                $table->string('type'); // cod_collection, settlement_payment, refund, adjustment
                
                $table->morphs('transactable'); // shipment, settlement, invoice
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                
                $table->decimal('amount', 15, 2);
                $table->string('currency', 3)->default('USD');
                $table->string('direction'); // credit, debit
                
                $table->string('payment_method')->nullable();
                $table->string('payment_reference')->nullable();
                
                $table->decimal('balance_before', 15, 2)->nullable();
                $table->decimal('balance_after', 15, 2)->nullable();
                
                $table->string('status')->default('completed');
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                
                $table->index(['type', 'created_at'], 'fin_trans_type_created_idx');
            });
        }

        // Driver Cash Accounts (for COD tracking)
        if (!Schema::hasTable('driver_cash_accounts')) {
            Schema::create('driver_cash_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('driver_id')->unique();
                $table->decimal('balance', 15, 2)->default(0);
                $table->decimal('pending_remittance', 15, 2)->default(0);
                $table->string('currency', 3)->default('USD');
                $table->timestamp('last_remittance_at')->nullable();
                $table->timestamps();
                
                $table->foreign('driver_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_cash_accounts');
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('settlement_items');
        Schema::dropIfExists('merchant_settlements');
        Schema::dropIfExists('cod_collections');
    }
};
