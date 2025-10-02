/**
 * Mock Navigation Configuration
 * Converted from Laravel config/admin_nav.php
 * Includes common sections: Dashboard, Operations, Sales, Finance, Settings
 */

import type { NavigationConfig } from '../types/navigation';

export const navigationConfig: NavigationConfig = {
  logoUrl: '/images/default/logo1.png',
  appName: 'Baraka',
  buckets: [
    {
      id: 'menu',
      label: 'MENU',
      visible: true,
      items: [
        {
          id: 'dashboard',
          label: 'Dashboard',
          icon: 'Home',
          expanded: true,
          children: [
            {
              id: 'dashboard-overview',
              label: 'Overview',
              icon: 'LayoutDashboard',
              path: '/dashboard',
              visible: true
            },
            {
              id: 'dashboard-analytics',
              label: 'Analytics',
              icon: 'TrendingUp',
              path: '/dashboard/analytics',
              visible: true
            },
            {
              id: 'dashboard-reports',
              label: 'Reports',
              icon: 'FileText',
              path: '/dashboard/reports',
              visible: true
            }
          ],
          badge: {
            count: 3,
            variant: 'attention',
            ariaLabel: '3 SLA alerts',
            title: '3 SLA alerts today'
          },
          visible: true
        },
        {
          id: 'deliveryman',
          label: 'Delivery Drivers',
          icon: 'Users',
          path: '/deliveryman',
          badge: {
            count: 12,
            variant: 'success',
            ariaLabel: '12 active drivers',
            title: '12 active drivers'
          },
          visible: true
        },
        {
          id: 'branch-manage',
          label: 'Branch Management',
          icon: 'Building2',
          children: [
            {
              id: 'branches',
              label: 'Branches',
              icon: 'Building',
              path: '/branches',
              visible: true
            },
            {
              id: 'branch-payments',
              label: 'Payments',
              icon: 'CreditCard',
              path: '/hub/payments',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'merchant-manage',
          label: 'Merchant Management',
          icon: 'Store',
          children: [
            {
              id: 'merchants',
              label: 'Merchants',
              icon: 'User',
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
          id: 'todo',
          label: 'To-Do List',
          icon: 'CheckSquare',
          path: '/todo',
          badge: {
            count: 7,
            variant: 'warning',
            ariaLabel: '7 open tasks',
            title: '7 open tasks'
          },
          visible: true
        },
        {
          id: 'support',
          label: 'Support',
          icon: 'MessageSquare',
          path: '/support',
          badge: {
            count: 2,
            variant: 'attention',
            ariaLabel: '2 urgent tickets',
            title: '2 urgent support tickets'
          },
          visible: true
        },
        {
          id: 'parcel',
          label: 'Parcels',
          icon: 'Package',
          path: '/parcels',
          badge: {
            count: 5,
            variant: 'error',
            ariaLabel: '5 exception parcels',
            title: '5 parcels requiring attention'
          },
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
          path: '/shipments',
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
              id: 'linehaul-legs',
              label: 'Transport Legs',
              icon: 'Truck',
              path: '/linehaul',
              visible: true
            },
            {
              id: 'manifests',
              label: 'Manifests',
              icon: 'List',
              path: '/manifests',
              visible: true
            },
            {
              id: 'ecmr',
              label: 'e-CMR',
              icon: 'FileText',
              path: '/ecmr',
              visible: true
            }
          ],
          visible: true
        },
        {
          id: 'scan-events',
          label: 'Scan Events',
          icon: 'Scan',
          path: '/scans',
          visible: true
        },
        {
          id: 'routes',
          label: 'Routes & Stops',
          icon: 'Truck',
          path: '/routes',
          visible: true
        }
      ]
    },
    {
      id: 'sales',
      label: 'SALES',
      visible: true,
      items: [
        {
          id: 'customers',
          label: 'Customers',
          icon: 'Users',
          children: [
            {
              id: 'customers-all',
              label: 'All Customers',
              icon: 'Users',
              path: '/customers',
              visible: true
            },
            {
              id: 'customers-create',
              label: 'New Customer',
              icon: 'UserPlus',
              path: '/customers/create',
              visible: true
            }
          ],
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
          icon: 'FileText',
          path: '/contracts',
          visible: true
        },
        {
          id: 'address-book',
          label: 'Address Book',
          icon: 'Book',
          path: '/address-book',
          visible: true
        }
      ]
    },
    {
      id: 'compliance',
      label: 'COMPLIANCE',
      visible: true,
      items: [
        {
          id: 'audits',
          label: 'Audits',
          icon: 'ClipboardCheck',
          path: '/compliance/audits',
          visible: true
        },
        {
          id: 'policies',
          label: 'Policies',
          icon: 'Shield',
          path: '/compliance/policies',
          visible: true
        },
        {
          id: 'compliance-reports',
          label: 'Reports',
          icon: 'FileBarChart',
          path: '/compliance/reports',
          visible: true
        }
      ]
    },
    {
      id: 'finance',
      label: 'FINANCE',
      visible: true,
      items: [
        {
          id: 'rate-cards',
          label: 'Rate Cards',
          icon: 'Calculator',
          path: '/rate-cards',
          visible: true
        },
        {
          id: 'invoices',
          label: 'Invoices',
          icon: 'Receipt',
          path: '/invoices',
          badge: {
            count: 15,
            variant: 'info',
            ariaLabel: '15 pending invoices',
            title: '15 pending invoices'
          },
          visible: true
        },
        {
          id: 'settlements',
          label: 'Settlements',
          icon: 'ArrowLeftRight',
          path: '/settlements',
          visible: true
        },
        {
          id: 'payroll',
          label: 'Payroll',
          icon: 'Wallet',
          children: [
            {
              id: 'salary-generate',
              label: 'Generate Salary',
              icon: 'Calculator',
              path: '/payroll/generate',
              visible: true
            },
            {
              id: 'salary-index',
              label: 'Salary Records',
              icon: 'DollarSign',
              path: '/payroll',
              visible: true
            }
          ],
          visible: true
        }
      ]
    },
    {
      id: 'tools',
      label: 'TOOLS',
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
          id: 'reports',
          label: 'Reports',
          icon: 'BarChart',
          children: [
            {
              id: 'parcel-reports',
              label: 'Parcel Reports',
              icon: 'Package',
              path: '/reports/parcels',
              visible: true
            },
            {
              id: 'salary-reports',
              label: 'Salary Reports',
              icon: 'DollarSign',
              path: '/reports/salary',
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
          id: 'sms-settings',
          label: 'SMS Settings',
          icon: 'MessageCircle',
          path: '/settings/sms',
          visible: true
        },
        {
          id: 'notification-settings',
          label: 'Notifications',
          icon: 'Bell',
          path: '/settings/notifications',
          visible: true
        }
      ]
    }
  ]
};
