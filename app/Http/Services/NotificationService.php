<?php

namespace App\Http\Services;

use App\Models\Backend\Notification;

class NotificationService
{
    public function create(array $data)
    {
        return Notification::create($data);
    }
}
