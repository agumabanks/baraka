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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_head_id')->nullable()->constrained('account_heads')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('merchant_id')->nullable()->constrained('merchants')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('delivery_man_id')->nullable()->constrained('delivery_man')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('parcel_id')->nullable()->constrained('parcels')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onUpdate('cascade')->onDelete('cascade');
            $table->decimal('amount', 16, 2)->nullable();
            $table->date('date')->nullable();
            $table->foreignId('receipt')->nullable()->constrained('uploads')->onUpdate('cascade')->onDelete('cascade');
            $table->text('note')->nullable();
            $table->text('title')->nullable();
            $table->timestamps();

            $table->index('account_head_id');
            $table->index('merchant_id');
            $table->index('delivery_man_id');
            $table->index('user_id');
            $table->index('parcel_id');
            $table->index('account_id');
            $table->index('date');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('expenses');
    }
};
