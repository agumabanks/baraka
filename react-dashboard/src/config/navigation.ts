/**
 * Complete 360° ERP Navigation Configuration
 * All 10 Phases of Baraka Courier ERP System
 */

import type { NavigationConfig } from '../types/navigation';

export const navigationConfig: NavigationConfig = {
  logoUrl: '/images/default/logo1.png',
  appName: 'Baraka',
  buckets: [
    {
      id: 'dashboard',
      label: 'COMMAND CENTER',
      visible: true,
      items: [
        {
          id: 'dashboard-home',
          label: 'Dashboard Home',
          icon: 'LayoutDashboard',
          path: '/dashboard',
          visible: true
        },
        {
          id: 'workflow-board',
          label: 'Workflow Board',
          icon: 'Network',
          path: '/workflow',
          visible: true
        },
        {
          id: 'reports-center',
          label: 'Reports Center',
          icon: 'FileBarChart',
          path: '/reports',
          visible: true
        }
      ]
    },
    {
      id: 'navigation',
      label: 'NAVIGATION',
      visible: true,
      items: [
        {
          id: 'merchant-management',
          label: 'Merchant Management',
          icon: 'Store',
          expanded: false,
          children: [
            {
              id: 'merchants-all',
              label: 'Merchants',
              icon: 'ShoppingBag',
              path: '/merchants',
              visible: true
            },
            {
              id: 'merchant-payments',
              label: 'Payments',
              icon: 'DollarSign',
              path: '/merchant/payments',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'sales',
          label: 'Sales',
          icon: 'TrendingUp',
          expanded: false,
          children: [
            {
              id: 'customers-all',
              label: 'Customers',
              icon: 'Users',
              path: '/customers',
              visible: true
            },
            {
              id: 'quotations',
              label: 'Quotations',
              icon: 'FileSignature',
              path: '/quotations',
              visible: true
            },
            {
              id: 'contracts',
              label: 'Contracts',
              icon: 'ScrollText',
              path: '/contracts',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'todo-list',
          label: 'To-do List',
          icon: 'CheckSquare',
          path: '/todo',
          visible: true
        },
        {
          id: 'support-tickets',
          label: 'Support Tickets',
          icon: 'MessageSquare',
          path: '/support',
          visible: true
        }
      ]
    },
    {
      id: 'branch-management',
      label: 'BRANCH MANAGEMENT',
      visible: true,
      items: [
        {
          id: 'branch-management-menu',
          label: 'Branch Management',
          icon: 'Building2',
          expanded: false,
          children: [
            {
              id: 'branches-all',
              label: 'Branches',
              icon: 'Building',
              path: '/branches',
              visible: true
            },
            {
              id: 'branch-managers',
              label: 'Branch Managers',
              icon: 'UserTie',
              path: '/branch-managers',
              visible: true
            },
            {
              id: 'branch-workers',
              label: 'Branch Workers',
              icon: 'UserCog',
              path: '/branch-workers',
              visible: true
            },
            {
              id: 'local-clients',
              label: 'Local Clients',
              icon: 'Users',
              path: '/branches/clients',
              visible: true
            },
            {
              id: 'branch-shipments',
              label: 'Shipments by Branch',
              icon: 'Truck',
              path: '/branches/shipments',
              visible: true
            },
            {
              id: 'branches-hierarchy',
              label: 'Branch Hierarchy',
              icon: 'GitBranch',
              path: '/branches/hierarchy',
              visible: true
            }
          ],
          visible: true
        }
      ]
    },
    {
      id: 'operations',
      label: 'OPERATIONS',
      visible: true,
      items: [
        {
          id: 'control-center',
          label: 'Control Center',
          icon: 'Command',
          children: [
            {
              id: 'dispatch-board',
              label: 'Dispatch Board',
              icon: 'Monitor',
              path: '/operations/dispatch',
              visible: true
            },
            {
              id: 'exception-tower',
              label: 'Exception Tower',
              icon: 'AlertTriangle',
              path: '/operations/exceptions',
              visible: true
            },
            {
              id: 'control-tower',
              label: 'Control Tower',
              icon: 'Radio',
              path: '/operations/control-tower',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'bookings',
          label: 'Bookings',
          icon: 'ClipboardList',
          path: '/bookings',
          visible: true
        },
        {
          id: 'shipments',
          label: 'Shipments',
          icon: 'Box',
          children: [
            {
              id: 'shipments-all',
              label: 'All Shipments',
              icon: 'Package',
              path: '/shipments',
              visible: true
            },
            {
              id: 'shipments-workflow',
              label: 'Workflow Status',
              icon: 'Workflow',
              path: '/shipments/workflow',
              visible: true
            },
            {
              id: 'shipments-tracking',
              label: 'Tracking',
              icon: 'MapPin',
              path: '/shipments/tracking',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'parcels',
          label: 'Parcels',
          icon: 'Package',
          path: '/parcels',
          visible: true
        },
        {
          id: 'bags',
          label: 'Bags',
          icon: 'Layers',
          path: '/bags',
          visible: true
        },
        {
          id: 'linehaul',
          label: 'Linehaul',
          icon: 'Route',
          children: [
            {
              id: 'transport-legs',
              label: 'Transport Legs',
              icon: 'Truck',
              path: '/linehaul/legs',
              visible: true
            },
            {
              id: 'manifests',
              label: 'Manifests',
              icon: 'List',
              path: '/linehaul/manifests',
              visible: true
            },
            {
              id: 'ecmr',
              label: 'e-CMR',
              icon: 'FileText',
              path: '/linehaul/ecmr',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'routes',
          label: 'Routes & Optimization',
          icon: 'Map',
          children: [
            {
              id: 'routes-all',
              label: 'All Routes',
              icon: 'Route',
              path: '/routes',
              visible: true
            },
            {
              id: 'route-optimization',
              label: 'Route Optimizer',
              icon: 'Zap',
              path: '/routes/optimize',
              visible: true
            },
            {
              id: 'stops',
              label: 'Stops',
              icon: 'MapPin',
              path: '/routes/stops',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'scan-events',
          label: 'Scan Events',
          icon: 'Scan',
          path: '/operations/scans',
          visible: true
        }
      ]
    },
    {
      id: 'assets',
      label: 'ASSET MANAGEMENT',
      visible: true,
      items: [
        {
          id: 'asset-management',
          label: 'Assets',
          icon: 'Warehouse',
          children: [
            {
              id: 'assets-all',
              label: 'All Assets',
              icon: 'Box',
              path: '/assets',
              visible: true
            },
            {
              id: 'asset-status',
              label: 'Asset Status',
              icon: 'Activity',
              path: '/assets/status',
              visible: true
            },
            {
              id: 'asset-utilization',
              label: 'Utilization',
              icon: 'Gauge',
              path: '/assets/utilization',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'vehicles',
          label: 'Vehicles',
          icon: 'Truck',
          children: [
            {
              id: 'vehicles-all',
              label: 'All Vehicles',
              icon: 'Car',
              path: '/vehicles',
              visible: true
            },
            {
              id: 'vehicle-maintenance',
              label: 'Maintenance',
              icon: 'Wrench',
              path: '/vehicles/maintenance',
              visible: true
            },
            {
              id: 'vehicle-fuel',
              label: 'Fuel Management',
              icon: 'Fuel',
              path: '/vehicles/fuel',
              visible: true
            },
            {
              id: 'vehicle-accidents',
              label: 'Accidents',
              icon: 'AlertCircle',
              path: '/vehicles/accidents',
              visible: true
            }
          ],
          visible: true
        }
      ]
    },

    {
      id: 'finance',
      label: 'FINANCE & BILLING',
      visible: true,
      items: [
        {
          id: 'rate-cards',
          label: 'Rate Cards',
          icon: 'Calculator',
          path: '/finance/rate-cards',
          visible: true
        },
        {
          id: 'invoices',
          label: 'Invoices',
          icon: 'Receipt',
          children: [
            {
              id: 'invoices-all',
              label: 'All Invoices',
              icon: 'FileText',
              path: '/finance/invoices',
              visible: true
            },
            {
              id: 'invoices-pending',
              label: 'Pending',
              icon: 'Clock',
              path: '/finance/invoices/pending',
              visible: true
            },
            {
              id: 'invoices-paid',
              label: 'Paid',
              icon: 'CheckCircle',
              path: '/finance/invoices/paid',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'cod-management',
          label: 'COD Management',
          icon: 'Banknote',
          children: [
            {
              id: 'cod-dashboard',
              label: 'COD Dashboard',
              icon: 'LayoutDashboard',
              path: '/finance/cod',
              visible: true
            },
            {
              id: 'cod-reconciliation',
              label: 'Reconciliation',
              icon: 'RefreshCw',
              path: '/finance/cod/reconciliation',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'settlements',
          label: 'Settlements',
          icon: 'ArrowLeftRight',
          children: [
            {
              id: 'settlements-branch',
              label: 'Branch Settlements',
              icon: 'Building',
              path: '/finance/settlements/branch',
              visible: true
            },
            {
              id: 'settlements-driver',
              label: 'Driver Settlements',
              icon: 'User',
              path: '/finance/settlements/driver',
              visible: true
            },
            {
              id: 'settlements-cycles',
              label: 'Settlement Cycles',
              icon: 'Calendar',
              path: '/finance/settlements/cycles',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'payments',
          label: 'Payments',
          icon: 'CreditCard',
          path: '/finance/payments',
          visible: true
        },
        {
          id: 'payroll',
          label: 'Payroll',
          icon: 'Wallet',
          children: [
            {
              id: 'payroll-generate',
              label: 'Generate Salary',
              icon: 'Calculator',
              path: '/payroll/generate',
              visible: true
            },
            {
              id: 'payroll-records',
              label: 'Salary Records',
              icon: 'List',
              path: '/payroll',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'financial-reports',
          label: 'Financial Reports',
          icon: 'FileBarChart',
          path: '/finance/reports',
          visible: true
        }
      ]
    },
    {
      id: 'compliance',
      label: 'COMPLIANCE & RISK',
      visible: true,
      items: [
        {
          id: 'kyc',
          label: 'KYC Verification',
          icon: 'ShieldCheck',
          path: '/compliance/kyc',
          visible: true
        },
        {
          id: 'dangerous-goods',
          label: 'Dangerous Goods',
          icon: 'AlertOctagon',
          children: [
            {
              id: 'dg-classification',
              label: 'Classification',
              icon: 'Tag',
              path: '/compliance/dg/classification',
              visible: true
            },
            {
              id: 'dg-shipments',
              label: 'DG Shipments',
              icon: 'Package',
              path: '/compliance/dg/shipments',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'customs',
          label: 'Customs & Trade',
          icon: 'Globe',
          children: [
            {
              id: 'customs-declarations',
              label: 'Declarations',
              icon: 'FileText',
              path: '/compliance/customs/declarations',
              visible: true
            },
            {
              id: 'customs-docs',
              label: 'Documents',
              icon: 'Files',
              path: '/compliance/customs/docs',
              visible: true
            },
            {
              id: 'hs-codes',
              label: 'HS Codes',
              icon: 'Hash',
              path: '/compliance/customs/hs-codes',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'fraud-detection',
          label: 'Fraud Detection',
          icon: 'ShieldAlert',
          children: [
            {
              id: 'fraud-dashboard',
              label: 'Dashboard',
              icon: 'LayoutDashboard',
              path: '/compliance/fraud',
              visible: true
            },
            {
              id: 'fraud-cases',
              label: 'Cases',
              icon: 'FileWarning',
              path: '/compliance/fraud/cases',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'risk-assessment',
          label: 'Risk Assessment',
          icon: 'Scale',
          path: '/compliance/risk',
          visible: true
        },
        {
          id: 'regulatory',
          label: 'Regulatory Reporting',
          icon: 'FileBarChart2',
          path: '/compliance/regulatory',
          visible: true
        },
        {
          id: 'data-privacy',
          label: 'Data Privacy (GDPR)',
          icon: 'Lock',
          path: '/compliance/privacy',
          visible: true
        }
      ]
    },
    {
      id: 'integrations',
      label: 'INTEGRATIONS',
      visible: true,
      items: [
        {
          id: 'api-keys',
          label: 'API Keys',
          icon: 'Key',
          path: '/integrations/api-keys',
          visible: true
        },
        {
          id: 'webhooks',
          label: 'Webhooks',
          icon: 'Webhook',
          children: [
            {
              id: 'webhooks-endpoints',
              label: 'Endpoints',
              icon: 'Link',
              path: '/integrations/webhooks',
              visible: true
            },
            {
              id: 'webhooks-logs',
              label: 'Delivery Logs',
              icon: 'FileText',
              path: '/integrations/webhooks/logs',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'edi',
          label: 'EDI Integration',
          icon: 'Share2',
          path: '/integrations/edi',
          visible: true
        },
        {
          id: 'marketplace',
          label: 'Marketplace Connectors',
          icon: 'ShoppingCart',
          path: '/integrations/marketplace',
          visible: true
        },
        {
          id: 'third-party',
          label: 'Third-Party Apps',
          icon: 'Puzzle',
          path: '/integrations/third-party',
          visible: true
        },
        {
          id: 'data-sync',
          label: 'Data Sync',
          icon: 'RefreshCw',
          path: '/integrations/sync',
          visible: true
        },
        {
          id: 'integration-monitor',
          label: 'Monitoring',
          icon: 'Activity',
          path: '/integrations/monitoring',
          visible: true
        }
      ]
    },
    {
      id: 'tools',
      label: 'TOOLS & UTILITIES',
      visible: true,
      items: [
        {
          id: 'search',
          label: 'Global Search',
          icon: 'Search',
          path: '/search',
          visible: true
        },
        {
          id: 'todo',
          label: 'To-Do List',
          icon: 'CheckSquare',
          path: '/todo',
          visible: true
        },
        {
          id: 'support',
          label: 'Support Tickets',
          icon: 'MessageSquare',
          path: '/support',
          visible: true
        },
        {
          id: 'reports',
          label: 'Reports',
          icon: 'BarChart',
          children: [
            {
              id: 'reports-operations',
              label: 'Operations Reports',
              icon: 'Activity',
              path: '/reports/operations',
              visible: true
            },
            {
              id: 'reports-financial',
              label: 'Financial Reports',
              icon: 'DollarSign',
              path: '/reports/financial',
              visible: true
            },
            {
              id: 'reports-compliance',
              label: 'Compliance Reports',
              icon: 'Shield',
              path: '/reports/compliance',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'logs',
          label: 'Activity Logs',
          icon: 'History',
          path: '/logs',
          visible: true
        }
      ]
    },
    {
      id: 'settings',
      label: 'SETTINGS',
      visible: true,
      items: [
        {
          id: 'user-role',
          label: 'Users & Roles',
          icon: 'Shield',
          children: [
            {
              id: 'roles',
              label: 'Roles',
              icon: 'ShieldCheck',
              path: '/settings/roles',
              visible: true
            },
            {
              id: 'users',
              label: 'Users',
              icon: 'User',
              path: '/settings/users',
              visible: true
            },
            {
              id: 'departments',
              label: 'Departments',
              icon: 'Building',
              path: '/settings/departments',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'general-settings',
          label: 'General Settings',
          icon: 'Settings',
          path: '/settings/general',
          visible: true
        },
        {
          id: 'delivery-charge',
          label: 'Delivery Charges',
          icon: 'CreditCard',
          path: '/settings/delivery-charge',
          visible: true
        },
        {
          id: 'notification-settings',
          label: 'Notifications',
          icon: 'Bell',
          children: [
            {
              id: 'notification-preferences',
              label: 'Preferences',
              icon: 'Settings',
              path: '/settings/notifications',
              visible: true
            },
            {
              id: 'sms-settings',
              label: 'SMS',
              icon: 'MessageCircle',
              path: '/settings/sms',
              visible: true
            },
            {
              id: 'email-settings',
              label: 'Email',
              icon: 'Mail',
              path: '/settings/email',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'map-settings',
          label: 'Map Settings',
          icon: 'Map',
          path: '/settings/maps',
          visible: true
        }
      ]
    }
  ]
};
