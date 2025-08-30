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
        Schema::table('assets', function (Blueprint $table) {
            $table->string('asset_type')->nullable()->after('name');
            $table->bigInteger('vehicle_id')->nullable()->after('asset_type');
            $table->date('purchase_date')->nullable()->after('amount');
            $table->integer('registration_documents')->nullable()->after('purchase_date');
            $table->date('registration_date')->nullable()->after('registration_documents');
            $table->date('registration_expiry_date')->nullable()->after('registration_date');
            $table->string('yearly_depreciation_value')->nullable()->after('registration_expiry_date');
            $table->date('insurance_registration')->nullable()->after('yearly_depreciation_value');
            $table->tinyInteger('insurance_status')->default(2)->after('insurance_registration');
            $table->integer('insurance_documents')->nullable()->after('insurance_status');
            $table->date('insurance_expiry_date')->nullable()->after('insurance_documents');
            $table->decimal('insurance_amount', 13, 2)->nullable()->after('insurance_expiry_date');
            $table->date('maintenance_schedule')->nullable()->after('insurance_amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'asset_type',
                'vehicle_id',
                'purchase_date',
                'registration_documents',
                'registration_date',
                'registration_expiry_date',
                'yearly_depreciation_value',
                'insurance_registration',
                'insurance_status',
                'insurance_documents',
                'insurance_expiry_date',
                'insurance_amount',
                'maintenance_schedule',
            ]);
        });
    }
};
