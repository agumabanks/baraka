<?php

namespace App\Repositories\NotificationSettings;

use App\Models\Backend\NotificationSettings;
use Illuminate\Support\Facades\Artisan;

class NotificationSettingsRepository implements NotificationSettingsInterface
{
    public function all()
    {
        return NotificationSettings::find(1);
    }

    public function update($request)
    {
        $fcm_secret_key = str_replace(' ', '', $request->fcm_secret_key);
        $fcm_topic = str_replace(' ', '_', $request->fcm_topic);
        $notification = NotificationSettings::find(1);
        $notification->fcm_secret_key = $fcm_secret_key;
        $notification->fcm_topic = $fcm_topic;
        $notification->save();
        setEnv('FCM_SECRET_KEY', $fcm_secret_key);
        setEnv('FCM_TOPIC', $fcm_topic);
        Artisan::call('optimize:clear');

        return $notification;
    }
}
