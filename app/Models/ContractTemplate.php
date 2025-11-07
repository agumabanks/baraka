<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ContractTemplate extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'template_type',
        'terms_template',
        'default_settings',
        'approval_required',
        'auto_renewal_enabled',
        'created_by_id',
    ];

    protected $casts = [
        'terms_template' => 'array',
        'default_settings' => 'array',
        'approval_required' => 'boolean',
        'auto_renewal_enabled' => 'boolean',
        'template_type' => 'string',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('ContractTemplate')
            ->logOnly(['name', 'template_type', 'approval_required'])
            ->setDescriptionForEvent(fn (string $eventName) => "Contract template {$eventName}");
    }

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'template_id');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('template_type', $type);
    }

    public function scopeRequiringApproval($query)
    {
        return $query->where('approval_required', true);
    }

    public function scopeAutoRenewalEnabled($query)
    {
        return $query->where('auto_renewal_enabled', true);
    }

    // Business Logic
    public function getRequiredTerms(): array
    {
        $requiredFields = ['payment_terms', 'delivery_terms', 'liability', 'force_majeure'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($this->terms_template[$field])) {
                $missingFields[] = $field;
            }
        }
        
        return $missingFields;
    }

    public function isValid(): bool
    {
        return empty($this->getRequiredTerms());
    }

    public function getTemplatePreview(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->template_type,
            'has_approval' => $this->approval_required,
            'auto_renewal' => $this->auto_renewal_enabled,
            'terms_count' => count($this->terms_template ?? []),
            'is_valid' => $this->isValid()
        ];
    }

    public function validateAndGetTerms(array $overrides = []): array
    {
        $terms = $this->terms_template;
        
        // Apply any overrides
        $terms = array_merge($terms, $overrides);
        
        // Validate required fields
        $missingFields = $this->getRequiredTerms();
        if (!empty($missingFields)) {
            throw new \Exception("Missing required terms: " . implode(', ', $missingFields));
        }
        
        return $terms;
    }

    public function getDefaultDiscountTiers(): array
    {
        return $this->default_settings['discount_tiers'] ?? [
            [
                'name' => 'Bronze',
                'volume_requirement' => 0,
                'discount_percentage' => 0,
                'benefits' => []
            ],
            [
                'name' => 'Silver', 
                'volume_requirement' => 50,
                'discount_percentage' => 5,
                'benefits' => ['priority_support', 'reporting']
            ],
            [
                'name' => 'Gold',
                'volume_requirement' => 200,
                'discount_percentage' => 10,
                'benefits' => ['priority_support', 'reporting', 'dedicated_account_manager']
            ],
            [
                'name' => 'Platinum',
                'volume_requirement' => 500,
                'discount_percentage' => 15,
                'benefits' => ['priority_support', 'reporting', 'dedicated_account_manager', 'custom_pricing']
            ]
        ];
    }

    public function cloneTemplate(string $newName, ?array $modifications = null): self
    {
        $newTemplate = $this->replicate();
        $newTemplate->name = $newName;
        
        if ($modifications) {
            $newTemplate->fill($modifications);
        }
        
        $newTemplate->save();
        
        return $newTemplate->fresh();
    }
}