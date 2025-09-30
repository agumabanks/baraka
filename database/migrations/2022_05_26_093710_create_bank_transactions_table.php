<?php

use App\Enums\AccountHeads;
use App\Enums\UserType;
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
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('user_type')->default(UserType::ADMIN)->comment(UserType::ADMIN.'='.trans('userType.'.UserType::ADMIN).','.UserType::HUB.'='.trans('userType.'.UserType::HUB))->nullable();
            $table->foreignId('hub_id')->nullable()->constrained('hubs')->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('expense_id')->nullable();
            $table->foreignId('fund_transfer_id')->nullable()->constrained('fund_transfers')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('accounts')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedTinyInteger('type')->comment('income='.AccountHeads::INCOME.', expense='.AccountHeads::EXPENSE)->nullable();
            $table->decimal('amount', 16, 2)->nullable();
            $table->date('date')->nullable();
            $table->longText('note')->nullable();
            $table->string('cash_received_dvry')->nullable();
            $table->foreignId('income_id')->nullable()->constrained('incomes')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();

            $table->index('user_type');
            $table->index('hub_id');
            $table->index('expense_id');
            $table->index('fund_transfer_id');
            $table->index('account_id');
            $table->index('type');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bank_transactions');
    }
};
