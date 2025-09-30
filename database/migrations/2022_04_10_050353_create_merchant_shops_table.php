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
        Schema::create('merchant_shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->nullable()->constrained('merchants')->onUpdate('cascade')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('contact_no')->nullable();
            $table->string('address')->nullable();
            $table->string('merchant_lat')->nullable();
            $table->string('merchant_long')->nullable();
            $table->unsignedTinyInteger('status')->default(Status::ACTIVE)->comment(Status::ACTIVE.'='.trans('status.'.Status::ACTIVE).', '.Status::INACTIVE.'='.trans('status.'.Status::INACTIVE));
            $table->unsignedTinyInteger('default_shop')->default(Status::INACTIVE)->comment(Status::ACTIVE.'='.trans('status.'.Status::ACTIVE).', '.Status::INACTIVE.'='.trans('status.'.Status::INACTIVE));
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
        Schema::dropIfExists('merchant_shops');
    }
};
