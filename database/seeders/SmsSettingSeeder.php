<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\Backend\SmsSetting;
use Illuminate\Database\Seeder;

class SmsSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // SMS settings
        // REVE SMS
        SmsSetting::create(['key' => 'reve_api_key',     'value' => 'Your API key']);
        SmsSetting::create(['key' => 'reve_secret_key', 'value' => 'Your secret key']);
        SmsSetting::create(['key' => 'reve_api_url',        'value' => 'http://smpp.ajuratech.com:7788/sendtext']);
        SmsSetting::create(['key' => 'reve_username',        'value' => '']);
        SmsSetting::create(['key' => 'reve_user_password',        'value' => '']);
        SmsSetting::create(['key' => 'reve_status',        'value' => Status::INACTIVE]);
        // Twilio SMS
        SmsSetting::create(['key' => 'twilio_sid',     'value' => '']);
        SmsSetting::create(['key' => 'twilio_token', 'value' => '']);
        SmsSetting::create(['key' => 'twilio_from', 'value' => '']);
        SmsSetting::create(['key' => 'twilio_status', 'value' => Status::INACTIVE]);

        // NEXMO SMS
        SmsSetting::create(['key' => 'nexmo_key',     'value' => '']);
        SmsSetting::create(['key' => 'nexmo_secret_key', 'value' => '']);
        SmsSetting::create(['key' => 'nexmo_status', 'value' => Status::INACTIVE]);
        // CLICK SEND SMS
        SmsSetting::create(['key' => 'click_send_username',     'value' => '']);
        SmsSetting::create(['key' => 'click_send_api_key', 'value' => '']);
        SmsSetting::create(['key' => 'click_send_status', 'value' => Status::INACTIVE]);

    }
}
