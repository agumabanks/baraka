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
        Schema::create('parcel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->nullable()->constrained('merchants')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('hub_id')->nullable()->constrained('hubs')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('delivery_man_id')->nullable()->constrained('delivery_man')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('parcel_id')->nullable()->constrained('parcels')->onUpdate('cascade')->onDelete('cascade');
            $table->longtext('pickup_address')->nullable();
            $table->string('pickup_phone')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->longtext('customer_address')->nullable();
            $table->string('invoice_no')->nullable();
            $table->decimal('cash_collection',13,2)->nullable();
            $table->decimal('selling_price',13,2)->nullable();
            $table->decimal('total_delivery_amount',13,2)->nullable();
            $table->decimal('current_payable',13,2)->nullable();
            $table->longtext('note')->nullable();
            $table->timestamps();

            $table->index('merchant_id');
            $table->index('hub_id');
            $table->index('delivery_man_id');
            $table->index('parcel_id');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parcel_logs');
    }
};
