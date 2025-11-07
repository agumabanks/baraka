<?php

namespace App\Services;

use App\Models\ContractTemplate;
use App\Models\User;
use App\Models\Contract;
use App\Models\ContractVolumeDiscount;
use App\Models\ContractServiceLevel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Contract Template Service
 * 
 * Handles contract template creation, management, and versioning
 * including:
 * - Template creation and customization
 * - Service level agreement definitions
 * - Pricing tier configurations
 * - Terms and conditions management
 * - Template versioning and approval workflow
 */
class ContractTemplateService
{
    private const CACHE_TTL_TEMPLATES = 1800; // 30 minutes
    private const MAX_TEMPLATE_VERSIONS = 10;

    public function __construct(
        private WebhookManagementService $webhookService
    ) {}

    /**
     * Create a new contract template
     */
    public function createTemplate(array $templateData, int $createdByUserId): ContractTemplate
    {
        return DB::transaction(function() use ($templateData, $createdByUserId) {
            // Validate template data
            $this->validateTemplateData($templateData);

            // Create base template
            $template = ContractTemplate::create([
                'name' => $templateData['name'],
                'description' => $templateData['description'] ?? '',
                'template_type' => $templateData['template_type'] ?? 'standard',
                'terms_template' => $this->processTermsTemplate($templateData['terms'] ?? []),
                'default_settings' => $templateData['default_settings'] ?? [],
                'approval_required' => $templateData['approval_required'] ?? false,
                'auto_renewal_enabled' => $templateData['auto_renewal_enabled'] ?? false,
                'created_by_id' => $createdByUserId
            ]);

            // Create default volume discount tiers if specified
            if (isset($templateData['volume_tiers'])) {
                $this->createVolumeTiers($template, $templateData['volume_tiers']);
            }

            // Create service level definitions if specified
            if (isset($templateData['service_levels'])) {
                $this->createServiceLevels($template, $templateData['service_levels']);
            }

            // Log template creation
            $this->logTemplateActivity($template, 'created', $createdByUserId);

            // Cache template
            $this->cacheTemplate($template);

            Log::info('Contract template created', [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'template_type' => $template->template_type,
                'created_by' => $createdByUserId
            ]);

            return $template->load(['creator', 'volumeTiers', 'serviceLevels']);
        });
    }

    /**
     * Update an existing template
     */
    public function updateTemplate(int $templateId, array $updateData, int $updatedByUserId): ContractTemplate
    {
        return DB::transaction(function() use ($templateId, $updateData, $updatedByUserId) {
            $template = ContractTemplate::findOrFail($templateId);
            
            // Check if update requires approval
            $requiresApproval = $this->requiresTemplateApproval($updateData);
            
            if ($requiresApproval && !$template->approval_required) {
                $template->update(['approval_required' => true]);
            }

            // Create new version if major changes
            if ($this->isMajorChange($updateData)) {
                $newVersion = $this->createTemplateVersion($template, $updateData, $updatedByUserId);
                return $newVersion;
            }

            // Update existing template
            $oldData = $template->toArray();
            $template->update($updateData);

            // Log template update
            $this->logTemplateActivity($template, 'updated', $updatedByUserId, $oldData);

            // Clear cache
            $this->clearTemplateCache($template->id);

            return $template->fresh();
        });
    }

    /**
     * Clone a template with modifications
     */
    public function cloneTemplate(int $templateId, string $newName, array $modifications = [], int $clonedByUserId): ContractTemplate
    {
        $originalTemplate = ContractTemplate::findOrFail($templateId);
        
        $clonedData = array_merge($originalTemplate->toArray(), [
            'name' => $newName,
            'created_by_id' => $clonedByUserId
        ], $modifications);

        $clonedTemplate = $this->createTemplate($clonedData, $clonedByUserId);
        
        // Copy related data
        $this->copyTemplateData($originalTemplate, $clonedTemplate);

        // Log template cloning
        $this->logTemplateActivity($clonedTemplate, 'cloned', $clonedByUserId, [
            'original_template_id' => $templateId,
            'original_template_name' => $originalTemplate->name
        ]);

        return $clonedTemplate->load(['creator', 'volumeTiers', 'serviceLevels']);
    }

