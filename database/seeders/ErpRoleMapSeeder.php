<?php

namespace Database\Seeders;

use App\Models\Backend\Role;
use Illuminate\Database\Seeder;

class ErpRoleMapSeeder extends Seeder
{
    public function run(): void
    {
        $rolePerms = [
            'hq_admin' => $this->allPerms(),
            'branch_ops_manager' => [
                'shipments_read', 'shipments_write',
                'bags_read', 'bags_write',
                'legs_read', 'legs_write',
                'scans_read', 'scans_write',
                'routes_read', 'routes_write',
                'epod_read', 'epod_write',
                'control_board_read',
                'commodities_read', 'commodities_write',
                'hscodes_read', 'hscodes_write',
                'customs_docs_read', 'customs_docs_write',
                'ics2_read', 'ics2_write',
                'dps_read',
                'ratecards_read',
                'invoices_read',
                'cod_receipts_read',
                'settlements_read',
                'global_search_read',
            ],
            'support' => [
                'shipments_read', 'scans_read', 'epod_read', 'control_board_read', 'global_search_read', 'dps_read',
            ],
            'finance' => [
                'ratecards_read', 'invoices_read', 'cod_receipts_read', 'settlements_read', 'shipments_read', 'global_search_read',
            ],
            'driver' => [
                'routes_read', 'scans_read', 'scans_write', 'epod_read', 'shipments_read',
            ],
            'customer' => [
                'global_search_read',
            ],
        ];

        foreach ($rolePerms as $slug => $perms) {
            $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucwords(str_replace('_', ' ', $slug))]);
            $role->permissions = array_values(array_unique(array_merge((array) ($role->permissions ?? []), $perms)));
            $role->save();
        }
    }

    private function allPerms(): array
    {
        return [
            'shipments_read', 'shipments_write', 'bags_read', 'bags_write', 'legs_read', 'legs_write',
            'scans_read', 'scans_write', 'routes_read', 'routes_write', 'epod_read', 'epod_write',
            'control_board_read', 'commodities_read', 'commodities_write', 'hscodes_read', 'hscodes_write',
            'customs_docs_read', 'customs_docs_write', 'ics2_read', 'ics2_write', 'dps_read', 'dps_run',
            'ratecards_read', 'ratecards_write', 'invoices_read', 'invoices_write', 'cod_receipts_read', 'cod_receipts_write',
            'settlements_read', 'settlements_write', 'api_keys_read', 'api_keys_write', 'webhooks_read', 'webhooks_write',
            'global_search_read',
        ];
    }
}
