<?php

namespace Database\Seeders;

use App\Enums\BranchStatus;
use App\Models\Backend\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

class BranchSeeder extends Seeder
{
    protected ?LoggerInterface $logger = null;

    public function run(): void
    {
        $definitions = $this->definitions();
        $this->createOrUpdateBranches($definitions);

        $this->logger()->info('Branch seeding completed', [
            'total_branches' => Branch::count(),
        ]);
    }

    /**
     * Exposes the normalized branch definitions so commands/tests can inspect them.
     */
    public function definitions(): array
    {
        $configured = config('seeders.branches', []);

        if (!empty($configured) && is_array($configured)) {
            return $configured;
        }

        return $this->defaultBranchDefinitions();
    }

    private function defaultBranchDefinitions(): array
    {
        return [
            [
                'code' => 'HUB-DUBAI',
                'name' => 'Dubai Main Hub',
                'type' => 'HUB',
                'address' => 'Dubai International City',
                'is_hub' => true,
                'status' => BranchStatus::ACTIVE->value,
            ],
            [
                'code' => 'HUB-ABU-DHABI',
                'name' => 'Abu Dhabi Hub',
                'type' => 'HUB',
                'address' => 'Abu Dhabi Industrial Zone',
                'is_hub' => true,
                'status' => BranchStatus::ACTIVE->value,
            ],
            [
                'code' => 'REG-DUBAI-NORTH',
                'name' => 'Dubai North Regional',
                'type' => 'REGIONAL',
                'address' => 'Dubai Silicon Oasis',
                'is_hub' => false,
                'parent_code' => 'HUB-DUBAI',
                'status' => BranchStatus::ACTIVE->value,
            ],
            [
                'code' => 'REG-DUBAI-SOUTH',
                'name' => 'Dubai South Regional',
                'type' => 'REGIONAL',
                'address' => 'Dubai South Logistics City',
                'is_hub' => false,
                'parent_code' => 'HUB-DUBAI',
                'status' => BranchStatus::ACTIVE->value,
            ],
            [
                'code' => 'LOC-DUBAI-DIPS',
                'name' => 'Dubai DIPS Local',
                'type' => 'LOCAL',
                'address' => 'Dubai Investment Park',
                'is_hub' => false,
                'parent_code' => 'REG-DUBAI-NORTH',
                'status' => BranchStatus::ACTIVE->value,
            ],
        ];
    }

    private function createOrUpdateBranches(array $branches): void
    {
        $normalized = [];

        foreach ($branches as $branchData) {
            if (!isset($branchData['code'])) {
                $this->logger()->warning('Skipping branch without code', ['branch' => $branchData]);
                continue;
            }

            $normalized[] = [
                'attributes' => $this->normalizeBranchAttributes($branchData),
                'parent_code' => isset($branchData['parent_code'])
                    ? Str::upper($branchData['parent_code'])
                    : null,
            ];
        }

        $cache = [];

        foreach ($normalized as $entry) {
            $branch = Branch::updateOrCreate(
                ['code' => $entry['attributes']['code']],
                Arr::except($entry['attributes'], ['parent_code'])
            );

            $cache[$branch->code] = $branch;

            $this->logger()->info('Branch seeded', [
                'code' => $branch->code,
                'name' => $branch->name,
                'created' => $branch->wasRecentlyCreated,
            ]);
        }

        foreach ($normalized as $entry) {
            if (!$entry['parent_code']) {
                continue;
            }

            $child = $cache[$entry['attributes']['code']] ?? Branch::where('code', $entry['attributes']['code'])->first();
            $parent = $cache[$entry['parent_code']] ?? Branch::where('code', $entry['parent_code'])->first();

            if ($child && $parent && $child->parent_branch_id !== $parent->id) {
                $child->update(['parent_branch_id' => $parent->id]);
                $this->logger()->info('Branch parent linked', [
                    'child' => $child->code,
                    'parent' => $parent->code,
                ]);
            }
        }
    }

    private function normalizeBranchAttributes(array $branchData): array
    {
        $code = Str::upper($branchData['code']);
        $type = Str::upper($branchData['type'] ?? 'LOCAL');
        $status = $branchData['status'] ?? BranchStatus::ACTIVE->value;

        $statusEnum = match (true) {
            $status instanceof BranchStatus => $status,
            is_int($status) => BranchStatus::fromLegacy($status),
            default => BranchStatus::fromString((string) $status),
        };

        return [
            'code' => $code,
            'name' => $branchData['name'] ?? $code,
            'type' => $type,
            'address' => $branchData['address'] ?? 'Not Provided',
            'is_hub' => (bool) ($branchData['is_hub'] ?? $type === 'HUB'),
            'status' => $statusEnum->toLegacy(),
            'parent_branch_id' => $branchData['parent_branch_id'] ?? null,
            'latitude' => $branchData['latitude'] ?? null,
            'longitude' => $branchData['longitude'] ?? null,
            'operating_hours' => $branchData['operating_hours'] ?? null,
            'capabilities' => $branchData['capabilities'] ?? null,
            'metadata' => $branchData['metadata'] ?? null,
        ];
    }

    private function logger(): LoggerInterface
    {
        if ($this->logger === null) {
            $channel = config('seeders.logging.log_channel', 'stack');
            $this->logger = Log::channel($channel);
        }

        return $this->logger;
    }
}
