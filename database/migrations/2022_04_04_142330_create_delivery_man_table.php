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
        Schema::create('delivery_man', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedTinyInteger('status')->default(Status::ACTIVE)->comment(Status::ACTIVE.'='.trans('status.'.Status::ACTIVE).', '.Status::INACTIVE.'='.trans('status.'.Status::INACTIVE));
            $table->string('delivery_lat')->nullable();
            $table->string('delivery_long')->nullable();
            $table->decimal('delivery_charge', 13, 2)->default(0);
            $table->decimal('pickup_charge', 13, 2)->default(0);
            $table->decimal('return_charge', 13, 2)->default(0);
            $table->decimal('current_balance', 13, 2)->default(0);
            $table->decimal('opening_balance', 13, 2)->default(0);
            $table->foreignId('driving_license_image_id')->nullable()->constrained('uploads')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();

            $table->index('user_id');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivery_man');
    }
};
