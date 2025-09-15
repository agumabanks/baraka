<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // sales: quotations
        if (!Schema::hasTable('quotations')) {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users');
            $table->foreignId('origin_branch_id')->nullable()->constrained('hubs');
            $table->string('destination_country', 2);
            $table->string('service_type');
            $table->unsignedInteger('pieces');
            $table->decimal('weight_kg', 8, 3);
            $table->unsignedInteger('volume_cm3')->nullable();
            $table->unsignedInteger('dim_factor')->default(5000);
            $table->decimal('base_charge', 12, 2)->default(0);
            $table->json('surcharges_json')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->char('currency', 3)->default('USD');
            $table->enum('status', ['draft','sent','accepted','expired'])->default('draft');
            $table->date('valid_until')->nullable();
            $table->string('pdf_path')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
        }

        // sales: contracts
        if (!Schema::hasTable('contracts')) {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('rate_card_id')->nullable()->constrained('rate_cards');
            $table->json('sla_json')->nullable();
            $table->enum('status', ['active','suspended','ended'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        }

        // sales: address books
        if (!Schema::hasTable('address_books')) {
        Schema::create('address_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users');
            $table->enum('type', ['shipper','consignee','payer']);
            $table->string('name');
            $table->string('phone_e164');
            $table->string('email')->nullable();
            $table->string('country', 2);
            $table->string('city');
            $table->text('address_line');
            $table->string('tax_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        }

        // compliance: kyc_records
        if (!Schema::hasTable('kyc_records')) {
        Schema::create('kyc_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users');
            $table->enum('status', ['pending','approved','rejected'])->default('pending');
            $table->json('documents')->nullable();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users');
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        }

        // compliance: dps_screenings
        if (!Schema::hasTable('dps_screenings')) {
        Schema::create('dps_screenings', function (Blueprint $table) {
            $table->id();
            $table->string('screened_type');
            $table->unsignedBigInteger('screened_id');
            $table->string('query');
            $table->json('response_json')->nullable();
            $table->enum('result', ['clear','hit']);
            $table->string('list_name')->nullable();
            $table->decimal('match_score', 5, 2)->nullable();
            $table->dateTime('screened_at');
            $table->timestamps();
        });
        }

        // compliance: dangerous_goods
        if (!Schema::hasTable('dangerous_goods')) {
        Schema::create('dangerous_goods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->nullable()->constrained('shipments');
            $table->string('un_number', 6);
            $table->string('dg_class', 5);
            $table->string('packing_group', 2)->nullable();
            $table->string('proper_shipping_name');
            $table->decimal('net_qty', 8, 3)->nullable();
            $table->string('pkg_type')->nullable();
            $table->enum('status', ['declared','held','rejected'])->default('declared');
            $table->json('docs')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        }

        // compliance: ics2_filings
        if (!Schema::hasTable('ics2_filings')) {
        Schema::create('ics2_filings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->nullable()->constrained('shipments');
            $table->foreignId('transport_leg_id')->nullable()->constrained('transport_legs');
            $table->enum('mode', ['air','road','sea','rail']);
            $table->string('ens_ref')->nullable();
            $table->enum('status', ['draft','lodged','accepted','rejected'])->default('draft');
            $table->dateTime('lodged_at')->nullable();
            $table->json('response_json')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        }

        // linehaul: awb_stocks
        if (!Schema::hasTable('awb_stocks')) {
        Schema::create('awb_stocks', function (Blueprint $table) {
            $table->id();
            $table->char('carrier_code', 2);
            $table->char('iata_prefix', 3);
            $table->unsignedBigInteger('range_start');
            $table->unsignedBigInteger('range_end');
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('voided_count')->default(0);
            $table->foreignId('hub_id')->nullable()->constrained('hubs');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users');
            $table->enum('status', ['active','exhausted','voided'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        }

        // linehaul: manifests
        if (!Schema::hasTable('manifests')) {
        Schema::create('manifests', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->enum('mode', ['air','road']);
            $table->foreignId('carrier_id')->nullable()->constrained('hubs');
            $table->dateTime('departure_at');
            $table->dateTime('arrival_at')->nullable();
            $table->foreignId('origin_branch_id')->constrained('hubs');
            $table->foreignId('destination_branch_id')->nullable()->constrained('hubs');
            $table->json('legs_json')->nullable();
            $table->json('bags_json')->nullable();
            $table->enum('status', ['open','closed','departed','arrived'])->default('open');
            $table->json('docs')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        }

        // linehaul: ecmrs
        if (!Schema::hasTable('ecmrs')) {
        Schema::create('ecmrs', function (Blueprint $table) {
            $table->id();
            $table->string('cmr_number');
            $table->string('road_carrier');
            $table->foreignId('origin_branch_id')->constrained('hubs');
            $table->foreignId('destination_branch_id')->constrained('hubs');
            $table->string('doc_path')->nullable();
            $table->enum('status', ['draft','issued','delivered'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });
        }

        // hub ops: sortation_bins
        if (!Schema::hasTable('sortation_bins')) {
        Schema::create('sortation_bins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('hubs');
            $table->string('code');
            $table->string('lane')->nullable();
            $table->enum('status', ['active','blocked'])->default('active');
            $table->timestamps();
        });
        }

        // hub ops: wh_locations
        if (!Schema::hasTable('wh_locations')) {
        Schema::create('wh_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('hubs');
            $table->string('code');
            $table->enum('type', ['shelf','floor','cage','bin']);
            $table->unsignedInteger('capacity')->nullable();
            $table->enum('status', ['active','blocked'])->default('active');
            $table->timestamps();
        });
        }

        // customer care: return_orders
        if (!Schema::hasTable('return_orders')) {
        Schema::create('return_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->string('reason_code');
            $table->enum('initiated_by', ['customer','ops']);
            $table->enum('status', ['initiated','in_transit','received','completed','cancelled'])->default('initiated');
            $table->string('rto_label_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        }

        // customer care: claims
        if (!Schema::hasTable('claims')) {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->enum('type', ['loss','damage','delay']);
            $table->text('description');
            $table->decimal('amount_claimed', 12, 2);
            $table->json('evidence')->nullable();
            $table->enum('status', ['open','approved','rejected','paid'])->default('open');
            $table->decimal('settled_amount', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        }

        // finance & rating: surcharge_rules
        if (!Schema::hasTable('surcharge_rules')) {
        Schema::create('surcharge_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('trigger', ['fuel','security','remote_area','oversize','weekend','dg','re_attempt','custom']);
            $table->enum('rate_type', ['flat','percent']);
            $table->decimal('amount', 10, 4);
            $table->char('currency', 3)->nullable();
            $table->json('applies_to')->nullable();
            $table->date('active_from');
            $table->date('active_to')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        }

        // finance & rating: cash_office_days
        if (!Schema::hasTable('cash_office_days')) {
        Schema::create('cash_office_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('hubs');
            $table->date('business_date');
            $table->decimal('cod_collected', 12, 2)->default(0);
            $table->decimal('cash_on_hand', 12, 2)->default(0);
            $table->decimal('banked_amount', 12, 2)->default(0);
            $table->decimal('variance', 12, 2)->default(0);
            $table->foreignId('submitted_by_id')->constrained('users');
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();
        });
        }

        // finance & rating: fx_rates
        if (!Schema::hasTable('fx_rates')) {
        Schema::create('fx_rates', function (Blueprint $table) {
            $table->id();
            $table->char('base', 3);
            $table->char('counter', 3);
            $table->decimal('rate', 16, 8);
            $table->string('provider');
            $table->dateTime('effective_at');
            $table->timestamps();
            $table->unique(['base','counter','effective_at']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fx_rates');
        Schema::dropIfExists('cash_office_days');
        Schema::dropIfExists('surcharge_rules');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('return_orders');
        Schema::dropIfExists('wh_locations');
        Schema::dropIfExists('sortation_bins');
        Schema::dropIfExists('ecmrs');
        Schema::dropIfExists('manifests');
        Schema::dropIfExists('awb_stocks');
        Schema::dropIfExists('ics2_filings');
        Schema::dropIfExists('dangerous_goods');
        Schema::dropIfExists('dps_screenings');
        Schema::dropIfExists('kyc_records');
        Schema::dropIfExists('address_books');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('quotations');
    }
};