    /**
     * Create template from existing contract
     */
    public function createTemplateFromContract(int $contractId, string $templateName, int $createdByUserId): ContractTemplate
    {
        $contract = Contract::findOrFail($contractId);
        
        $templateData = [
            'name' => $templateName,
            'description' => "Template created from contract: {$contract->name}",
            'template_type' => $contract->contract_type,
            'terms' => $contract->sla_json,
            'default_settings' => [
                'discount_tiers' => $contract->discount_tiers,
                'service_level_commitments' => $contract->service_level_commitments,
                'auto_renewal_terms' => $contract->auto_renewal_terms,
                'compliance_requirements' => $contract->compliance_requirements
            ],
            'approval_required' => true,
            'auto_renewal_enabled' => !is_null($contract->auto_renewal_terms)
        ];

        $template = $this->createTemplate($templateData, $createdByUserId);
        
        // Copy related data from contract
        $this->copyContractDataToTemplate($contract, $template);

        // Log template creation
        $this->logTemplateActivity($template, 'created_from_contract', $createdByUserId, [
            'source_contract_id' => $contractId,
            'source_contract_name' => $contract->name
        ]);

        return $template->load(['creator', 'volumeTiers', 'serviceLevels']);
    }

    /**
     * Get template with all related data
     */
    public function getTemplateWithDetails(int $templateId): array
    {
        $template = ContractTemplate::findOrFail($templateId);
        
        return [
            'template' => $template->getTemplatePreview(),
            'creator' => [
                'id' => $template->creator->id,
                'name' => $template->creator->name,
                'email' => $template->creator->email
            ],
            'volume_tiers' => $this->getTemplateVolumeTiers($templateId),
            'service_levels' => $this->getTemplateServiceLevels($templateId),
            'usage_statistics' => $this->getTemplateUsageStatistics($templateId),
            'version_history' => $this->getTemplateVersionHistory($templateId),
            'approval_workflow' => $this->getTemplateApprovalWorkflow($templateId)
        ];
    }

