<?php

use App\Enums\InvoiceStatus;
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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->onUpdate('cascade')->onDelete('cascade');
            $table->string('invoice_id')->unique()->nullable();
            $table->string('invoice_date')->nullable();
            $table->decimal('total_charge', 16, 2)->nullable();
            $table->decimal('cash_collection', 16, 2)->nullable();
            $table->decimal('current_payable', 16, 2)->nullable();
            $table->longText('parcels_id')->nullable();
            $table->unsignedTinyInteger('status')->default(InvoiceStatus::PROCESSING)->comment(
                ' Unpaid      = '.InvoiceStatus::UNPAID.
                ', Processing  = '.InvoiceStatus::PROCESSING.
                ', Paid        = '.InvoiceStatus::PAID,
            );
            $table->bigInteger('payment_id')->nullable();
            $table->timestamps();

            $table->index('merchant_id');
            $table->index('status');

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
};
