<?php

namespace App\Repositories\GeneralSettings;

use App\Models\Backend\GeneralSettings;

interface GeneralSettingsInterface
{
    public function all();

    public function update($request);

    public function preferences(GeneralSettings $settings): array;
}
