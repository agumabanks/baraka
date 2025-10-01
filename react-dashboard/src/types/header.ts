/**
 * Header Component Types
 * TypeScript interfaces for header/navbar components
 */

export interface User {
  id: string;
  name: string;
  email: string;
  avatar?: string;
  role: string;
  permissions?: string[];
}

export interface Language {
  code: string;
  name: string;
  flag: string;
  active?: boolean;
}

export interface Notification {
  id: string;
  title: string;
  message: string;
  type: 'info' | 'success' | 'warning' | 'error';
  read: boolean;
  timestamp: Date;
  actionUrl?: string;
}

export interface BreadcrumbItem {
  label: string;
  href?: string;
  active?: boolean;
}

export interface HeaderProps {
  user: User;
  currentLanguage: Language;
  languages: Language[];
  notifications: Notification[];
  breadcrumbs?: BreadcrumbItem[];
  onLanguageChange: (language: Language) => void;
  onNotificationClick: (notification: Notification) => void;
  onToggleSidebar: () => void;
  onLogout: () => void;
  logoUrl?: string;
  appName?: string;
}

export interface UserMenuProps {
  user: User;
  onLogout: () => void;
}

export interface LanguageSelectorProps {
  currentLanguage: Language;
  languages: Language[];
  onLanguageChange: (language: Language) => void;
}

export interface BreadcrumbProps {
  items: BreadcrumbItem[];
}

export interface MobileMenuToggleProps {
  onToggle: () => void;
  isOpen: boolean;
}