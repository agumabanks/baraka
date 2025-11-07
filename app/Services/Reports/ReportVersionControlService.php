<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ReportVersionControlService
{
    protected $versionTable = 'report_definitions_version';
    protected $currentTable = 'report_definitions';
    protected $tagTable = 'report_tags';
    protected $maxVersions = 50; // Maximum versions to keep per report

    /**
     * Create a new report definition with initial version
     */
    public function createReport(array $reportData, string $createdBy): array
    {
        $versionId = DB::transaction(function () use ($reportData, $createdBy) {
            // Create the main report definition
            $reportId = DB::table($this->currentTable)->insertGetId([
                'name' => $reportData['name'],
                'description' => $reportData['description'] ?? null,
                'type' => $reportData['type'],
                'category' => $reportData['category'] ?? null,
                'parameters' => json_encode($reportData['parameters'] ?? []),
                'query_definition' => $reportData['query_definition'],
                'output_format' => $reportData['output_format'] ?? 'json',
                'is_public' => $reportData['is_public'] ?? false,
                'created_by' => $createdBy,
                'created_at' => now(),
                'updated_at' => now(),
                'current_version_id' => null
            ]);

            // Create initial version
            $versionId = $this->createVersion($reportId, $reportData, $createdBy, 'v1.0', 'Initial version', true);

            // Update the report with current version
            DB::table($this->currentTable)
                ->where('id', $reportId)
                ->update(['current_version_id' => $versionId]);

            return $versionId;
        });

        Log::info("Created report definition", [
            'report_id' => $reportId,
            'version_id' => $versionId,
            'created_by' => $createdBy
        ]);

        return [
            'success' => true,
            'report_id' => $reportId,
            'version_id' => $versionId,
            'message' => 'Report definition created successfully'
        ];
    }

    /**
     * Create a new version of an existing report
     */
    public function createVersion(int $reportId, array $reportData, string $updatedBy, string $version, string $changeLog, bool $isActive = true): int
    {
        // Get current version to create increment
        $currentVersion = $this->getCurrentVersion($reportId);
        $newVersion = $version ?? $this->getNextVersion($currentVersion);

        // Create version record
        $versionId = DB::table($this->versionTable)->insertGetId([
            'report_id' => $reportId,
            'version' => $newVersion,
            'name' => $reportData['name'],
            'description' => $reportData['description'] ?? null,
            'type' => $reportData['type'],
            'category' => $reportData['category'] ?? null,
            'parameters' => json_encode($reportData['parameters'] ?? []),
            'query_definition' => $reportData['query_definition'],
            'output_format' => $reportData['output_format'] ?? 'json',
            'change_log' => $changeLog,
            'is_active' => $isActive,
            'updated_by' => $updatedBy,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Deactivate other versions if this is the active one
        if ($isActive) {
            DB::table($this->versionTable)
                ->where('report_id', $reportId)
                ->where('id', '!=', $versionId)
                ->update(['is_active' => false]);
        }

        // Clean up old versions
        $this->cleanupOldVersions($reportId);

        // Clear cache
        $this->invalidateReportCache($reportId);

        Log::info("Created new report version", [
            'report_id' => $reportId,
            'version_id' => $versionId,
            'version' => $newVersion,
            'updated_by' => $updatedBy
        ]);

        return $versionId;
    }

    /**
     * Get report with all its versions
     */
    public function getReportWithVersions(int $reportId): ?array
    {
        $report = DB::table($this->currentTable)
            ->where('id', $reportId)
            ->first();

        if (!$report) {
            return null;
        }

        $versions = DB::table($this->versionTable)
            ->where('report_id', $reportId)
            ->orderBy('version', 'desc')
            ->get()
            ->map(function ($version) {
                $version->parameters = json_decode($version->parameters, true);
                return $version;
            })
            ->toArray();

        $report->parameters = json_decode($report->parameters, true);
        $report->versions = $versions;
        $report->current_version = collect($versions)->firstWhere('is_active', true);

        return $report;
    }

    /**
     * Get specific version of a report
     */
    public function getReportVersion(int $versionId): ?array
    {
        $version = DB::table($this->versionTable)
            ->where('id', $versionId)
            ->first();

        if (!$version) {
            return null;
        }

        $version->parameters = json_decode($version->parameters, true);

        return $version;
    }

    /**
     * Compare two versions of a report
     */
    public function compareVersions(int $versionId1, int $versionId2): array
    {
        $version1 = $this->getReportVersion($versionId1);
        $version2 = $this->getReportVersion($versionId2);

        if (!$version1 || !$version2) {
            throw new \InvalidArgumentException("One or both versions not found");
        }

        $differences = [];
        $fields = ['name', 'description', 'type', 'category', 'parameters', 'query_definition', 'output_format'];

        foreach ($fields as $field) {
            $value1 = is_array($version1->$field) ? json_encode($version1->$field) : $version1->$field;
            $value2 = is_array($version2->$field) ? json_encode($version2->$field) : $version2->$field;

            if ($value1 !== $value2) {
                $differences[$field] = [
                    'version1' => $version1->$field,
                    'version2' => $version2->$field,
                    'version1_value' => $value1,
                    'version2_value' => $value2
                ];
            }
        }

        return [
            'version1' => $version1,
            'version2' => $version2,
            'differences' => $differences,
            'summary' => [
                'total_changes' => count($differences),
                'fields_changed' => array_keys($differences)
            ]
        ];
    }

    /**
     * Revert to a previous version
     */
    public function revertToVersion(int $reportId, int $targetVersionId, string $updatedBy): array
    {
        $targetVersion = $this->getReportVersion($targetVersionId);

        if (!$targetVersion || $targetVersion->report_id != $reportId) {
            throw new \InvalidArgumentException("Target version not found or doesn't belong to this report");
        }

        // Create new version based on the target version
        $reportData = [
            'name' => $targetVersion->name,
            'description' => $targetVersion->description,
            'type' => $targetVersion->type,
            'category' => $targetVersion->category,
            'parameters' => $targetVersion->parameters,
            'query_definition' => $targetVersion->query_definition,
            'output_format' => $targetVersion->output_format
        ];

        $newVersionId = $this->createVersion(
            $reportId, 
            $reportData, 
            $updatedBy, 
            $this->getNextVersion($this->getCurrentVersion($reportId)),
            "Reverted to version {$targetVersion->version}",
            true
        );

        return [
            'success' => true,
            'new_version_id' => $newVersionId,
            'reverted_to' => $targetVersion->version,
            'message' => "Report reverted to version {$targetVersion->version}"
        ];
    }

    /**
     * Get report history with pagination
     */
    public function getReportHistory(int $reportId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $versions = DB::table($this->versionTable)
            ->where('report_id', $reportId)
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(function ($version) {
                $version->parameters = json_decode($version->parameters, true);
                return $version;
            });

        $total = DB::table($this->versionTable)
            ->where('report_id', $reportId)
            ->count();

        return [
            'versions' => $versions->toArray(),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage)
            ]
        ];
    }

    /**
     * Search reports by name, description, or tags
     */
    public function searchReports(string $query, ?array $filters = null, int $limit = 20): array
    {
        $cacheKey = "report_search:" . md5($query . serialize($filters) . $limit);
        
        return Cache::remember($cacheKey, 300, function () use ($query, $filters, $limit) {
            $q = DB::table($this->currentTable)
                ->leftJoin($this->tagTable, $this->currentTable . '.id', '=', $this->tagTable . '.report_id')
                ->where(function ($builder) use ($query) {
                    $builder->where($this->currentTable . '.name', 'LIKE', "%{$query}%")
                        ->orWhere($this->currentTable . '.description', 'LIKE', "%{$query}%")
                        ->orWhere($this->tagTable . '.tag', 'LIKE', "%{$query}%");
                });

            if ($filters) {
                if (isset($filters['type'])) {
                    $q->where($this->currentTable . '.type', $filters['type']);
                }
                if (isset($filters['category'])) {
                    $q->where($this->currentTable . '.category', $filters['category']);
                }
                if (isset($filters['created_by'])) {
                    $q->where($this->currentTable . '.created_by', $filters['created_by']);
                }
                if (isset($filters['is_public'])) {
                    $q->where($this->currentTable . '.is_public', $filters['is_public']);
                }
            }

            return $q->select([
                $this->currentTable . '.*',
                $this->tagTable . '.tag'
            ])
            ->groupBy($this->currentTable . '.id')
            ->limit($limit)
            ->get()
            ->map(function ($report) {
                $report->parameters = json_decode($report->parameters, true);
                return $report;
            })
            ->toArray();
        });
    }

    /**
     * Get reports by category or type
     */
    public function getReportsByCategory(string $category, int $limit = 50): array
    {
        return Cache::remember("reports_category:{$category}:{$limit}", 600, function () use ($category, $limit) {
            return DB::table($this->currentTable)
                ->where('category', $category)
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->map(function ($report) {
                    $report->parameters = json_decode($report->parameters, true);
                    return $report;
                })
                ->toArray();
        });
    }

    /**
     * Export report definition and all its versions
     */
    public function exportReport(int $reportId): array
    {
        $report = $this->getReportWithVersions($reportId);
        
        if (!$report) {
            throw new \InvalidArgumentException("Report not found");
        }

        return [
            'export_metadata' => [
                'exported_at' => now()->toISOString(),
                'exported_by' => Auth::user()->id ?? 'system',
                'version' => '1.0'
            ],
            'report' => $report
        ];
    }

    /**
     * Import report definition from JSON
     */
    public function importReport(array $importData, string $importedBy): array
    {
        if (!isset($importData['report'])) {
            throw new \InvalidArgumentException("Invalid import format: missing report data");
        }

        $reportData = $importData['report'];
        
        // Check if report with same name already exists
        $existingReport = DB::table($this->currentTable)
            ->where('name', $reportData['name'])
            ->first();

        if ($existingReport) {
            // Create new version of existing report
            $versionId = $this->createVersion(
                $existingReport->id,
                $reportData,
                $importedBy,
                $this->getNextVersion($this->getCurrentVersion($existingReport->id)),
                "Imported from external source"
            );

            return [
                'success' => true,
                'report_id' => $existingReport->id,
                'version_id' => $versionId,
                'message' => "Report imported as new version"
            ];
        } else {
            // Create new report
            return $this->createReport($reportData, $importedBy);
        }
    }

    /**
     * Add tags to a report
     */
    public function addTags(int $reportId, array $tags): array
    {
        $report = DB::table($this->currentTable)->find($reportId);
        
        if (!$report) {
            throw new \InvalidArgumentException("Report not found");
        }

        $existingTags = DB::table($this->tagTable)
            ->where('report_id', $reportId)
            ->pluck('tag')
            ->toArray();

        $newTags = array_diff($tags, $existingTags);

        foreach ($newTags as $tag) {
            DB::table($this->tagTable)->insert([
                'report_id' => $reportId,
                'tag' => strtolower(trim($tag)),
                'created_at' => now()
            ]);
        }

        $this->invalidateReportCache($reportId);

        return [
            'success' => true,
            'added_tags' => $newTags,
            'total_tags' => count(array_unique(array_merge($existingTags, $tags)))
        ];
    }

    /**
     * Get all reports with their latest version info
     */
    public function getAllReports(?array $filters = null): array
    {
        $cacheKey = "all_reports:" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($filters) {
            $q = DB::table($this->currentTable)
                ->leftJoin($this->versionTable, function ($join) {
                    $join->on($this->currentTable . '.current_version_id', '=', $this->versionTable . '.id');
                })
                ->leftJoin($this->tagTable, $this->currentTable . '.id', '=', $this->tagTable . '.report_id')
                ->select([
                    $this->currentTable . '.*',
                    $this->versionTable . '.version as current_version',
                    $this->versionTable . '.updated_at as current_version_updated_at',
                    DB::raw('GROUP_CONCAT(' . $this->tagTable . '.tag) as tags')
                ])
                ->groupBy($this->currentTable . '.id');

            if ($filters) {
                if (isset($filters['type'])) {
                    $q->where($this->currentTable . '.type', $filters['type']);
                }
                if (isset($filters['category'])) {
                    $q->where($this->currentTable . '.category', $filters['category']);
                }
                if (isset($filters['created_by'])) {
                    $q->where($this->currentTable . '.created_by', $filters['created_by']);
                }
            }

            return $q->get()
                ->map(function ($report) {
                    $report->parameters = json_decode($report->parameters, true);
                    $report->tags = $report->tags ? explode(',', $report->tags) : [];
                    return $report;
                })
                ->toArray();
        });
    }

    protected function getCurrentVersion(int $reportId): ?string
    {
        $version = DB::table($this->versionTable)
            ->where('report_id', $reportId)
            ->where('is_active', true)
            ->value('version');

        return $version;
    }

    protected function getNextVersion(?string $currentVersion): string
    {
        if (!$currentVersion) {
            return 'v1.0';
        }

        // Parse version (e.g., v1.0 -> v1.1)
        if (preg_match('/^v(\d+)\.(\d+)$/', $currentVersion, $matches)) {
            $major = (int) $matches[1];
            $minor = (int) $matches[2];
            return "v{$major}." . ($minor + 1);
        }

        return 'v2.0'; // Default if parsing fails
    }

    protected function cleanupOldVersions(int $reportId): void
    {
        $versions = DB::table($this->versionTable)
            ->where('report_id', $reportId)
            ->orderBy('created_at', 'desc')
            ->offset($this->maxVersions)
            ->get();

        foreach ($versions as $version) {
            DB::table($this->versionTable)
                ->where('id', $version->id)
                ->delete();
        }
    }

    protected function invalidateReportCache(int $reportId): void
    {
        // Clear report-specific cache
        $report = DB::table($this->currentTable)->find($reportId);
        if ($report) {
            Cache::forget("report_{$reportId}");
            Cache::forget("report_search:" . md5($report->name));
        }

        // Clear general report lists
        Cache::forget("all_reports");
        Cache::forget("reports_category:{$report->category}");
    }
}