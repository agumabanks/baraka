<?php

namespace App\Services;

use App\Models\DpsScreening;

class DpsScreeningService
{
    public function run(string $screenedType, int $screenedId, string $query): DpsScreening
    {
        // Stub: pretend to call CSL API and return clear
        $payload = [
            'query' => $query,
            'results' => [],
            'provider' => 'US_CSL',
        ];

        return DpsScreening::create([
            'screened_type' => $screenedType,
            'screened_id' => $screenedId,
            'query' => $query,
            'response_json' => $payload,
            'result' => 'clear',
            'list_name' => 'CSL',
            'match_score' => null,
            'screened_at' => now(),
        ]);
    }
}

