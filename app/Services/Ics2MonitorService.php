<?php

namespace App\Services;

use App\Models\Ics2Filing;

class Ics2MonitorService
{
    public function createDraft(array $data): Ics2Filing
    {
        $data['status'] = $data['status'] ?? 'draft';

        return Ics2Filing::create($data);
    }
}
