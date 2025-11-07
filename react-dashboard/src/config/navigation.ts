import type { NavigationConfig } from '../types/navigation';

/**
 * Minimal navigation configuration that mirrors the currently delivered features
 * in the React admin dashboard. This keeps the sidebar focused on what actually
 * works today while backend-driven navigation (via /api/navigation/admin) loads.
 */
export const navigationConfig: NavigationConfig = {
  logoUrl: '/images/default/logo1.png',
  appName: 'Baraka',
  buckets: [
    {
      id: 'command-center',
      label: 'COMMAND CENTER',
      visible: true,
      items: [
        {
          id: 'dashboard-home',
          label: 'Dashboard Home',
          icon: 'LayoutDashboard',
          path: '/dashboard',
          visible: true,
        },
        {
          id: 'workflow-board',
          label: 'Workflow Board',
          icon: 'KanbanSquare',
          path: '/workflow',
          visible: true,
        },
        {
          id: 'task-board',
          label: 'Task Board',
          icon: 'CheckSquare',
          path: '/todo',
          visible: true,
        },
        {
          id: 'live-tracking',
          label: 'Live Tracking',
          icon: 'MapPin',
          path: '/tracking',
          visible: true,
        },
        {
          id: 'reports-center',
          label: 'Reports Center',
          icon: 'BarChart2',
          path: '/reports',
          visible: true,
        },
      ],
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
          visible: true,
        },
        {
          id: 'shipments',
          label: 'Shipments',
          icon: 'Package',
          path: '/shipments',
          visible: true,
        },
        {
          id: 'bags',
          label: 'Bags',
          icon: 'Briefcase',
          path: '/bags',
          visible: true,
        },
        {
          id: 'routes',
          label: 'Routes',
          icon: 'Route',
          path: '/routes',
          visible: true,
        },
        {
          id: 'scans',
          label: 'Scan Logs',
          icon: 'Barcode',
          path: '/scans',
          visible: true,
        },
      ],
    },
    {
      id: 'branch-network',
      label: 'BRANCH NETWORK',
      visible: true,
      items: [
        {
          id: 'branch-management',
          label: 'Branch Management',
          icon: 'Building2',
          path: '/branches',
          visible: true,
          expanded: true,
          children: [
            {
              id: 'branches-overview',
              label: 'Branch Performance',
              icon: 'LayoutDashboard',
              path: '/branches',
              visible: true,
            },
            {
              id: 'branch-managers',
              label: 'Managers',
              icon: 'UserCog',
              path: '/branch-managers',
              visible: true,
            },
            {
              id: 'branch-workers',
              label: 'Workers',
              icon: 'Users',
              path: '/branch-workers',
              visible: true,
            },
            {
              id: 'drivers',
              label: 'Drivers & Rosters',
              icon: 'Truck',
              path: '/drivers',
              visible: true,
            },
            {
              id: 'branch-clients',
              label: 'Local Clients',
              icon: 'Users',
              path: '/branches/clients',
              visible: true,
            },
            {
              id: 'branch-shipments',
              label: 'Shipments',
              icon: 'Route',
              path: '/branches/shipments',
              visible: true,
            },
            {
              id: 'branch-hierarchy',
              label: 'Hierarchy',
              icon: 'GitBranch',
              path: '/branches/hierarchy',
              visible: true,
            },
          ],
        },
      ],
    },
    {
      id: 'partners',
      label: 'PARTNERS',
      visible: true,
      items: [
        {
          id: 'merchants',
          label: 'Merchants',
          icon: 'Store',
          path: '/merchants',
          visible: true,
        },
        {
          id: 'merchant-payments',
          label: 'Merchant Payments',
          icon: 'CreditCard',
          path: '/merchant/payments',
          visible: true,
        },
      ],
    },
    {
      id: 'sales',
      label: 'SALES',
      visible: true,
      items: [
        {
          id: 'customer-hub',
          label: 'Customers',
          icon: 'Users',
          expanded: true,
          visible: true,
          children: [
            {
              id: 'customers-all',
              label: 'All Customers',
              icon: 'Users',
              path: '/customers',
              visible: true,
            },
            {
              id: 'customers-create',
              label: 'Add Customer',
              icon: 'UserPlus',
              path: '/customers/create',
              visible: true,
            },
          ],
        },
        {
          id: 'quotations',
          label: 'Quotations',
          icon: 'FileSignature',
          path: '/quotations',
          visible: true,
        },
        {
          id: 'contracts',
          label: 'Contracts',
          icon: 'ScrollText',
          path: '/contracts',
          visible: true,
        },
        {
          id: 'address-book',
          label: 'Address Book',
          icon: 'BookUser',
          path: '/address-book',
          visible: true,
        },
      ],
    },
    {
      id: 'support',
      label: 'SUPPORT',
      visible: true,
      items: [
        {
          id: 'support-center',
          label: 'Support Tickets',
          icon: 'MessageSquare',
          path: '/support',
          visible: true,
        },
      ],
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
          visible: true,
          children: [
            {
              id: 'settings-roles',
              label: 'Roles',
              icon: 'ShieldCheck',
              path: '/settings/roles',
              visible: true,
            },
            {
              id: 'settings-users',
              label: 'Users',
              icon: 'User',
              path: '/settings/users',
              visible: true,
            },
          ],
        },
      ],
    },
  ],
};
