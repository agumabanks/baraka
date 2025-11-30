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
            if (! Schema::hasColumn('invoices', 'invoice_number')) {
                $table->string('invoice_number')->nullable()->after('id');
            }

            if (! Schema::hasColumn('invoices', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('customer_id');
            }

            if (! Schema::hasColumn('invoices', 'metadata')) {
                $table->json('metadata')->nullable()->after('parcels_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'invoice_number')) {
                $table->dropColumn('invoice_number');
            }
            if (Schema::hasColumn('invoices', 'branch_id')) {
                $table->dropColumn('branch_id');
            }
            if (Schema::hasColumn('invoices', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }
};
