<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum BranchWorkerRole: string
{
    case BRANCH_MANAGER = 'BRANCH_MANAGER';
    case OPS_SUPERVISOR = 'OPS_SUPERVISOR';
    case OPS_AGENT = 'OPS_AGENT';
    case SORTATION_AGENT = 'SORTATION_AGENT';
    case COURIER = 'COURIER';
    case DRIVER = 'DRIVER';
    case CUSTOMER_SUPPORT = 'CUSTOMER_SUPPORT';
    case SECURITY = 'SECURITY';
    case DISPATCHER = 'DISPATCHER';
    case FINANCE_OFFICER = 'FINANCE_OFFICER';

    public static function fromString(string $value): self
    {
        $normalized = Str::upper(str_replace([' ', '-'], '_', trim($value)));

        return match ($normalized) {
            'MANAGER', 'BRANCHMANAGER' => self::BRANCH_MANAGER,
            'SUPERVISOR', 'OPERATIONS_SUPERVISOR', 'SUPERVISOR_OPS' => self::OPS_SUPERVISOR,
            'OPERATIONS', 'OPERATIONS_AGENT', 'OPS' => self::OPS_AGENT,
            'SORTER', 'SORTATION', 'WAREHOUSE', 'WAREHOUSE_WORKER' => self::SORTATION_AGENT,
            'RIDER', 'DELIVERY', 'DELIVERY_AGENT', 'DELIVERYMAN' => self::COURIER,
            'DRIVER_AGENT' => self::DRIVER,
            'CUSTOMER_SERVICE', 'SUPPORT' => self::CUSTOMER_SUPPORT,
            'GUARD' => self::SECURITY,
            'DISPATCH', 'DISPATCH_AGENT' => self::DISPATCHER,
            'FINANCE', 'ACCOUNTANT' => self::FINANCE_OFFICER,
            default => self::tryFrom($normalized) ?? self::OPS_AGENT,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::BRANCH_MANAGER => 'Branch Manager',
            self::OPS_SUPERVISOR => 'Operations Supervisor',
            self::OPS_AGENT => 'Operations Agent',
            self::SORTATION_AGENT => 'Sortation Agent',
            self::COURIER => 'Courier',
            self::DRIVER => 'Driver',
            self::CUSTOMER_SUPPORT => 'Customer Support',
            self::SECURITY => 'Security',
            self::DISPATCHER => 'Dispatcher',
            self::FINANCE_OFFICER => 'Finance Officer',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ])
            ->values()
            ->all();
    }
}
