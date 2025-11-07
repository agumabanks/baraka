<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAccessibilityPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'high_contrast',
        'large_text',
        'reduced_motion',
        'screen_reader_mode',
        'keyboard_navigation_only',
        'font_size',
        'color_scheme',
        'disable_animations',
        'enable_focus_indicators',
        'custom_css',
        'preferences_data',
    ];

    protected $casts = [
        'high_contrast' => 'boolean',
        'large_text' => 'boolean',
        'reduced_motion' => 'boolean',
        'screen_reader_mode' => 'boolean',
        'keyboard_navigation_only' => 'boolean',
        'disable_animations' => 'boolean',
        'enable_focus_indicators' => 'boolean',
        'custom_css' => 'array',
        'preferences_data' => 'array',
    ];

    /**
     * Get the user who owns these preferences
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all CSS classes to apply based on preferences
     */
    public function getCssClassesAttribute(): array
    {
        $classes = [];
        
        if ($this->high_contrast) {
            $classes[] = 'high-contrast';
        }
        
        if ($this->large_text) {
            $classes[] = 'large-text';
        }
        
        if ($this->reduced_motion) {
            $classes[] = 'reduced-motion';
        }
        
        if ($this->disable_animations) {
            $classes[] = 'no-animations';
        }
        
        $classes[] = "font-size-{$this->font_size}";
        $classes[] = "color-scheme-{$this->color_scheme}";
        
        return $classes;
    }

    /**
     * Get accessibility features summary
     */
    public function getFeaturesSummaryAttribute(): array
    {
        $features = [];
        
        if ($this->high_contrast) {
            $features[] = 'High Contrast';
        }
        
        if ($this->large_text) {
            $features[] = 'Large Text';
        }
        
        if ($this->reduced_motion) {
            $features[] = 'Reduced Motion';
        }
        
        if ($this->screen_reader_mode) {
            $features[] = 'Screen Reader Mode';
        }
        
        if ($this->keyboard_navigation_only) {
            $features[] = 'Keyboard Navigation Only';
        }
        
        if ($this->disable_animations) {
            $features[] = 'No Animations';
        }
        
        return $features;
    }
}