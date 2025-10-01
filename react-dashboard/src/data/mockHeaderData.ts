import type { User, Language, Notification, BreadcrumbItem } from '../types/header';

/**
 * Mock data for Header component demonstration
 */

export const mockUser: User = {
  id: '1',
  name: 'John Doe',
  email: 'john.doe@example.com',
  avatar: 'https://via.placeholder.com/40x40/000000/FFFFFF?text=JD',
  role: 'Administrator',
  permissions: ['admin', 'manage_users', 'view_reports']
};

export const mockLanguages: Language[] = [
  { code: 'en', name: 'English', flag: 'us' },
  { code: 'bn', name: 'Bangla', flag: 'bd' },
  { code: 'in', name: 'Hindi', flag: 'in' },
  { code: 'ar', name: 'Arabic', flag: 'sa' },
  { code: 'fr', name: 'French', flag: 'fr' },
  { code: 'es', name: 'Spanish', flag: 'es' },
  { code: 'zh', name: 'Chinese', flag: 'cn' }
];

export const mockCurrentLanguage: Language = mockLanguages[0];

export const mockNotifications: Notification[] = [
  {
    id: '1',
    title: 'New Order Received',
    message: 'Order #1234 has been placed by customer ABC Corp',
    type: 'info',
    read: false,
    timestamp: new Date(Date.now() - 1000 * 60 * 5), // 5 minutes ago
    actionUrl: '/orders/1234'
  },
  {
    id: '2',
    title: 'Payment Failed',
    message: 'Payment for invoice #5678 failed to process',
    type: 'error',
    read: false,
    timestamp: new Date(Date.now() - 1000 * 60 * 15), // 15 minutes ago
    actionUrl: '/invoices/5678'
  },
  {
    id: '3',
    title: 'Delivery Completed',
    message: 'Package #9012 has been successfully delivered',
    type: 'success',
    read: true,
    timestamp: new Date(Date.now() - 1000 * 60 * 60), // 1 hour ago
    actionUrl: '/deliveries/9012'
  },
  {
    id: '4',
    title: 'System Maintenance',
    message: 'Scheduled maintenance will begin in 2 hours',
    type: 'warning',
    read: true,
    timestamp: new Date(Date.now() - 1000 * 60 * 60 * 2), // 2 hours ago
  }
];

export const mockBreadcrumbs: BreadcrumbItem[] = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Orders', href: '/orders' },
  { label: 'Order Details', active: true }
];

export const mockBreadcrumbsHome: BreadcrumbItem[] = [
  { label: 'Dashboard', active: true }
];

export const mockBreadcrumbsDeep: BreadcrumbItem[] = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Settings', href: '/settings' },
  { label: 'User Management', href: '/settings/users' },
  { label: 'Edit User', active: true }
];