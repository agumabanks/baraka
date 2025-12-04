<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // POS-UX-06: Route templates
        if (!Schema::hasTable('route_templates')) {
            Schema::create('route_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('origin_branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('destination_branch_id')->constrained('branches')->cascadeOnDelete();
                $table->string('default_service_level')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->index(['origin_branch_id', 'destination_branch_id']);
                $table->index('active');
            });
        }

        // POS-BR-03: Route capabilities matrix
        if (!Schema::hasTable('route_capabilities')) {
            Schema::create('route_capabilities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('origin_branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('destination_branch_id')->constrained('branches')->cascadeOnDelete();
                $table->string('service_level');
                $table->decimal('max_weight', 10, 2)->nullable();
                $table->boolean('hazmat_allowed')->default(false);
                $table->boolean('cod_allowed')->default(true);
                $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
                $table->timestamps();

                $table->unique(['origin_branch_id', 'destination_branch_id', 'service_level'], 'route_cap_unique');
                $table->index('status');
            });
        }

        // POS-BR-02: Service constraints
        if (!Schema::hasTable('service_constraints')) {
            Schema::create('service_constraints', function (Blueprint $table) {
                $table->id();
                $table->string('service_level');
                $table->foreignId('origin_branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
                $table->foreignId('destination_branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
                $table->decimal('min_weight', 10, 3)->default(0);
                $table->decimal('max_weight', 10, 3)->nullable();
                $table->decimal('min_declared_value', 12, 2)->nullable();
                $table->decimal('max_declared_value', 12, 2)->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->index(['service_level', 'origin_branch_id', 'destination_branch_id'], 'svc_constraint_idx');
            });
        }

        // POS-RATE-04: Tariffs (public rate tables)
        if (!Schema::hasTable('tariffs')) {
            Schema::create('tariffs', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('service_level');
                $table->string('zone')->nullable();
                $table->decimal('weight_from', 10, 3)->default(0);
                $table->decimal('weight_to', 10, 3)->nullable();
                $table->decimal('base_rate', 12, 2)->default(0);
                $table->decimal('per_kg_rate', 12, 2)->default(0);
                $table->decimal('fuel_surcharge_percent', 5, 2)->default(0);
                $table->string('currency', 3)->default('UGX');
                $table->string('version')->default('1.0');
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->index(['service_level', 'zone', 'active']);
                $table->index(['effective_from', 'effective_to']);
            });
        }

        // POS-RATE-04: Customer contracts
        if (!Schema::hasTable('customer_contracts')) {
            Schema::create('customer_contracts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->string('contract_number')->unique();
                $table->string('name');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->decimal('credit_limit', 14, 2)->nullable();
                $table->integer('payment_terms_days')->default(30);
                $table->decimal('discount_percent', 5, 2)->default(0);
                $table->enum('status', ['draft', 'active', 'suspended', 'expired', 'terminated'])->default('draft');
                $table->text('notes')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->index(['customer_id', 'status']);
                $table->index(['start_date', 'end_date']);
            });
        }

        // POS-RATE-04: Customer contract items (rate overrides)
        if (!Schema::hasTable('customer_contract_items')) {
            Schema::create('customer_contract_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained('customer_contracts')->cascadeOnDelete();
                $table->string('service_level');
                $table->string('zone')->nullable();
                $table->decimal('weight_from', 10, 3)->default(0);
                $table->decimal('weight_to', 10, 3)->nullable();
                $table->decimal('base_rate', 12, 2)->nullable();
                $table->decimal('per_kg_rate', 12, 2)->nullable();
                $table->decimal('discount_percent', 5, 2)->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->index(['contract_id', 'service_level']);
            });
        }

        // POS-PAY-02: Payment transactions
        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('shipment_id')->nullable()->constrained('shipments')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->string('idempotency_key')->nullable()->unique();
                $table->decimal('amount', 14, 2);
                $table->string('currency', 3)->default('UGX');
                $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending');
                $table->enum('method', ['cash', 'card', 'mobile_money', 'bank_transfer', 'on_account', 'cheque'])->default('cash');
                $table->enum('payer_type', ['sender', 'receiver', 'third_party', 'account'])->default('sender');
                $table->string('external_reference')->nullable();
                $table->string('gateway_response_code')->nullable();
                $table->text('gateway_response_message')->nullable();
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['shipment_id', 'status']);
                $table->index(['customer_id', 'status']);
                $table->index('method');
                $table->index('created_at');
            });
        }

        // POS-REL-01: Shipment drafts
        if (!Schema::hasTable('shipment_drafts')) {
            Schema::create('shipment_drafts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->json('payload');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->enum('status', ['draft', 'pending', 'completed', 'expired', 'cancelled'])->default('draft');
                $table->foreignId('shipment_id')->nullable()->constrained('shipments')->nullOnDelete();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['branch_id', 'status']);
                $table->index(['created_by', 'status']);
                $table->index('expires_at');
            });
        }

        // POS-SEC-04: Shipment audits
        if (!Schema::hasTable('shipment_audits')) {
            Schema::create('shipment_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
                $table->string('event_type'); // created, updated, discount_applied, label_reprinted, cancelled, status_changed, payment_received
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->text('reason')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();

                $table->index(['shipment_id', 'event_type']);
                $table->index('created_at');
            });
        }

        // POS-PAY-03: Accounting entries (GL ledger)
        if (!Schema::hasTable('accounting_entries')) {
            Schema::create('accounting_entries', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();
                $table->foreignId('shipment_id')->nullable()->constrained('shipments')->nullOnDelete();
                $table->string('account_code');
                $table->string('account_name')->nullable();
                $table->enum('entry_type', ['debit', 'credit']);
                $table->decimal('amount', 14, 2);
                $table->string('currency', 3)->default('UGX');
                $table->string('reference')->nullable();
                $table->text('description')->nullable();
                $table->date('posting_date');
                $table->enum('status', ['pending', 'posted', 'reversed'])->default('pending');
                $table->string('external_sync_id')->nullable();
                $table->timestamp('synced_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['account_code', 'posting_date']);
                $table->index(['shipment_id', 'status']);
                $table->index('status');
            });
        }

        // POS-SEC-03: Supervisor overrides
        if (!Schema::hasTable('supervisor_overrides')) {
            Schema::create('supervisor_overrides', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shipment_id')->nullable()->constrained('shipments')->cascadeOnDelete();
                $table->string('action_type'); // discount, cancel, backdate, reprint, price_override
                $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('reason');
                $table->json('request_data')->nullable();
                $table->json('approved_data')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['shipment_id', 'action_type']);
                $table->index(['requested_by', 'status']);
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_overrides');
        Schema::dropIfExists('accounting_entries');
        Schema::dropIfExists('shipment_audits');
        Schema::dropIfExists('shipment_drafts');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('customer_contract_items');
        Schema::dropIfExists('customer_contracts');
        Schema::dropIfExists('tariffs');
        Schema::dropIfExists('service_constraints');
        Schema::dropIfExists('route_capabilities');
        Schema::dropIfExists('route_templates');
    }
};
