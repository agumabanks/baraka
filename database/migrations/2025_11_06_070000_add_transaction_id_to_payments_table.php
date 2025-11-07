<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payments', 'transaction_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('transaction_id')->nullable()->after('status');
                $table->index('transaction_id');
            });
        }

        if (Schema::hasColumn('payments', 'transaction_reference') && Schema::hasColumn('payments', 'transaction_id')) {
            DB::table('payments')
                ->whereNull('transaction_id')
                ->whereNotNull('transaction_reference')
                ->update([
                    'transaction_id' => DB::raw('transaction_reference'),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payments', 'transaction_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('payments_transaction_id_index');
            });

            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('transaction_id');
            });
        }
    }
};
