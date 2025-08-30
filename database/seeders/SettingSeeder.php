<?php

namespace Database\Seeders;

use App\Models\Backend\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {


        //social login settings
        //facebook
        Setting::create(['key' => 'facebook_client_id',     'value' => 'facebook client id']);
        Setting::create(['key' => 'facebook_client_secret', 'value' => 'client secret']);
        Setting::create(['key' => 'facebook_status',        'value' => 1]);
        //google
        Setting::create(['key' => 'google_client_id',     'value' => 'client id']);
        Setting::create(['key' => 'google_client_secret', 'value' => 'client secret']);
        Setting::create(['key' => 'google_status',        'value' => 1]);

        //===== payment setup ===
        //stripe
        Setting::create(['key' => 'stripe_publishable_key',     'value' => 'publishable key']);
        Setting::create(['key' => 'stripe_secret_key',          'value' => 'secret key']);
        Setting::create(['key' => 'stripe_status',              'value' => 1]);

        //Razorpay
        Setting::create(['key' => 'razorpay_key',               'value' => '']);
        Setting::create(['key' => 'razorpay_secret',            'value' => '']);
        Setting::create(['key' => 'razorpay_status',            'value' => 1]);

        //sslcommerz
        Setting::create(['key' => 'sslcommerz_store_id',        'value' => 'store id']);
        Setting::create(['key' => 'sslcommerz_store_password',  'value' => 'store password']);
        Setting::create(['key' => 'sslcommerz_testmode',        'value' => 1]);
        Setting::create(['key' => 'sslcommerz_status',          'value' => 1]);

        //paypal
        Setting::create(['key' => 'paypal_client_id',              'value' => 'client id']);
        Setting::create(['key' => 'paypal_client_secret',          'value' => 'client secret']);
        Setting::create(['key' => 'paypal_mode',                   'value' => 'sendbox']);
        Setting::create(['key' => 'paypal_status',                 'value' => 1]);

        //skrill
        Setting::create(['key' => 'skrill_merchant_email',         'value' => 'demoqco@sun-fish.com']);
        Setting::create(['key' => 'skrill_status',                 'value' => 1]);


        // //bkash
        Setting::create(['key' => 'bkash_app_id',              'value' => 'application id']);
        Setting::create(['key' => 'bkash_app_secret',          'value' => 'application secret key']);
        Setting::create(['key' => 'bkash_username',            'value' => 'username']);
        Setting::create(['key' => 'bkash_password',            'value' => 'password']);
        Setting::create(['key' => 'bkash_test_mode',           'value' => 1]);
        Setting::create(['key' => 'bkash_status',              'value' => 1]);


        //aamar pay
        Setting::create(['key' => 'aamarpay_store_id',        'value' => 'aamarypay']);
        Setting::create(['key' => 'aamarpay_signature_key',   'value' => '28c78bb1f45112f5d40b956fe104645a']);
        Setting::create(['key' => 'aamarpay_sendbox_mode',    'value' => 1]);
        Setting::create(['key' => 'aamarpay_status',          'value' => 1]);
         

        //=====payment setup===





    }
}
