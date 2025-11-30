<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'email_enabled',
        'sms_enabled',
        'push_enabled',
        'whatsapp_enabled',
        'enabled_events',
        'disabled_events',
        'quiet_start',
        'quiet_end',
        'timezone',
        'max_sms_per_day',
        'max_push_per_hour',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'enabled_events' => 'array',
        'disabled_events' => 'array',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if channel is enabled
     */
    public function isChannelEnabled(string $channel): bool
    {
        return match ($channel) {
            'email' => $this->email_enabled,
            'sms' => $this->sms_enabled,
            'push' => $this->push_enabled,
            'whatsapp' => $this->whatsapp_enabled,
            default => false,
        };
    }

    /**
     * Check if event is enabled
     */
    public function isEventEnabled(string $eventCode): bool
    {
        // If disabled_events contains this event, it's disabled
        if (!empty($this->disabled_events) && in_array($eventCode, $this->disabled_events)) {
            return false;
        }

        // If enabled_events is set, check if event is in the list
        if (!empty($this->enabled_events)) {
            return in_array($eventCode, $this->enabled_events);
        }

        // Default: all events enabled
        return true;
    }

    /**
     * Check if currently in quiet hours
     */
    public function isQuietHours(): bool
    {
        if (!$this->quiet_start || !$this->quiet_end) {
            return false;
        }

        $now = now()->timezone($this->timezone);
        $quietStart = $now->copy()->setTimeFromTimeString($this->quiet_start);
        $quietEnd = $now->copy()->setTimeFromTimeString($this->quiet_end);

        // Handle overnight quiet hours (e.g., 22:00 to 07:00)
        if ($quietStart->gt($quietEnd)) {
            return $now->gte($quietStart) || $now->lte($quietEnd);
        }

        return $now->between($quietStart, $quietEnd);
    }

    /**
     * Get or create preferences for a notifiable
     */
    public static function getOrCreate($notifiable): self
    {
        return self::firstOrCreate([
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id,
        ]);
    }
}
