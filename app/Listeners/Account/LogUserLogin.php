<?php

namespace App\Listeners\Account;

use App\Events\Account\UserLoggedIn;
use App\Services\Security\AuditLogger;
use App\Services\Security\SessionManager;

class LogUserLogin
{
    protected AuditLogger $auditLogger;
    protected SessionManager $sessionManager;

    public function __construct(AuditLogger $auditLogger, SessionManager $sessionManager)
    {
        $this->auditLogger = $auditLogger;
        $this->sessionManager = $sessionManager;
    }

    public function handle(UserLoggedIn $event): void
    {
        // Log to audit log
        $this->auditLogger->logLogin($event->user, true, $event->metadata);
        
        // Track session
        $this->sessionManager->trackLogin($event->user, request());
    }
}
