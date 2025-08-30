<?php

use App\Enums\PickupRequestType;
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
        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_type')->nullable()->comment('regular = '.PickupRequestType::REGULAR.',','express = '.PickupRequestType::EXPRESS);
            $table->foreignId('merchant_id')->constrained('merchants')->onUpdate('cascade')->onDelete('cascade');
            $table->text('address')->nullable();
            $table->longText('note')->nullable();
            //regular pickup requests
            $table->bigInteger('parcel_quantity')->default(0);
            //express pickup request
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('cod_amount',16,2)->default(0)->nullable();
            $table->string('invoice')->nullable();
            $table->bigInteger('weight')->default(0)->nullable();
            $table->unsignedTinyInteger('exchange')->default(0)->comment('yes = 1, no = 0'); 
            $table->timestamps();

            $table->index('merchant_id');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pickup_requests');
    }
};
