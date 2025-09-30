<?php

namespace Database\Seeders;

use App\Models\Backend\NotificationSettings;
use Illuminate\Database\Seeder;

class NotificationSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $notification = new NotificationSettings;
        $notification->fcm_secret_key = '';
        $notification->fcm_topic = '';
        $notification->save();
    }
}
