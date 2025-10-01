/**
 * Navigation Type Definitions
 * Based on Laravel admin_nav.php structure
 */

/**
 * Badge variant types for navigation items
 * All badges use monochrome styling per Steve Jobs design standards
 */
export type BadgeVariant = 
  | 'default'    // Standard black badge
  | 'success'    // Gray-700 badge
  | 'warning'    // Gray-600 badge
  | 'info'       // Gray-500 badge
  | 'attention'  // Black with pulse animation
  | 'error';     // Black badge for errors

/**
 * Badge configuration for navigation items
 */
export interface NavBadge {
  /** Badge count or label */
  count: number | string;
  /** Visual style variant */
  variant?: BadgeVariant;
  /** Accessible label for screen readers */
  ariaLabel?: string;
  /** Tooltip text on hover */
  title?: string;
}

/**
 * Individual navigation item
 * Can represent a link or a collapsible parent with children
 */
export interface NavItem {
  /** Unique identifier */
  id: string;
  /** Display label */
  label: string;
  /** Icon class or component name (e.g., 'Home' for lucide-react) */
  icon: string;
  /** Navigation path/route */
  path?: string;
  /** Badge configuration */
  badge?: NavBadge;
  /** Child navigation items */
  children?: NavItem[];
  /** Whether this item is currently active */
  active?: boolean;
  /** Whether this item is expanded (for collapsible items) */
  expanded?: boolean;
  /** Whether this item is visible (permission-based) */
  visible?: boolean;
}

/**
 * Navigation bucket/section
 * Groups related navigation items under a labeled divider
 */
export interface NavBucket {
  /** Unique bucket identifier */
  id: string;
  /** Bucket label/title */
  label: string;
  /** Navigation items in this bucket */
  items: NavItem[];
  /** Whether this bucket is visible */
  visible?: boolean;
}

/**
 * Complete navigation configuration
 */
export interface NavigationConfig {
  /** Logo image URL or path */
  logoUrl?: string;
  /** Application/company name */
  appName?: string;
  /** Navigation buckets (grouped sections) */
  buckets: NavBucket[];
}

/**
 * Sidebar component props
 */
export interface SidebarProps {
  /** Navigation configuration */
  navigation: NavigationConfig;
  /** Current active route path */
  currentPath?: string;
  /** Whether sidebar is open (mobile) */
  isOpen?: boolean;
  /** Callback when sidebar is closed (mobile) */
  onClose?: () => void;
  /** Callback when navigation item is clicked */
  onNavigate?: (path: string) => void;
  /** Additional CSS classes */
  className?: string;
}

/**
 * SidebarItem component props
 */
export interface SidebarItemProps {
  /** Navigation item data */
  item: NavItem;
  /** Current active route path */
  currentPath?: string;
  /** Nesting level (0 = top level, 1 = submenu, etc.) */
  level?: number;
  /** Callback when item is clicked */
  onClick?: (path?: string) => void;
  /** Additional CSS classes */
  className?: string;
}