<?php

namespace Database\Seeders;

use App\Models\Backend\FundTransfer;
use Illuminate\Database\Seeder;

class FundTransferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $account = new FundTransfer;
        $account->from_account = 1;
        $account->to_account = 2;
        $account->amount = 00;
        $account->date = '2022-04-14';
        $account->description = 'Lorem ipsum, dolor sit amet consectetur adipisicing elit. Accusantium, ex.';
        $account->save();
    }
}
