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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // Basic Information
            $table->string('customer_code')->unique();
            $table->string('company_name')->nullable();
            $table->string('contact_person');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('fax')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Saudi Arabia');

            // Business Information
            $table->string('tax_id')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('industry')->nullable();
            $table->string('company_size')->nullable(); // Small, Medium, Large, Enterprise
            $table->decimal('annual_revenue', 15, 2)->nullable();

            // Credit & Financial Information
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->string('payment_terms')->default('net_30'); // net_15, net_30, net_60, cod
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->string('currency', 3)->default('SAR');

            // Segmentation & Categorization
            $table->string('customer_type')->default('regular'); // vip, regular, inactive, prospect
            $table->string('segment')->nullable(); // High-Value, Standard, Low-Value
            $table->string('source')->nullable(); // Referral, Website, Sales, Marketing
            $table->integer('priority_level')->default(3); // 1=High, 2=Medium, 3=Low

            // Communication Preferences
            $table->json('communication_channels')->nullable(); // email, sms, whatsapp, phone
            $table->json('notification_preferences')->nullable();
            $table->string('preferred_language')->default('ar');

            // Relationship Management
            $table->foreignId('account_manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('primary_branch_id')->nullable()->constrained('unified_branches')->onDelete('set null');
            $table->foreignId('sales_rep_id')->nullable()->constrained('users')->onDelete('set null');

            // Status & Lifecycle
            $table->string('status')->default('active'); // active, inactive, suspended, blacklisted
            $table->timestamp('last_contact_date')->nullable();
            $table->timestamp('last_shipment_date')->nullable();
            $table->timestamp('customer_since');
            $table->text('notes')->nullable();

            // Analytics & Tracking
            $table->integer('total_shipments')->default(0);
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->decimal('average_order_value', 10, 2)->default(0);
            $table->integer('complaints_count')->default(0);
            $table->decimal('satisfaction_score', 3, 2)->nullable(); // 1.00 - 5.00

            // Compliance & Legal
            $table->boolean('kyc_verified')->default(false);
            $table->timestamp('kyc_verified_at')->nullable();
            $table->json('compliance_flags')->nullable();

            // Indexes for performance
            $table->index(['status', 'customer_type']);
            $table->index(['account_manager_id']);
            $table->index(['primary_branch_id']);
            $table->index(['last_shipment_date']);
            $table->index(['total_spent']);
            $table->index(['customer_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
