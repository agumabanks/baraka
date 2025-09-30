<?php

namespace App\Repositories\GoogleMapSettings;

use App\Models\Backend\GoogleMapSetting;

class GoogleMapSettingsRepository implements GoogleMapSettingsInterface
{
    public function all()
    {
        return GoogleMapSetting::find(1);
    }

    public function update($request)
    {
        $map_key = str_replace(' ', '', $request->map_key);
        $settings = GoogleMapSetting::find(1);
        if ($settings) {
            $settings->map_key = $map_key;
            $settings->save();
        } else {
            $settings = new GoogleMapSetting;
            $settings->map_key = $map_key;
            $settings->save();
        }

        return $settings;
    }
}
