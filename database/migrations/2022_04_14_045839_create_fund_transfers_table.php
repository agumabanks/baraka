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
        Schema::create('fund_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_account')->nullable()->constrained('accounts')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('to_account')->nullable()->constrained('accounts')->onUpdate('cascade')->onDelete('cascade');
            $table->decimal('amount',16,2)->nullable();
            $table->date('date')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('from_account');
            $table->index('to_account');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fund_transfers');
    }
};
