<?php

namespace Database\Seeders;

use App\Models\Backend\Merchantpanel\PaymentAccount;
use App\Models\Backend\Setting;
use Illuminate\Database\Seeder;

class PaymentAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $payment = new PaymentAccount;
        $payment->merchant_id = 1;
        $payment->payment_method = 'bank';
        $payment->bank_name = 'NRB Commercial Bank Ltd.';
        $payment->holder_name = 'Marchant';
        $payment->account_no = 123456;
        $payment->branch_name = 'Dhaka branch';
        $payment->routing_no = 123456;
        $payment->status = 1;

        $payment->save();

        $company = ['Bkash', 'Nogod', 'Rocket'];
        foreach ($company as $key => $value) {
            $payments = new PaymentAccount;
            $payments->merchant_id = 1;
            $payments->payment_method = 'mobile';
            $payments->holder_name = 'Marchant';
            $payments->mobile_company = $value;
            $payments->mobile_no = '01300000000';
            $payments->account_type = 'Personal';
            $payments->status = 1;
            $payments->save();
        }

        // paymob
        Setting::create(['key' => 'paymob_iframe_id',              'value' => '66802']);
        Setting::create(['key' => 'paymob_integration_id',         'value' => '751633']);
        Setting::create(['key' => 'paymob_hmac',                   'value' => 'E980FF4BE16F9C7BD2DBE65A7FFAA667']);
        Setting::create(['key' => 'paymob_api_key',                'value' => 'ZXlKMGVYQWlPaUpLVjFRaUxDSmhiR2NpT2lKSVV6VXhNaUo5LmV5SndjbTltYVd4bFgzQnJJam95TWpjMU9Dd2libUZ0WlNJNkltbHVhWFJwWVd3aUxDSmpiR0Z6Y3lJNklrMWxjbU5vWVc1MEluMC5xQmpMUmRfNDRyVmZnUjQ1QjZCVWVqaFBzUHdhbDdZX3AzTkg1MG1iV0o1UWphV0NwVGRXbUpzMUJuclI0VkRyYU9qZ09wbnNFUmp2aE1fUmZNV2VIZw==']);
        Setting::create(['key' => 'callback_url',                  'value' => 'https:\/\/shippidex.com\/merchant\/paymob\/callback']);
        Setting::create(['key' => 'paymob_mode',                   'value' => 'live']);
        Setting::create(['key' => 'paymob_status',                 'value' => 1]);

    }
}
