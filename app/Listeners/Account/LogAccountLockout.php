<?php

namespace App\Listeners\Account;

use App\Events\Account\AccountLocked;
use App\Services\Security\AuditLogger;

class LogAccountLockout
{
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    public function handle(AccountLocked $event): void
    {
        $this->auditLogger->logAccountLocked($event->user, $event->attempts, $event->reason);
    }
}
