<?php

use App\Enums\BooleanStatus;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('online_payments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('parcel_id')->nullable();
            $table->string('source')->nullable()->comment('parcel,wallet');
            $table->longText('payer_details')->nullable();
            $table->foreignId('merchant_id')->constrained('merchants')->onUpdate('cascade')->onDelete('cascade');
            $table->string('order_id')->nullable();
            $table->string('transaction_id')->nullable();
            $table->decimal('amount',22,2)->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedTinyInteger('is_paid')->default(BooleanStatus::NO);
            $table->string('status')->default('pending')->nullable()->comment('pending,processing,success,fail');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('online_payments');
    }
};
