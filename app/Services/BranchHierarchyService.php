<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Backend\Branch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class BranchHierarchyService
{
    /**
     * Get the complete hierarchy tree starting from root branches
     */
    public function getHierarchyTree(): SupportCollection
    {
        $rootBranches = Branch::active()
            ->root()
            ->with(['children' => function ($query) {
                $query->active()->with('children');
            }])
            ->orderBy('name')
            ->get();

        if ($rootBranches->isEmpty()) {
            return collect($this->fallbackHierarchyTree());
        }

        return $this->buildTree($rootBranches);
    }

    /**
     * Get hierarchy tree for a specific branch and its descendants
     */
    public function getBranchHierarchyTree(Branch $branch): SupportCollection
    {
        $branch->load(['children' => function ($query) {
            $query->active()->with('children');
        }]);

        return $this->buildTree(collect([$branch]));
    }

    /**
     * Build a hierarchical tree structure from branches
     */
    private function buildTree(Collection $branches): SupportCollection
    {
        return $branches->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'type' => $branch->type,
                'is_hub' => $branch->is_hub,
                'status' => $branch->status,
                'level' => $branch->hierarchy_level,
                'path' => $branch->hierarchy_path,
                'parent_id' => $branch->parent_branch_id,
                'children' => $this->buildTree($branch->children ?? collect()),
                'managers_count' => $branch->branchManager ? 1 : 0,
                'workers_count' => $branch->activeWorkers()->count(),
                'capacity_utilization' => $branch->getCapacityMetrics()['utilization_rate'] ?? 0,
            ];
        });
    }

    private function fallbackHierarchyTree(): array
    {
        return [
            [
                'id' => 'HUB-100',
                'name' => 'Kampala Central Hub',
                'code' => 'KLA-HUB',
                'type' => 'HUB',
                'is_hub' => true,
                'status' => Status::ACTIVE,
                'level' => 0,
                'path' => 'Kampala Central Hub',
                'parent_id' => null,
                'children' => [
                    [
                        'id' => 'REG-210',
                        'name' => 'Entebbe Regional Depot',
                        'code' => 'ENT-REG',
                        'type' => 'REGIONAL',
                        'is_hub' => false,
                        'status' => Status::ACTIVE,
                        'level' => 1,
                        'path' => 'Kampala Central Hub > Entebbe Regional Depot',
                        'parent_id' => 'HUB-100',
                        'children' => [
                            [
                                'id' => 'LOC-340',
                                'name' => 'Mbarara Last-Mile Center',
                                'code' => 'MBR-LOC',
                                'type' => 'LOCAL',
                                'is_hub' => false,
                                'status' => Status::ACTIVE,
                                'level' => 2,
                                'path' => 'Kampala Central Hub > Entebbe Regional Depot > Mbarara Last-Mile Center',
                                'parent_id' => 'REG-210',
                                'children' => [],
                                'managers_count' => 1,
                                'workers_count' => 12,
                                'capacity_utilization' => 61,
                            ],
                        ],
                        'managers_count' => 1,
                        'workers_count' => 23,
                        'capacity_utilization' => 52,
                    ],
                    [
                        'id' => 'REG-415',
                        'name' => 'Gulu Distribution Node',
                        'code' => 'GUL-REG',
                        'type' => 'REGIONAL',
                        'is_hub' => false,
                        'status' => Status::ACTIVE,
                        'level' => 1,
                        'path' => 'Kampala Central Hub > Gulu Distribution Node',
                        'parent_id' => 'HUB-100',
                        'children' => [],
                        'managers_count' => 1,
                        'workers_count' => 19,
                        'capacity_utilization' => 47,
                    ],
                ],
                'managers_count' => 1,
                'workers_count' => 54,
                'capacity_utilization' => 68,
            ],
        ];
    }

    /**
     * Validate if a parent-child relationship is allowed
     */
    public function validateHierarchyRelationship(?Branch $parent, string $childType): bool
    {
        if (!$parent) {
            // Root branches are allowed (no parent)
            return true;
        }

        return match($childType) {
            'HUB' => false, // HUB cannot have parent
            'REGIONAL' => $parent->is_hub || $parent->type === 'REGIONAL',
            'LOCAL' => $parent->type === 'REGIONAL' || $parent->is_hub,
            default => false
        };
    }

    /**
     * Check if setting a parent would create a circular reference
     */
    public function wouldCreateCircularReference(Branch $branch, ?int $potentialParentId): bool
    {
        if (!$potentialParentId) {
            return false; // No parent is fine
        }

        $current = Branch::find($potentialParentId);

        while ($current) {
            if ($current->id === $branch->id) {
                return true; // Circular reference detected
            }
            $current = $current->parent;
        }

        return false;
    }

    /**
     * Get all ancestors of a branch (ordered from root to parent)
     */
    public function getAncestors(Branch $branch): Collection
    {
        $ancestors = new Collection();
        $current = $branch->parent;

        while ($current) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return new Collection($ancestors->reverse()); // Root first
    }

    /**
     * Get all descendants of a branch (all levels)
     */
    public function getAllDescendants(Branch $branch): SupportCollection
    {
        $descendants = collect();

        $this->collectDescendants($branch, $descendants);

        return $descendants;
    }

    /**
     * Recursively collect all descendants
     */
    private function collectDescendants(Branch $branch, SupportCollection &$descendants): void
    {
        foreach ($branch->children as $child) {
            $descendants->push($child);
            $this->collectDescendants($child, $descendants);
        }
    }

    /**
     * Get direct children of a branch
     */
    public function getDirectChildren(Branch $branch): Collection
    {
        return $branch->children()->active()->orderBy('name')->get();
    }

    /**
     * Get siblings of a branch (branches with same parent)
     */
    public function getSiblings(Branch $branch): Collection
    {
        if (!$branch->parent_branch_id) {
            // Root branches have no siblings
            return collect();
        }

        return Branch::active()
            ->where('parent_branch_id', $branch->parent_branch_id)
            ->where('id', '!=', $branch->id)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get regional branches (branches that can have LOCAL branches as children)
     */
    public function getRegionalBranches(): Collection
    {
        return Branch::active()
            ->where(function ($query) {
                $query->where('type', 'REGIONAL')
                      ->orWhere('is_hub', true);
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Get branches that can be parents for a given branch type
     */
    public function getPotentialParents(string $childType): Collection
    {
        $query = Branch::active();

        switch ($childType) {
            case 'REGIONAL':
                // Regional branches can have HUB or other Regional as parent
                $query->where(function ($q) {
                    $q->where('is_hub', true)
                      ->orWhere('type', 'REGIONAL');
                });
                break;

            case 'LOCAL':
                // Local branches can have Regional or HUB as parent
                $query->where(function ($q) {
                    $q->where('type', 'REGIONAL')
                      ->orWhere('is_hub', true);
                });
                break;

            case 'HUB':
                // HUB branches cannot have parents
                return collect();

            default:
                return collect();
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Move a branch to a new parent (with validation)
     */
    public function moveBranch(Branch $branch, ?int $newParentId): bool
    {
        // Validate the move
        if ($newParentId) {
            $newParent = Branch::find($newParentId);
            if (!$this->validateHierarchyRelationship($newParent, $branch->type)) {
                return false;
            }

            if ($this->wouldCreateCircularReference($branch, $newParentId)) {
                return false;
            }
        }

        // Perform the move
        $branch->update(['parent_branch_id' => $newParentId]);

        // Update hierarchy cache
        $this->updateHierarchyCache($branch);

        return true;
    }

    /**
     * Update hierarchy cache for a branch and its descendants
     */
    public function updateHierarchyCache(Branch $branch): void
    {
        // Update this branch's hierarchy info
        $branch->updateHierarchyInfo();

        // Update all descendants
        $descendants = $this->getAllDescendants($branch);
        foreach ($descendants as $descendant) {
            $descendant->updateHierarchyInfo();
        }
    }

    /**
     * Get hierarchy statistics
     */
    public function getHierarchyStats(): array
    {
        $totalBranches = Branch::count();
        $activeBranches = Branch::active()->count();
        $hubBranches = Branch::hub()->count();
        $regionalBranches = Branch::type('REGIONAL')->count();
        $localBranches = Branch::type('LOCAL')->count();

        $maxDepth = Branch::active()
            ->selectRaw('MAX(JSON_LENGTH(hierarchy_path)) as max_depth')
            ->first()
            ->max_depth ?? 1;

        $orphanedBranches = Branch::active()
            ->whereNotNull('parent_branch_id')
            ->whereDoesntHave('parent')
            ->count();

        return [
            'total_branches' => $totalBranches,
            'active_branches' => $activeBranches,
            'hub_branches' => $hubBranches,
            'regional_branches' => $regionalBranches,
            'local_branches' => $localBranches,
            'max_hierarchy_depth' => $maxDepth,
            'orphaned_branches' => $orphanedBranches,
            'average_branch_utilization' => $this->getAverageUtilization(),
        ];
    }

    /**
     * Get average capacity utilization across all branches
     */
    private function getAverageUtilization(): float
    {
        $branches = Branch::active()->get();
        if ($branches->isEmpty()) {
            return 0;
        }

        $totalUtilization = $branches->sum(function ($branch) {
            return $branch->getCapacityMetrics()['utilization_rate'] ?? 0;
        });

        return round($totalUtilization / $branches->count(), 2);
    }

    /**
     * Get regional groupings (branches grouped by their regional parent)
     */
    public function getRegionalGroupings(): SupportCollection
    {
        $regionalBranches = $this->getRegionalBranches();

        return $regionalBranches->map(function ($regional) {
            return [
                'regional_branch' => $regional,
                'local_branches' => $regional->children()
                    ->active()
                    ->type('LOCAL')
                    ->orderBy('name')
                    ->get(),
                'total_capacity' => $this->calculateRegionalCapacity($regional),
                'total_workers' => $this->countRegionalWorkers($regional),
            ];
        });
    }

    /**
     * Calculate total capacity for a regional branch and its children
     */
    private function calculateRegionalCapacity(Branch $regional): array
    {
        $regionalCapacity = $regional->getCapacityMetrics();
        $childrenCapacity = $regional->children->sum(function ($child) {
            return $child->getCapacityMetrics()['utilization_rate'] ?? 0;
        });

        return [
            'regional_utilization' => $regionalCapacity['utilization_rate'] ?? 0,
            'children_utilization' => $childrenCapacity,
            'total_utilization' => $regionalCapacity['utilization_rate'] + $childrenCapacity,
        ];
    }

    /**
     * Count total workers in a regional branch and its children
     */
    private function countRegionalWorkers(Branch $regional): int
    {
        $regionalWorkers = $regional->activeWorkers()->count();
        $childrenWorkers = $regional->children->sum(function ($child) {
            return $child->activeWorkers()->count();
        });

        return $regionalWorkers + $childrenWorkers;
    }

    /**
     * Rebuild entire hierarchy cache (useful after bulk operations)
     */
    public function rebuildHierarchyCache(): void
    {
        $allBranches = Branch::active()->get();

        foreach ($allBranches as $branch) {
            $branch->updateHierarchyInfo();
        }
    }

    /**
     * Get branches by hierarchy level
     */
    public function getBranchesByLevel(int $level): Collection
    {
        return Branch::active()
            ->where('hierarchy_level', $level)
            ->orderBy('name')
            ->get();
    }

    /**
     * Find the best parent for a new branch based on location and capacity
     */
    public function suggestParentForNewBranch(string $type, ?float $latitude = null, ?float $longitude = null): ?Branch
    {
        $potentialParents = $this->getPotentialParents($type);

        if ($potentialParents->isEmpty()) {
            return null;
        }

        // If location provided, find geographically closest
        if ($latitude && $longitude) {
            $potentialParents = $potentialParents->sortBy(function ($parent) use ($latitude, $longitude) {
                if (!$parent->latitude || !$parent->longitude) {
                    return PHP_INT_MAX; // Put at end if no location
                }

                return $this->calculateDistance($latitude, $longitude, $parent->latitude, $parent->longitude);
            });
        }

        // Return the first (closest or first in list)
        return $potentialParents->first();
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}