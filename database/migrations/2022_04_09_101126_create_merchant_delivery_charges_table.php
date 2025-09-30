<?php

use App\Enums\Status;
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
        Schema::create('merchant_delivery_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->nullable()->constrained('merchants')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('delivery_charge_id')->nullable()->constrained('delivery_charges')->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('weight')->nullable();
            $table->unsignedTinyInteger('category_id')->nullable();
            $table->decimal('same_day', 16, 2)->nullable();
            $table->decimal('next_day', 16, 2)->nullable();
            $table->decimal('sub_city', 16, 2)->nullable();
            $table->decimal('outside_city', 16, 2)->nullable();
            $table->unsignedTinyInteger('status')->default(Status::ACTIVE)->comment(Status::ACTIVE.'='.trans('status.'.Status::ACTIVE).', '.Status::INACTIVE.'='.trans('status.'.Status::INACTIVE));
            $table->timestamps();

            $table->index('merchant_id');
            $table->index('delivery_charge_id');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_delivery_charges');
    }
};
