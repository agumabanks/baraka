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
        Schema::create('delivery_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('deliverycategories')->onUpdate('cascade')->onDelete('cascade');
            $table->tinyInteger('weight')->default(0);
            $table->decimal('same_day',16,2)->default(0);
            $table->decimal('next_day',16,2)->default(0);
            $table->decimal('sub_city',16,2)->default(0);
            $table->decimal('outside_city',16,2)->default(0);
            $table->integer('position')->nullable();
            $table->unsignedTinyInteger('status')->default(Status::ACTIVE)->comment(Status::ACTIVE.'='.trans('status.'.Status::ACTIVE).', ' .Status::INACTIVE.'='.trans('status.'.Status::INACTIVE));
            $table->timestamps();

            $table->index('category_id');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivery_charges');
    }
};
