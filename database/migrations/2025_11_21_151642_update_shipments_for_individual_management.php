<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update shipments table
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'waybill_number')) {
                $table->string('waybill_number')->nullable()->unique()->after('tracking_number');
            }
            if (!Schema::hasColumn('shipments', 'service_level')) {
                $table->string('service_level')->default('standard')->after('type'); // express, economy, same_day
            }
            if (!Schema::hasColumn('shipments', 'incoterms')) {
                $table->string('incoterms')->nullable()->after('service_level'); // DDP, DDU, EXW
            }
            if (!Schema::hasColumn('shipments', 'payer_type')) {
                $table->enum('payer_type', ['sender', 'receiver', 'third_party'])->default('sender')->after('incoterms');
            }
            if (!Schema::hasColumn('shipments', 'special_instructions')) {
                $table->text('special_instructions')->nullable()->after('payer_type');
            }
            if (!Schema::hasColumn('shipments', 'declared_value')) {
                $table->decimal('declared_value', 10, 2)->nullable()->after('special_instructions');
            }
            if (!Schema::hasColumn('shipments', 'insurance_amount')) {
                $table->decimal('insurance_amount', 10, 2)->nullable()->after('declared_value');
            }
            if (!Schema::hasColumn('shipments', 'customs_value')) {
                $table->decimal('customs_value', 10, 2)->nullable()->after('insurance_amount');
            }
            if (!Schema::hasColumn('shipments', 'currency')) {
                $table->string('currency', 3)->default('USD')->after('customs_value');
            }
            if (!Schema::hasColumn('shipments', 'chargeable_weight_kg')) {
                $column = $table->decimal('chargeable_weight_kg', 8, 2)->default(0);
                if (Schema::hasColumn('shipments', 'weight')) {
                    $column->after('weight');
                } elseif (Schema::hasColumn('shipments', 'price_amount')) {
                    $column->after('price_amount');
                }
            }
            // Ensure volume column exists or add it if missing (some existing schemas might have it)
            if (!Schema::hasColumn('shipments', 'volume_cbm')) {
                $column = $table->decimal('volume_cbm', 8, 4)->default(0);
                if (Schema::hasColumn('shipments', 'chargeable_weight_kg')) {
                    $column->after('chargeable_weight_kg');
                }
            }
        });

        // Create parcels table (for multi-piece shipments)
        if (! Schema::hasTable('parcels')) {
            Schema::create('parcels', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shipment_id');
                $table->string('barcode')->unique();
                $table->decimal('weight_kg', 8, 2);
                $table->decimal('length_cm', 8, 2);
                $table->decimal('width_cm', 8, 2);
                $table->decimal('height_cm', 8, 2);
                $table->decimal('volume_cbm', 8, 4);
                $table->string('description')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('shipment_id')->references('id')->on('shipments')->onDelete('cascade');
                $table->index('barcode');
            });
        }

        // Create shipment_events table (granular tracking)
        if (! Schema::hasTable('shipment_events')) {
            Schema::create('shipment_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shipment_id');
                $table->string('event_code'); // PICKED_UP, IN_TRANSIT, OUT_FOR_DELIVERY, DELIVERED, EXCEPTION
                $table->string('location');
                $table->text('description')->nullable();
                $table->timestamp('occurred_at');
                $table->unsignedBigInteger('user_id')->nullable(); // Who scanned/updated it
                $table->json('metadata')->nullable(); // Extra data like GPS coords, signature image URL
                $table->timestamps();

                $table->foreign('shipment_id')->references('id')->on('shipments')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                $table->index(['shipment_id', 'occurred_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipment_events');
        Schema::dropIfExists('parcels');

        Schema::table('shipments', function (Blueprint $table) {
            $columns = [
                'waybill_number', 'service_level', 'incoterms', 'payer_type', 
                'special_instructions', 'declared_value', 'insurance_amount', 
                'customs_value', 'currency', 'chargeable_weight_kg', 'volume_cbm'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('shipments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
