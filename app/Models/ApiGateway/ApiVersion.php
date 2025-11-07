<?php

namespace App\Models\ApiGateway;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'name',
        'description',
        'is_active',
        'deprecation_date',
        'migrated_to_version',
        'is_deprecated',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_deprecated' => 'boolean',
        'deprecation_date' => 'datetime',
    ];

    /**
     * Get the routes for this version
     */
    public function routes()
    {
        return $this->hasMany(ApiRoute::class);
    }

    /**
     * Get the routes that have been migrated from this version
     */
    public function migratedRoutes()
    {
        return $this->hasMany(ApiRoute::class, 'migrated_from_version');
    }

    /**
     * Get the version this was migrated to
     */
    public function migratedTo()
    {
        return $this->belongsTo(self::class, 'migrated_to_version', 'version');
    }

    /**
     * Check if version is deprecated
     */
    public function isDeprecated(): bool
    {
        return $this->is_deprecated || 
               ($this->deprecation_date && $this->deprecation_date->isPast());
    }

    /**
     * Get active routes for this version
     */
    public function activeRoutes()
    {
        return $this->routes()->where('is_active', true);
    }

    /**
     * Scope to get active versions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('is_deprecated', false)
                    ->where(function ($q) {
                        $q->whereNull('deprecation_date')
                          ->orWhere('deprecation_date', '>', now());
                    });
    }

    /**
     * Scope to get deprecated versions
     */
    public function scopeDeprecated($query)
    {
        return $query->where('is_deprecated', true)
                    ->orWhere('deprecation_date', '<=', now());
    }

    /**
     * Get migration status
     */
    public function getMigrationStatus(): array
    {
        $totalRoutes = $this->routes()->count();
        $migratedRoutes = $this->routes()->whereNotNull('migrated_to_version')->count();
        
        return [
            'total_routes' => $totalRoutes,
            'migrated_routes' => $migratedRoutes,
            'remaining_routes' => $totalRoutes - $migratedRoutes,
            'migration_progress' => $totalRoutes > 0 ? ($migratedRoutes / $totalRoutes) * 100 : 0,
        ];
    }
}