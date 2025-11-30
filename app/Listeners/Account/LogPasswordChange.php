<?php

namespace App\Listeners\Account;

use App\Events\Account\PasswordChanged;
use App\Services\Security\AuditLogger;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordChangedNotification;

class LogPasswordChange
{
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    public function handle(PasswordChanged $event): void
    {
        // Log to audit log
        $this->auditLogger->logPasswordChange($event->user, [
            'forced' => $event->forced,
        ]);
        
        // Send email notification
        try {
            Mail::to($event->user->email)->send(new PasswordChangedNotification($event->user));
        } catch (\Exception $e) {
            \Log::error('Failed to send password changed email: ' . $e->getMessage());
        }
    }
}
