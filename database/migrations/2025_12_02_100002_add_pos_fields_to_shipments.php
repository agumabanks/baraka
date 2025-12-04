<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // POS-BR-05: Contents classification
            if (!Schema::hasColumn('shipments', 'content_type')) {
                $table->enum('content_type', ['document', 'parcel', 'battery', 'liquid', 'hazmat', 'other'])->nullable()->after('metadata');
            }
            if (!Schema::hasColumn('shipments', 'un_number')) {
                $table->string('un_number', 10)->nullable()->after('content_type');
            }
            if (!Schema::hasColumn('shipments', 'hazmat_class')) {
                $table->string('hazmat_class', 20)->nullable()->after('un_number');
            }
            if (!Schema::hasColumn('shipments', 'packaging_group')) {
                $table->string('packaging_group', 10)->nullable()->after('hazmat_class');
            }

            // POS-RATE-05: Rate table version tracking
            if (!Schema::hasColumn('shipments', 'rate_table_version')) {
                $table->string('rate_table_version', 50)->nullable()->after('packaging_group');
            }

            // POS-REL-03: Label print tracking
            if (!Schema::hasColumn('shipments', 'last_label_printed_at')) {
                $table->timestamp('last_label_printed_at')->nullable()->after('rate_table_version');
            }
            if (!Schema::hasColumn('shipments', 'label_print_count')) {
                $table->unsignedSmallInteger('label_print_count')->default(0)->after('last_label_printed_at');
            }

            // POS-PAY-01: Payer tracking
            if (!Schema::hasColumn('shipments', 'payer_type')) {
                $table->enum('payer_type', ['sender', 'receiver', 'third_party', 'account'])->default('sender')->after('label_print_count');
            }
            if (!Schema::hasColumn('shipments', 'payment_status')) {
                $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded'])->default('unpaid')->after('payer_type');
            }

            // POS-REL-01: Draft reference
            if (!Schema::hasColumn('shipments', 'draft_id')) {
                $table->uuid('draft_id')->nullable()->after('payment_status');
            }

            // POS pricing breakdown
            if (!Schema::hasColumn('shipments', 'base_rate')) {
                $table->decimal('base_rate', 12, 2)->nullable()->after('draft_id');
            }
            if (!Schema::hasColumn('shipments', 'weight_charge')) {
                $table->decimal('weight_charge', 12, 2)->nullable()->after('base_rate');
            }
            if (!Schema::hasColumn('shipments', 'surcharges_total')) {
                $table->decimal('surcharges_total', 12, 2)->nullable()->after('weight_charge');
            }
            if (!Schema::hasColumn('shipments', 'insurance_fee')) {
                $table->decimal('insurance_fee', 12, 2)->nullable()->after('surcharges_total');
            }
            if (!Schema::hasColumn('shipments', 'cod_fee')) {
                $table->decimal('cod_fee', 12, 2)->nullable()->after('insurance_fee');
            }
            if (!Schema::hasColumn('shipments', 'tax_amount')) {
                $table->decimal('tax_amount', 12, 2)->nullable()->after('cod_fee');
            }
            if (!Schema::hasColumn('shipments', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->nullable()->after('tax_amount');
            }
            if (!Schema::hasColumn('shipments', 'discount_reason')) {
                $table->string('discount_reason')->nullable()->after('discount_amount');
            }
            if (!Schema::hasColumn('shipments', 'discount_approved_by')) {
                $table->foreignId('discount_approved_by')->nullable()->after('discount_reason');
            }
        });

        // Add index for draft lookup
        Schema::table('shipments', function (Blueprint $table) {
            $table->index('draft_id');
            $table->index('payer_type');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['draft_id']);
            $table->dropIndex(['payer_type']);
            $table->dropIndex(['payment_status']);
        });

        Schema::table('shipments', function (Blueprint $table) {
            $columns = [
                'content_type', 'un_number', 'hazmat_class', 'packaging_group',
                'rate_table_version', 'last_label_printed_at', 'label_print_count',
                'payer_type', 'payment_status', 'draft_id',
                'base_rate', 'weight_charge', 'surcharges_total', 'insurance_fee',
                'cod_fee', 'tax_amount', 'discount_amount', 'discount_reason', 'discount_approved_by'
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('shipments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
