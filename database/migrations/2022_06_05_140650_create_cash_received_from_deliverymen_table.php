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
        Schema::create('cash_received_from_deliverymen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('hub_id')->nullable()->constrained('hubs')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('delivery_man_id')->nullable()->constrained('delivery_man')->onUpdate('cascade')->onDelete('cascade');
            $table->decimal('amount', 16, 2)->nullable();
            $table->dateTime('date')->nullable();
            $table->foreignId('receipt')->nullable()->constrained('uploads')->onUpdate('cascade')->onDelete('cascade');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('hub_id');
            $table->index('account_id');
            $table->index('delivery_man_id');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cash_received_from_deliverymen');
    }
};
