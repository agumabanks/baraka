<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnhancedRolesSeeder extends Seeder
{
    /**
     * Seed the 15-level DHL-grade role hierarchy
     */
    public function run(): void
    {
        $roles = [
            // SYSTEM LEVEL ROLES (role_level 1-10)
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'role_level' => 1,
                'role_category' => 'system',
                'branch_scope' => 'all',
                'description' => 'Full system access, multi-branch oversight, branch creation, global reporting',
                'is_system_role' => true,
                'capabilities' => ['*'], // All capabilities
                'permissions' => ['*'],
            ],
            [
                'name' => 'Regional Manager',
                'slug' => 'regional-manager',
                'role_level' => 10,
                'role_category' => 'system',
                'branch_scope' => 'region',
                'description' => 'Multi-branch supervision within assigned region, regional analytics, cross-branch coordination',
                'is_system_role' => true,
                'capabilities' => [
                    'branches.*', 'shipments.*', 'clients.*', 'reports.regional', 'analytics.regional',
                    'fleet.view', 'warehouse.view', 'finance.view', 'workforce.view',
                ],
                'permissions' => [],
            ],

            // BRANCH MANAGEMENT ROLES (role_level 20-30)
            [
                'name' => 'Branch Admin',
                'slug' => 'branch-admin',
                'role_level' => 20,
                'role_category' => 'branch',
                'branch_scope' => 'single',
                'description' => 'Complete single-branch operations management, staff management, financial control',
                'is_system_role' => true,
                'capabilities' => [
                    'shipments.*', 'clients.*', 'warehouse.*', 'fleet.*', 'finance.*', 'workforce.*',
                    'reports.branch', 'settings.branch', 'users.manage',
                ],
                'permissions' => [],
            ],
            [
                'name' => 'Branch Manager',
                'slug' => 'branch-manager',
                'role_level' => 25,
                'role_category' => 'branch',
                'branch_scope' => 'single',
                'description' => 'Operational oversight, staff supervision, performance reporting, customer relations',
                'is_system_role' => true,
                'capabilities' => [
                    'shipments.view', 'shipments.create', 'shipments.assign', 'shipments.update',
                    'clients.view', 'clients.create', 'clients.update',
                    'warehouse.view', 'fleet.view', 'finance.view', 'workforce.view',
                    'reports.branch',
                ],
                'permissions' => [],
            ],

            // DEPARTMENTAL MANAGERS (role_level 30-40)
            [
                'name' => 'Warehouse Manager',
                'slug' => 'warehouse-manager',
                'role_level' => 30,
                'role_category' => 'branch',
                'branch_scope' => 'single',
                'description' => 'Inventory control, storage optimization, receiving/dispatch operations, stock management',
                'is_system_role' => true,
                'capabilities' => [
                    'warehouse.*', 'inventory.*', 'receiving.*', 'dispatch.*', 'stock.*',
                    'shipments.view', 'reports.warehouse',
                ],
                'permissions' => [],
            ],
            [
                'name' => 'Fleet Manager',
                'slug' => 'fleet-manager',
                'role_level' => 30,
                'role_category' => 'branch',
                'branch_scope' => 'single',
                'description' => 'Vehicle fleet management, driver coordination, route planning, maintenance scheduling',
                'is_system_role' => true,
                'capabilities' => [
                    'fleet.*', 'vehicles.*', 'drivers.*', 'routes.*', 'trips.*', 'maintenance.*',
                    'shipments.view', 'reports.fleet',
                ],
                'permissions' => [],
            ],
            [
                'name' => 'Finance Officer',
                'slug' => 'finance-officer',
                'role_level' => 30,
                'role_category' => 'branch',
                'branch_scope' => 'single',
                'description' => 'Billing operations, payment processing, financial reporting, commission calculations',
                'is_system_role' => true,
                'capabilities' => [
                    'finance.*', 'invoices.*', 'payments.*', 'pricing.*', 'commissions.*',
                    'clients.view', 'shipments.view', 'reports.financial',
                ],
                'permissions' => [],
            ],
            [
                'name' => 'Operations Supervisor',
                'slug' => 'operations-supervisor',
                'role_level' => 35,
                'role_category' => 'branch',
                'branch_scope' => 'single',
                'description' => 'Daily operations oversight, staff task assignment, quality control, exception handling',
                'is_system_role' => true,
                'capabilities' => [
                    'shipments.view', 'shipments.assign', 'shipments.update', 'shipments.exceptions',
                    'warehouse.view', 'fleet.view', 'workforce.view', 'tasks.assign',
                ],
                'permissions' => [],
            ],

            // FRONTLINE STAFF (role_level 50-70)
            [
                'name' => 'Frontdesk Receptionist',
                'slug' => 'frontdesk-receptionist',
                'role_level' => 50,
                'role_category' => 'branch',
                'branch_scope' => 'single',
                'description' => 'Customer service, shipment intake, client onboarding, inquiry handling',
                'is_system_role' => true,
                'capabilities' => [
                    'shipments.create', 'shipments.view', 'clients.create', 'clients.view',
                    'support.tickets', 'tracking.query',
                ],
                'permissions' => [],
            ],
            [
                'name' => 'Warehouse Staff',
                'slug' => 'warehouse-staff',
                'role_level' => 60,
                'role_category' => 'field',
                'branch_scope' => 'single',
                'description' => 'Picking, packing, receiving, dispatch, inventory counts, quality checks',
                'is_system_role' => true,
                'capabilities' => [
                    'warehouse.receive', 'warehouse.pick', 'warehouse.pack', 'warehouse.dispatch',
                    'inventory.count', 'shipments.scan',
                ],
                'permissions' => [],
            ],
            [
                'name' => 'Driver',
                'slug' => 'driver',
                'role_level' => 60,
                'role_category' => 'field',
                'branch_scope' => 'single',
                'description' => 'Delivery execution, route optimization, POD collection, vehicle inspection',
                'is_system_role' => true,
                'capabilities' => [
                    'trips.view', 'trips.update', 'shipments.scan', 'shipments.deliver',
                    'pod.capture', 'vehicles.inspect',
                ],
                'permissions' => [],
            ],
            [
                'name' => 'Sales Representative',
                'slug' => 'sales-representative',
                'role_level' => 50,
                'role_category' => 'branch',
                'branch_scope' => 'single',
                'description' => 'Client acquisition, account management, quotation generation, relationship building',
                'is_system_role' => true,
                'capabilities' => [
                    'clients.create', 'clients.view', 'clients.update', 'quotations.create',
                    'shipments.view', 'reports.sales',
                ],
                'permissions' => [],
            ],
            [
                'name' => 'Customer Service Agent',
                'slug' => 'customer-service-agent',
                'role_level' => 55,
                'role_category' => 'branch',
                'branch_scope' => 'single',
                'description' => 'Support tickets, complaint resolution, tracking assistance, client communication',
                'is_system_role' => true,
                'capabilities' => [
                    'support.tickets', 'clients.view', 'shipments.view', 'tracking.query',
                    'communication.send',
                ],
                'permissions' => [],
            ],

            // CLIENT ROLES (role_level 100+)
            [
                'name' => 'Corporate Client',
                'slug' => 'corporate-client',
                'role_level' => 100,
                'role_category' => 'client',
                'branch_scope' => 'none',
                'description' => 'Full-featured business portal, multiple users, credit facilities, bulk operations',
                'is_system_role' => true,
                'capabilities' => [
                    'shipments.own.create', 'shipments.own.view', 'tracking.own', 'invoices.own.view',
                    'payments.own.create', 'reports.own', 'users.own.manage',
                ],
                'permissions' => [],
            ],
            [
                'name' => 'Individual Client',
                'slug' => 'individual-client',
                'role_level' => 110,
                'role_category' => 'client',
                'branch_scope' => 'none',
                'description' => 'Self-service portal, shipment tracking, payment processing, delivery management',
                'is_system_role' => true,
                'capabilities' => [
                    'shipments.own.create', 'shipments.own.view', 'tracking.own',
                    'payments.own.create', 'invoices.own.view',
                ],
                'permissions' => [],
            ],
        ];

        foreach ($roles as $roleData) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $roleData['slug']],
                array_merge($roleData, [
                    'active' => 1,
                    'capabilities' => json_encode($roleData['capabilities']),
                    'permissions' => json_encode($roleData['permissions']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✓ 15-level role hierarchy seeded successfully');

        // Seed role capabilities
        $this->seedRoleCapabilities();
    }

    /**
     * Seed the role capabilities reference table
     */
    protected function seedRoleCapabilities(): void
    {
        $capabilities = [
            // Shipments Module
            ['capability_key' => 'shipments.view', 'module' => 'shipments', 'capability_name' => 'View Shipments', 'privilege_level' => 50],
            ['capability_key' => 'shipments.create', 'module' => 'shipments', 'capability_name' => 'Create Shipments', 'privilege_level' => 40],
            ['capability_key' => 'shipments.update', 'module' => 'shipments', 'capability_name' => 'Update Shipments', 'privilege_level' => 40],
            ['capability_key' => 'shipments.delete', 'module' => 'shipments', 'capability_name' => 'Delete Shipments', 'privilege_level' => 20],
            ['capability_key' => 'shipments.assign', 'module' => 'shipments', 'capability_name' => 'Assign Shipments', 'privilege_level' => 35],
            ['capability_key' => 'shipments.deliver', 'module' => 'shipments', 'capability_name' => 'Deliver Shipments', 'privilege_level' => 60],
            ['capability_key' => 'shipments.scan', 'module' => 'shipments', 'capability_name' => 'Scan Shipments', 'privilege_level' => 60],
            ['capability_key' => 'shipments.exceptions', 'module' => 'shipments', 'capability_name' => 'Handle Exceptions', 'privilege_level' => 35],
            ['capability_key' => 'shipments.*', 'module' => 'shipments', 'capability_name' => 'All Shipment Operations', 'privilege_level' => 20],

            // Groupage/Consolidation Module
            ['capability_key' => 'consolidation.create', 'module' => 'consolidation', 'capability_name' => 'Create Consolidations', 'privilege_level' => 30],
            ['capability_key' => 'consolidation.manage', 'module' => 'consolidation', 'capability_name' => 'Manage Consolidations', 'privilege_level' => 30],
            ['capability_key' => 'consolidation.deconsolidate', 'module' => 'consolidation', 'capability_name' => 'Deconsolidate Shipments', 'privilege_level' => 30],

            // Clients/CRM Module
            ['capability_key' => 'clients.view', 'module' => 'clients', 'capability_name' => 'View Clients', 'privilege_level' => 50],
            ['capability_key' => 'clients.create', 'module' => 'clients', 'capability_name' => 'Create Clients', 'privilege_level' => 50],
            ['capability_key' => 'clients.update', 'module' => 'clients', 'capability_name' => 'Update Clients', 'privilege_level' => 40],
            ['capability_key' => 'clients.delete', 'module' => 'clients', 'capability_name' => 'Delete Clients', 'privilege_level' => 20],
            ['capability_key' => 'clients.*', 'module' => 'clients', 'capability_name' => 'All Client Operations', 'privilege_level' => 20],

            // Warehouse Module
            ['capability_key' => 'warehouse.view', 'module' => 'warehouse', 'capability_name' => 'View Warehouse', 'privilege_level' => 50],
            ['capability_key' => 'warehouse.receive', 'module' => 'warehouse', 'capability_name' => 'Receive Inventory', 'privilege_level' => 60],
            ['capability_key' => 'warehouse.pick', 'module' => 'warehouse', 'capability_name' => 'Pick Items', 'privilege_level' => 60],
            ['capability_key' => 'warehouse.pack', 'module' => 'warehouse', 'capability_name' => 'Pack Items', 'privilege_level' => 60],
            ['capability_key' => 'warehouse.dispatch', 'module' => 'warehouse', 'capability_name' => 'Dispatch Items', 'privilege_level' => 60],
            ['capability_key' => 'warehouse.*', 'module' => 'warehouse', 'capability_name' => 'All Warehouse Operations', 'privilege_level' => 30],

            // Inventory Module
            ['capability_key' => 'inventory.view', 'module' => 'inventory', 'capability_name' => 'View Inventory', 'privilege_level' => 50],
            ['capability_key' => 'inventory.adjust', 'module' => 'inventory', 'capability_name' => 'Adjust Inventory', 'privilege_level' => 30],
            ['capability_key' => 'inventory.count', 'module' => 'inventory', 'capability_name' => 'Count Inventory', 'privilege_level' => 60],
            ['capability_key' => 'inventory.*', 'module' => 'inventory', 'capability_name' => 'All Inventory Operations', 'privilege_level' => 30],

            // Fleet Module
            ['capability_key' => 'fleet.view', 'module' => 'fleet', 'capability_name' => 'View Fleet', 'privilege_level' => 50],
            ['capability_key' => 'fleet.manage', 'module' => 'fleet', 'capability_name' => 'Manage Fleet', 'privilege_level' => 30],
            ['capability_key' => 'vehicles.view', 'module' => 'fleet', 'capability_name' => 'View Vehicles', 'privilege_level' => 50],
            ['capability_key' => 'vehicles.manage', 'module' => 'fleet', 'capability_name' => 'Manage Vehicles', 'privilege_level' => 30],
            ['capability_key' => 'vehicles.inspect', 'module' => 'fleet', 'capability_name' => 'Inspect Vehicles', 'privilege_level' => 60],
            ['capability_key' => 'drivers.view', 'module' => 'fleet', 'capability_name' => 'View Drivers', 'privilege_level' => 50],
            ['capability_key' => 'drivers.manage', 'module' => 'fleet', 'capability_name' => 'Manage Drivers', 'privilege_level' => 30],
            ['capability_key' => 'trips.view', 'module' => 'fleet', 'capability_name' => 'View Trips', 'privilege_level' => 50],
            ['capability_key' => 'trips.create', 'module' => 'fleet', 'capability_name' => 'Create Trips', 'privilege_level' => 30],
            ['capability_key' => 'trips.update', 'module' => 'fleet', 'capability_name' => 'Update Trips', 'privilege_level' => 60],
            ['capability_key' => 'fleet.*', 'module' => 'fleet', 'capability_name' => 'All Fleet Operations', 'privilege_level' => 30],

            // Finance Module
            ['capability_key' => 'finance.view', 'module' => 'finance', 'capability_name' => 'View Finance', 'privilege_level' => 50],
            ['capability_key' => 'invoices.view', 'module' => 'finance', 'capability_name' => 'View Invoices', 'privilege_level' => 50],
            ['capability_key' => 'invoices.create', 'module' => 'finance', 'capability_name' => 'Create Invoices', 'privilege_level' => 30],
            ['capability_key' => 'invoices.approve', 'module' => 'finance', 'capability_name' => 'Approve Invoices', 'privilege_level' => 25],
            ['capability_key' => 'payments.view', 'module' => 'finance', 'capability_name' => 'View Payments', 'privilege_level' => 50],
            ['capability_key' => 'payments.create', 'module' => 'finance', 'capability_name' => 'Process Payments', 'privilege_level' => 30],
            ['capability_key' => 'pricing.view', 'module' => 'finance', 'capability_name' => 'View Pricing', 'privilege_level' => 50],
            ['capability_key' => 'pricing.manage', 'module' => 'finance', 'capability_name' => 'Manage Pricing', 'privilege_level' => 20],
            ['capability_key' => 'finance.*', 'module' => 'finance', 'capability_name' => 'All Finance Operations', 'privilege_level' => 20],

            // Workforce/HR Module
            ['capability_key' => 'workforce.view', 'module' => 'workforce', 'capability_name' => 'View Workforce', 'privilege_level' => 40],
            ['capability_key' => 'workforce.manage', 'module' => 'workforce', 'capability_name' => 'Manage Workforce', 'privilege_level' => 20],
            ['capability_key' => 'users.view', 'module' => 'workforce', 'capability_name' => 'View Users', 'privilege_level' => 40],
            ['capability_key' => 'users.manage', 'module' => 'workforce', 'capability_name' => 'Manage Users', 'privilege_level' => 20],
            ['capability_key' => 'attendance.manage', 'module' => 'workforce', 'capability_name' => 'Manage Attendance', 'privilege_level' => 30],
            ['capability_key' => 'workforce.*', 'module' => 'workforce', 'capability_name' => 'All Workforce Operations', 'privilege_level' => 20],

            // Reporting Module
            ['capability_key' => 'reports.branch', 'module' => 'reports', 'capability_name' => 'Branch Reports', 'privilege_level' => 35],
            ['capability_key' => 'reports.regional', 'module' => 'reports', 'capability_name' => 'Regional Reports', 'privilege_level' => 10],
            ['capability_key' => 'reports.financial', 'module' => 'reports', 'capability_name' => 'Financial Reports', 'privilege_level' => 30],
            ['capability_key' => 'reports.fleet', 'module' => 'reports', 'capability_name' => 'Fleet Reports', 'privilege_level' => 30],
            ['capability_key' => 'reports.warehouse', 'module' => 'reports', 'capability_name' => 'Warehouse Reports', 'privilege_level' => 30],
            ['capability_key' => 'reports.*', 'module' => 'reports', 'capability_name' => 'All Reports', 'privilege_level' => 20],

            // Settings Module
            ['capability_key' => 'settings.branch', 'module' => 'settings', 'capability_name' => 'Branch Settings', 'privilege_level' => 20],
            ['capability_key' => 'settings.system', 'module' => 'settings', 'capability_name' => 'System Settings', 'privilege_level' => 1],

            // Support Module
            ['capability_key' => 'support.tickets', 'module' => 'support', 'capability_name' => 'Handle Support Tickets', 'privilege_level' => 55],

            // Tracking Module
            ['capability_key' => 'tracking.query', 'module' => 'tracking', 'capability_name' => 'Query Tracking', 'privilege_level' => 55],
            ['capability_key' => 'tracking.own', 'module' => 'tracking', 'capability_name' => 'Track Own Shipments', 'privilege_level' => 100],
        ];

        foreach ($capabilities as $capability) {
            DB::table('role_capabilities')->updateOrInsert(
                ['capability_key' => $capability['capability_key']],
                array_merge($capability, [
                    'description' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✓ Role capabilities seeded successfully');
    }
}
