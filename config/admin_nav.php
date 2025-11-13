<?php

return [
    // Collapsible buckets in the admin sidebar
    'buckets' => [
        'command-center' => [
            'label_trans_key' => 'menus.command_center',
            'children' => [
                [
                    'key' => 'dashboard-home',
                    'label_trans_key' => 'menus.dashboard_home',
                    'icon' => 'fa fa-home',
                    'url' => '/dashboard',
                    'active_patterns' => ['dashboard.index'],
                    'permission_check' => null,
                ],
                [
                    'key' => 'workflow-board',
                    'label_trans_key' => 'menus.workflow_board',
                    'icon' => 'fa fa-network-wired',
                    'url' => '/workflow',
                    'active_patterns' => ['workflow.*'],
                    'permission_check' => null,
                ],
                [
                    'key' => 'reports-center',
                    'label_trans_key' => 'menus.reports_center',
                    'icon' => 'fa fa-file-text',
                    'url' => '/reports',
                    'active_patterns' => ['reports.*'],
                    'permission_check' => null,
                ],
                [
                    'key' => 'advanced-analytics',
                    'label' => 'Real-time Analytics',
                    'icon' => 'fa fa-chart-line',
                    'url' => '/analytics',
                    'active_patterns' => ['analytics.*'],
                    'permission_check' => null,
                ],
                [
                    'key' => 'live-tracking',
                    'label_trans_key' => 'menus.live_tracking',
                    'icon' => 'fa fa-map-marker-alt',
                    'url' => '/tracking',
                    'active_patterns' => ['tracking'],
                    'permission_check' => null,
                ]
            ],
        ],

        'navigation' => [
            'label_trans_key' => 'menus.navigation',
            'children' => [
                [
                    'key' => 'merchant-management',
                    'label_trans_key' => 'menus.merchant_management',
                    'icon' => 'fa fa-store',
                    'active_patterns' => ['admin.merchants.*', 'admin.merchant.payments.*'],
                    'children' => [
                        [
                            'key' => 'merchants',
                            'label_trans_key' => 'menus.merchants',
                            'icon' => 'fa fa-user',
                            'url' => '/merchants',
                            'active_patterns' => ['admin.merchants.*'],
                            'permission_check' => null,
                        ],
                        [
                            'key' => 'merchant-payments',
                            'label_trans_key' => 'menus.payments',
                            'icon' => 'fa fa-dollar-sign',
                            'url' => '/merchant/payments',
                            'active_patterns' => ['admin.merchant.payments.*'],
                            'permission_check' => null,
                        ],
                    ],
                ],
                [
                    'key' => 'todo-list',
                    'label_trans_key' => 'menus.todo_list',
                    'icon' => 'fa fa-check-square',
                    'url' => '/todo',
                    'active_patterns' => ['admin.todo'],
                    'permission_check' => null,
                ],
                [
                    'key' => 'support-tickets',
                    'label_trans_key' => 'menus.support',
                    'icon' => 'fa fa-message-square',
                    'url' => '/support',
                    'active_patterns' => ['admin.support'],
                    'permission_check' => null,
                ]
            ],
        ],

        'branch-management' => [
            'label_trans_key' => 'menus.branch_management',
            'children' => [
                [
                    'key' => 'branch-management-hub',
                    'label' => 'Branch Management',
                    'icon' => 'fa fa-building',
                    'url' => '/branches',
                    'expanded' => true,
                    'active_patterns' => ['admin.branches.*'],
                    'permission_check' => null,
                    'children' => [
                        [
                            'key' => 'branches-overview',
                            'label' => 'Overview',
                            'icon' => 'fa fa-table-columns',
                            'url' => '/branches',
                            'active_patterns' => ['admin.branches.*'],
                            'permission_check' => null,
                        ],
                        [
                            'key' => 'branch-managers',
                            'label_trans_key' => 'menus.branch_managers',
                            'icon' => 'fa fa-user-tie',
                            'url' => '/branch-managers',
                            'active_patterns' => ['admin.branch-managers.*'],
                            'permission_check' => null,
                        ],
                        [
                            'key' => 'branch-workers',
                            'label_trans_key' => 'menus.branch_workers',
                            'icon' => 'fa fa-users',
                            'url' => '/branch-workers',
                            'active_patterns' => ['admin.branch-workers.*'],
                            'permission_check' => null,
                        ],
                        [
                            'key' => 'local-clients',
                            'label_trans_key' => 'menus.local_clients',
                            'icon' => 'fa fa-user-friends',
                            'url' => '/branches/clients',
                            'active_patterns' => ['admin.branches.clients*'],
                            'permission_check' => null,
                        ],
                        [
                            'key' => 'branch-shipments',
                            'label_trans_key' => 'menus.branch_shipments',
                            'icon' => 'fa fa-truck',
                            'url' => '/branches/shipments',
                            'active_patterns' => ['admin.branches.shipments*'],
                            'permission_check' => null,
                        ],
                        [
                            'key' => 'branch-hierarchy',
                            'label_trans_key' => 'menus.branch_hierarchy',
                            'icon' => 'fa fa-sitemap',
                            'url' => '/branches/hierarchy',
                            'active_patterns' => ['admin.branches.hierarchy*'],
                            'permission_check' => null,
                        ],
                    ],
                ],
            ],
        ],

        'operations' => [
            'label_trans_key' => 'menus.operations',
            'children' => [
                [
                    'key' => 'bookings',
                    'label_trans_key' => 'menus.bookings',
                    'icon' => 'fa fa-clipboard-plus',
                    'url' => '/bookings',
                    'active_patterns' => ['admin.booking.*'],
                    'permission_check' => null,
                ],
                [
                    'key' => 'shipments',
                    'label_trans_key' => 'menus.shipments',
                    'icon' => 'fa fa-box',
                    'url' => '/shipments',
                    'active_patterns' => ['admin.shipments.*'],
                    'permission_check' => null,
                ],
                [
                    'key' => 'bags',
                    'label_trans_key' => 'menus.bags',
                    'icon' => 'fa fa-layer-group',
                    'url' => '/bags',
                    'active_patterns' => ['admin.bags.*'],
                    'permission_check' => null,
                ],
                [
                    'key' => 'scan-events',
                    'label_trans_key' => 'menus.scan_events',
                    'icon' => 'fa fa-barcode',
                    'url' => '/scans',
                    'active_patterns' => ['admin.scans.*'],
                    'permission_check' => null,
                ],
                [
                    'key' => 'routes',
                    'label_trans_key' => 'menus.routes_stops',
                    'icon' => 'fa fa-truck',
                    'url' => '/routes',
                    'active_patterns' => ['admin.routes.*'],
                    'permission_check' => null,
                ],
            ],
        ],

        'sales' => [
            'label_trans_key' => 'menus.sales',
            'children' => [
                [
                    'key' => 'customers',
                    'label_trans_key' => 'menus.customers',
                    'icon' => 'fa fa-users',
                    'active_patterns' => ['admin.customers.*'],
                    'children' => [
                        [
                            'key' => 'customers-index',
                            'label_trans_key' => 'menus.customers_all',
                            'icon' => 'fa fa-users',
                            'url' => '/customers',
                            'active_patterns' => ['admin.customers.index'],
                            'permission_check' => null,
                        ],
                        [
                            'key' => 'customers-create',
                            'label_trans_key' => 'menus.customers_create',
                            'icon' => 'fa fa-user-plus',
                            'url' => '/customers/create',
                            'active_patterns' => ['admin.customers.create'],
                            'permission_check' => null,
                        ],
                    ],
                ],
                [
                    'key' => 'quotations',
                    'label_trans_key' => 'menus.quotations',
                    'icon' => 'fa fa-file-signature',
                    'url' => '/quotations',
                    'active_patterns' => ['admin.quotations.*'],
                    'permission_check' => null,
                ],
                [
                    'key' => 'contracts',
                    'label_trans_key' => 'menus.contracts',
                    'icon' => 'fa fa-handshake',
                    'url' => '/contracts',
                    'active_patterns' => ['admin.contracts.*'],
                    'permission_check' => null,
                ],
                [
                    'key' => 'address-book',
                    'label_trans_key' => 'menus.address_book',
                    'icon' => 'fa fa-address-book',
                    'route' => 'admin.address-book.index',
                    'active_patterns' => ['admin.address-book.*'],
                    'permission_check' => null,
                ],
            ],
        ],

        'finance' => [
            'label_trans_key' => 'menus.finance',
            'children' => [
                [
                    'key' => 'invoices',
                    'label_trans_key' => 'menus.invoices',
                    'icon' => 'fa fa-file-invoice-dollar',
                    'route' => 'admin.invoices.index',
                    'active_patterns' => ['admin.invoices.*'],
                    'permission_check' => null,
                ],
                [
                    'key' => 'payments',
                    'label_trans_key' => 'menus.payments',
                    'icon' => 'fa fa-credit-card',
                    'route' => 'admin.payments.index',
                    'active_patterns' => ['admin.payments.*'],
                    'permission_check' => null,
                ],
                [
                    'key' => 'settlements',
                    'label_trans_key' => 'menus.settlements',
                    'icon' => 'fa fa-exchange-alt',
                    'route' => 'admin.settlements.index',
                    'active_patterns' => ['admin.settlements.*'],
                    'permission_check' => null,
                ],
            ],
        ],

        'tools' => [
            'label_trans_key' => 'menus.tools',
            'children' => [
                [
                    'key' => 'global-search',
                    'label_trans_key' => 'menus.search',
                    'icon' => 'fa fa-search',
                    'route' => 'admin.search',
                    'active_patterns' => ['admin.search'],
                    'permission_check' => null,
                ],
                [
                    'key' => 'reports',
                    'label_trans_key' => 'reports.title',
                    'icon' => 'fa fa-file-text',
                    'route' => 'admin.reports.index',
                    'active_patterns' => ['admin.reports.*'],
                    'permission_check' => null,
                ],
                [
                    'key' => 'logs',
                    'label_trans_key' => 'menus.active_logs',
                    'icon' => 'fa fa-history',
                    'route' => 'logs.index',
                    'active_patterns' => ['logs.*'],
                    'permission_check' => null,
                ],
            ],
        ],

        'settings' => [
            'label_trans_key' => 'menus.settings',
            'children' => [
                [
                    'key' => 'user-role',
                    'label_trans_key' => 'menus.user_role',
                    'icon' => 'fa fa-users',
                    'active_patterns' => ['users.*', 'roles.*'],
                    'children' => [
                        [
                            'key' => 'users',
                            'label_trans_key' => 'menus.users',
                            'icon' => 'fa fa-user',
                            'route' => 'users.index',
                            'active_patterns' => ['users.*'],
                            'permission_check' => null,
                        ],
                        [
                            'key' => 'roles',
                            'label_trans_key' => 'menus.roles',
                            'icon' => 'fa fa-user-shield',
                            'route' => 'roles.index',
                            'active_patterns' => ['roles.*'],
                            'permission_check' => null,
                        ],
                    ],
                ],
                [
                    'key' => 'general-settings',
                    'label_trans_key' => 'menus.general_settings',
                    'icon' => 'fa fa-cogs',
                    'url' => '/general-settings/index',
                    'active_patterns' => ['general-settings.*'],
                    'permission_check' => null,
                ],
            ],
        ],
    ],
];
