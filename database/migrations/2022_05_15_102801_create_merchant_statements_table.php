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
        Schema::create('merchant_statements', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('expense_id')->nullable();
            $table->foreignId('parcel_id')->nullable()->constrained('parcels')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('merchant_id')->nullable()->constrained('merchants')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('delivery_man_id')->nullable()->constrained('delivery_man')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedTinyInteger('type')->comment('income=1,expense=2')->nullable();
            $table->decimal('amount',16, 2)->nullable();
            $table->date('date')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('expense_id');
            $table->index('parcel_id');
            $table->index('merchant_id');
            $table->index('delivery_man_id');
            $table->index('type');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_statements');
    }
};
