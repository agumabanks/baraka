<?php

use App\Enums\Wallet\WalletPaymentMethod;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->string('source')->nullable();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('merchant_id')->constrained('merchants')->onUpdate('cascade')->onDelete('cascade');
            $table->string('transaction_id')->nullable();
            $table->decimal('amount',22,2)->nullable(); 
            $table->unsignedTinyInteger('type')->default(WalletType::INCOME)->comment(WalletType::INCOME.'= Income,'.WalletType::EXPENSE.'= Expense');
            $table->unsignedTinyInteger('payment_method')->default(WalletPaymentMethod::OFFLINE)->comment(WalletPaymentMethod::OFFLINE.' = Offline '); 
            $table->unsignedTinyInteger('status')->default(WalletStatus::PENDING)->comment(WalletStatus::PENDING.' = Pending , '.WalletStatus::APPROVED.'= Approved,'.WalletStatus::REJECTED.'= Reject'); 
            $table->timestamps();
 
            $table->index('source');
            $table->index('user_id');
            $table->index('merchant_id');
            $table->index('type');
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
        Schema::dropIfExists('wallets');
    }
};