    /**
     * Search templates by criteria
     */
    public function searchTemplates(array $criteria = []): Collection
    {
        $query = ContractTemplate::with(['creator', 'contracts']);

        // Apply filters
        if (!empty($criteria['template_type'])) {
            $query->where('template_type', $criteria['template_type']);
        }

        if (!empty($criteria['created_by'])) {
            $query->where('created_by_id', $criteria['created_by']);
        }

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if (isset($criteria['approval_required'])) {
            $query->where('approval_required', $criteria['approval_required']);
        }

        if (isset($criteria['auto_renewal_enabled'])) {
            $query->where('auto_renewal_enabled', $criteria['auto_renewal_enabled']);
        }

        // Sorting
        $sortBy = $criteria['sort_by'] ?? 'created_at';
        $sortOrder = $criteria['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->get();
    }

    /**
     * Get template recommendations for customer
     */
    public function getRecommendedTemplates(int $customerId): array
    {
        $customer = \App\Models\Customer::findOrFail($customerId);
        $customerContracts = Contract::where('customer_id', $customerId)->get();
        
        $recommendations = [];
        
        // Get templates by similar customer type
        $similarCustomerTemplates = ContractTemplate::where('template_type', $customer->customer_type ?? 'standard')
                                                  ->where('approval_required', false)
                                                  ->get();
        
        foreach ($similarCustomerTemplates as $template) {
            $recommendations[] = [
                'template' => $template->getTemplatePreview(),
                'match_reason' => 'customer_type_match',
                'match_score' => 0.8
            ];
        }
        
        // Get popular templates
        $popularTemplates = $this->getPopularTemplates(5);
        foreach ($popularTemplates as $template) {
            $recommendations[] = [
                'template' => $template->getTemplatePreview(),
                'match_reason' => 'popular_choice',
                'match_score' => 0.6
            ];
        }
        
        // Sort by match score
        usort($recommendations, function($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });
        
        return array_slice($recommendations, 0, 5);
    }

    /**
     * Get template statistics
     */
    public function getTemplateStatistics(): array
    {
        $totalTemplates = ContractTemplate::count();
        $templatesByType = ContractTemplate::selectRaw('template_type, COUNT(*) as count')
                                         ->groupBy('template_type')
                                         ->pluck('count', 'template_type')
                                         ->toArray();
        
        $activeContracts = Contract::whereHas('template')->count();
        $mostUsedTemplate = Contract::selectRaw('template_id, COUNT(*) as usage_count')
                                   ->groupBy('template_id')
                                   ->orderBy('usage_count', 'desc')
                                   ->first();
        
        $mostUsedTemplateData = null;
        if ($mostUsedTemplate) {
            $mostUsedTemplateData = ContractTemplate::find($mostUsedTemplate->template_id);
        }
        
        return [
            'total_templates' => $totalTemplates,
            'templates_by_type' => $templatesByType,
            'active_contracts' => $activeContracts,
            'most_used_template' => $mostUsedTemplateData?->getTemplatePreview(),
            'most_used_count' => $mostUsedTemplate->usage_count ?? 0,
            'templates_requiring_approval' => ContractTemplate::where('approval_required', true)->count(),
            'auto_renewal_templates' => ContractTemplate::where('auto_renewal_enabled', true)->count(),
            'recent_templates' => ContractTemplate::where('created_at', '>=', now()->subDays(30))->count()
        ];
    }

    /**
     * Validate template data
     */
    private function validateTemplateData(array $templateData): void
    {
        $errors = [];

        if (empty($templateData['name'])) {
            $errors[] = 'Template name is required';
        }

        if (empty($templateData['terms']) && empty($templateData['default_settings'])) {
            $errors[] = 'Either terms or default settings must be provided';
        }

        if (isset($templateData['terms'])) {
            $missingTerms = $this->getRequiredTerms();
            $providedTerms = array_keys($templateData['terms']);
            $missingRequiredTerms = array_diff($missingTerms, $providedTerms);
            
            if (!empty($missingRequiredTerms)) {
                $errors[] = 'Missing required terms: ' . implode(', ', $missingRequiredTerms);
            }
        }

        if (!empty($errors)) {
            throw new \Exception('Template validation failed: ' . implode(', ', $errors));
        }
    }

    private function getRequiredTerms(): array
    {
        return [
            'payment_terms',
            'delivery_terms', 
            'liability',
            'force_majeure',
            'termination_clause',
            'dispute_resolution'
        ];
    }

    private function processTermsTemplate(array $terms): array
    {
        $processedTerms = [];
        
        foreach ($terms as $key => $value) {
            $processedTerms[$key] = [
                'content' => $value,
                'version' => '1.0',
                'last_updated' => now()->toISOString(),
                'variables' => $this->extractTemplateVariables($value)
            ];
        }
        
        return $processedTerms;
    }

    private function extractTemplateVariables(string $content): array
    {
        // Extract variables in {{variable}} format
        preg_match_all('/\{\{(\w+)\}\}/', $content, $matches);
        return array_unique($matches[1] ?? []);
    }

    private function createVolumeTiers(ContractTemplate $template, array $volumeTiers): void
    {
        foreach ($volumeTiers as $tierData) {
            ContractVolumeDiscount::create(array_merge($tierData, [
                'template_id' => $template->id,
                'is_automatic' => $tierData['is_automatic'] ?? true
            ]));
        }
    }

    private function createServiceLevels(ContractTemplate $template, array $serviceLevels): void
    {
        foreach ($serviceLevels as $levelData) {
            ContractServiceLevel::create(array_merge($levelData, [
                'template_id' => $template->id
            ]));
        }
    }

    private function requiresTemplateApproval(array $updateData): bool
    {
        $criticalFields = ['terms_template', 'default_settings', 'auto_renewal_enabled'];
        
        foreach ($criticalFields as $field) {
            if (array_key_exists($field, $updateData)) {
                return true;
            }
        }
        
        return false;
    }

    private function isMajorChange(array $updateData): bool
    {
        $majorChangeFields = ['terms_template', 'default_settings', 'template_type'];
        
        foreach ($majorChangeFields as $field) {
            if (array_key_exists($field, $updateData)) {
                return true;
            }
        }
        
        return false;
    }

    private function createTemplateVersion(ContractTemplate $originalTemplate, array $updateData, int $versionCreatedBy): ContractTemplate
    {
        // Create new version with incremented version number
        $versionNumber = $this->getNextVersionNumber($originalTemplate);
        $versionName = $originalTemplate->name . " v{$versionNumber}";
        
        $versionData = array_merge($updateData, [
            'name' => $versionName,
            'parent_template_id' => $originalTemplate->id,
            'version_number' => $versionNumber,
            'approval_required' => true,
            'created_by_id' => $versionCreatedBy
        ]);
        
        $newVersion = $this->createTemplate($versionData, $versionCreatedBy);
        
        return $newVersion;
    }

    private function getNextVersionNumber(ContractTemplate $template): int
    {
        $existingVersions = ContractTemplate::where('parent_template_id', $template->id)
                                           ->max('version_number');
        
        return ($existingVersions ?? 0) + 1;
    }

    private function getTemplateVolumeTiers(int $templateId): array
    {
        $tiers = ContractVolumeDiscount::where('template_id', $templateId)
                                     ->orderBy('volume_requirement')
                                     ->get();
        
        return $tiers->map(function($tier) {
            return $tier->getTierSummary();
        })->toArray();
    }

    private function getTemplateServiceLevels(int $templateId): array
    {
        $levels = ContractServiceLevel::where('template_id', $templateId)->get();
        
        return $levels->map(function($level) {
            return [
                'id' => $level->id,
                'service_level_code' => $level->service_level_code,
                'delivery_window' => $level->getDeliveryWindow(),
                'reliability_threshold' => $level->reliability_threshold,
                'sla_claim_ratio' => $level->sla_claim_ratio
            ];
        })->toArray();
    }

    private function getTemplateUsageStatistics(int $templateId): array
    {
        $contracts = Contract::where('template_id', $templateId)->get();
        
        return [
            'total_contracts' => $contracts->count(),
            'active_contracts' => $contracts->where('status', 'active')->count(),
            'expired_contracts' => $contracts->where('status', 'expired')->count(),
            'average_contract_value' => $contracts->avg('volume_commitment') ?? 0,
            'last_used' => $contracts->max('created_at')?->toISOString()
        ];
    }

    private function getTemplateVersionHistory(int $templateId): array
    {
        $template = ContractTemplate::findOrFail($templateId);
        $versions = collect([$template]);
        
        // Get parent versions
        $parent = $template;
        while ($parent->parent_template_id) {
            $parent = ContractTemplate::find($parent->parent_template_id);
            if ($parent) {
                $versions->prepend($parent);
            }
        }
        
        // Get child versions
        $children = ContractTemplate::where('parent_template_id', $templateId)
                                   ->orderBy('version_number', 'desc')
                                   ->get();
        
        $allVersions = $versions->concat($children);
        
        return $allVersions->map(function($version) {
            return [
                'id' => $version->id,
                'name' => $version->name,
                'version_number' => $version->version_number,
                'is_current' => $version->id === request()->route('template'),
                'created_at' => $version->created_at->toISOString(),
                'created_by' => $version->creator?->name,
                'status' => $this->getVersionStatus($version)
            ];
        })->toArray();
    }

    private function getVersionStatus(ContractTemplate $template): string
    {
        if ($template->approval_required && !$template->approved_at) {
            return 'pending_approval';
        }
        
        if ($template->archived_at) {
            return 'archived';
        }
        
        $hasActiveContracts = Contract::where('template_id', $template->id)
                                    ->where('status', 'active')
                                    ->exists();
        
        return $hasActiveContracts ? 'active' : 'draft';
    }

    private function getTemplateApprovalWorkflow(int $templateId): array
    {
        $template = ContractTemplate::findOrFail($templateId);
        
        return [
            'requires_approval' => $template->approval_required,
            'approval_status' => $this->getVersionStatus($template),
            'approved_at' => $template->approved_at?->toISOString(),
            'approved_by' => $template->approvedBy?->name,
            'approval_notes' => $template->approval_notes
        ];
    }

    private function getPopularTemplates(int $limit = 5): Collection
    {
        return ContractTemplate::withCount('contracts')
                              ->having('contracts_count', '>', 0)
                              ->orderBy('contracts_count', 'desc')
                              ->limit($limit)
                              ->get();
    }

    private function copyTemplateData(ContractTemplate $source, ContractTemplate $target): void
    {
        // Copy volume tiers
        foreach ($source->volumeTiers as $volumeTier) {
            $volumeTier->replicate()->update(['template_id' => $target->id]);
        }
        
        // Copy service levels
        foreach ($source->serviceLevels as $serviceLevel) {
            $serviceLevel->replicate()->update(['template_id' => $target->id]);
        }
    }

    private function copyContractDataToTemplate(Contract $contract, ContractTemplate $template): void
    {
        // Copy volume discount tiers from contract
        foreach ($contract->volumeDiscounts as $volumeDiscount) {
            ContractVolumeDiscount::create(array_merge($volumeDiscount->toArray(), [
                'template_id' => $template->id,
                'contract_id' => null
            ]));
        }
        
        // Copy service levels from contract
        foreach ($contract->serviceLevelCommitments as $serviceLevel) {
            ContractServiceLevel::create(array_merge($serviceLevel->toArray(), [
                'template_id' => $template->id,
                'contract_id' => null
            ]));
        }
    }

    private function logTemplateActivity(ContractTemplate $template, string $action, int $userId, array $additionalData = []): void
    {
        Log::info("Contract template {$action}", array_merge([
            'template_id' => $template->id,
            'template_name' => $template->name,
            'user_id' => $userId
        ], $additionalData));
    }

    private function cacheTemplate(ContractTemplate $template): void
    {
        $cacheKey = "template_details_{$template->id}";
        $data = [
            'template' => $template->toArray(),
            'volume_tiers' => $template->volumeTiers->toArray(),
            'service_levels' => $template->serviceLevels->toArray()
        ];
        
        Cache::put($cacheKey, $data, now()->addMinutes(self::CACHE_TTL_TEMPLATES));
    }

    private function clearTemplateCache(int $templateId): void
    {
        Cache::forget("template_details_{$templateId}");
    }
}