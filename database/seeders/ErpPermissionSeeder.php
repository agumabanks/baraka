<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class ErpPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'shipments' => [
                'read' => 'shipments_read',
                'write' => 'shipments_write',
            ],
            'bags' => [
                'read' => 'bags_read',
                'write' => 'bags_write',
            ],
            'legs' => [
                'read' => 'legs_read',
                'write' => 'legs_write',
            ],
            'scans' => [
                'read' => 'scans_read',
                'write' => 'scans_write',
            ],
            'routes' => [
                'read' => 'routes_read',
                'write' => 'routes_write',
            ],
            'epod' => [
                'read' => 'epod_read',
                'write' => 'epod_write',
            ],
            'control_board' => [
                'read' => 'control_board_read',
            ],
            'commodities' => [
                'read' => 'commodities_read',
                'write' => 'commodities_write',
            ],
            'hscodes' => [
                'read' => 'hscodes_read',
                'write' => 'hscodes_write',
            ],
            'customs_docs' => [
                'read' => 'customs_docs_read',
                'write' => 'customs_docs_write',
            ],
            'ics2' => [
                'read' => 'ics2_read',
                'write' => 'ics2_write',
            ],
            'dps' => [
                'read' => 'dps_read',
                'run' => 'dps_run',
            ],
            'ratecards' => [
                'read' => 'ratecards_read',
                'write' => 'ratecards_write',
            ],
            'invoices' => [
                'read' => 'invoices_read',
                'write' => 'invoices_write',
            ],
            'cod_receipts' => [
                'read' => 'cod_receipts_read',
                'write' => 'cod_receipts_write',
            ],
            'settlements' => [
                'read' => 'settlements_read',
                'write' => 'settlements_write',
            ],
            'api_keys' => [
                'read' => 'api_keys_read',
                'write' => 'api_keys_write',
            ],
            'webhooks' => [
                'read' => 'webhooks_read',
                'write' => 'webhooks_write',
            ],
            'global_search' => [
                'read' => 'global_search_read',
            ],
        ];

        foreach ($map as $attribute => $keywords) {
            $perm = new Permission();
            $perm->attribute = $attribute;
            $perm->keywords = $keywords;
            $perm->save();
        }
    }
}

