<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            foreach ([
                'shipment_id' => fn () => $table->foreignId('shipment_id')->nullable()->after('id'),
                'customer_id' => fn () => $table->foreignId('customer_id')->nullable(),
                'subtotal' => fn () => $table->decimal('subtotal', 10, 2)->nullable(),
                'tax_amount' => fn () => $table->decimal('tax_amount', 10, 2)->nullable(),
                'total_amount' => fn () => $table->decimal('total_amount', 10, 2)->nullable(),
                'currency' => fn () => $table->string('currency', 3)->nullable(),
                'status' => fn () => $table->string('status')->nullable(),
                'due_date' => fn () => $table->timestamp('due_date')->nullable(),
                'paid_at' => fn () => $table->timestamp('paid_at')->nullable(),
                'notes' => fn () => $table->text('notes')->nullable(),
                'metadata' => fn () => $table->json('metadata')->nullable(),
                'branch_id' => fn () => $table->foreignId('branch_id')->nullable(),
            ] as $column => $callback) {
                if (! Schema::hasColumn('invoices', $column)) {
                    $callback();
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            foreach (['shipment_id', 'customer_id', 'subtotal', 'tax_amount', 'total_amount', 'currency', 'status', 'due_date', 'paid_at', 'notes', 'metadata', 'branch_id'] as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
