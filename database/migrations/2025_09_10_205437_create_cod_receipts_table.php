<?php

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
        Schema::create('cod_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('method'); // CASH, CARD, BANK_TRANSFER
            $table->string('receipt_image_path')->nullable();
            $table->foreignId('collected_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('collected_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('shipment_id');
            $table->index('collected_by');
            $table->index('collected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cod_receipts');
    }
};
