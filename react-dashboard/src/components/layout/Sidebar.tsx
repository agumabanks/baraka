import React, { useCallback, useMemo } from 'react';
import { X } from 'lucide-react';
import type { SidebarProps, NavBucket } from '../../types/navigation';
import SidebarItem from './SidebarItem';

const branchManagementBucket: NavBucket = {
  id: 'branch-management',
  label: 'BRANCH MANAGEMENT',
  visible: true,
  items: [
    {
      id: 'branch-management-menu',
      label: 'Branch Management',
      icon: 'Building2',
      expanded: false,
      visible: true,
      children: [
        {
          id: 'branches-all',
          label: 'All Branches',
          icon: 'Building',
          path: '/branches',
          visible: true
        },
        {
          id: 'branches-hierarchy',
          label: 'Branch Hierarchy',
          icon: 'GitBranch',
          path: '/branches/hierarchy',
          visible: true
        },
        {
          id: 'branches-analytics',
          label: 'Branch Analytics',
          icon: 'BarChart3',
          path: '/branches/analytics',
          visible: true
        },
        {
          id: 'branches-capacity',
          label: 'Capacity Planning',
          icon: 'Gauge',
          path: '/branches/capacity',
          visible: true
        },
        {
          id: 'branches-workers',
          label: 'Branch Workers',
          icon: 'UserCog',
          path: '/branches/workers',
          visible: true
        }
      ]
    },
    {
      id: 'deliveryman',
      label: 'Delivery Drivers',
      icon: 'Users',
      path: '/deliveryman',
      visible: true
    }
  ]
};

/**
 * Sidebar Component
 * Main navigation sidebar with monochrome Steve Jobs design
 * Features: collapsible sections, mobile responsive, keyboard navigation, accessibility
 */
const Sidebar: React.FC<SidebarProps> = ({
  navigation,
  currentPath = '/',
  isOpen = false,
  onClose,
  onNavigate,
  className = ''
}) => {
  const enhancedBuckets = useMemo(() => {
    const buckets = navigation?.buckets ?? [];
    if (buckets.some((bucket) => bucket.id === branchManagementBucket.id)) {
      return buckets;
    }
    return [...buckets, branchManagementBucket];
  }, [navigation]);

  // Handle navigation item click
  const handleNavigate = useCallback((path?: string) => {
    if (path && onNavigate) {
      onNavigate(path);
    }
    // Close mobile sidebar on navigation
    if (onClose && window.innerWidth < 992) {
      onClose();
    }
  }, [onNavigate, onClose]);

  // Handle keyboard navigation
  const handleKeyDown = useCallback((e: React.KeyboardEvent) => {
    if (e.key === 'Escape' && onClose) {
      onClose();
    }
  }, [onClose]);

  return (
    <>
      {/* Mobile overlay */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-black/50 z-40 lg:hidden"
          onClick={onClose}
          aria-hidden="true"
        />
      )}

      {/* Sidebar */}
      <aside
        className={`
          fixed lg:static inset-y-0 left-0 z-50
          w-80 lg:w-72 xl:w-80
          bg-mono-white text-mono-gray-900
          border-r border-mono-gray-200
          shadow-2xl lg:shadow-lg
          transform transition-transform duration-300 ease-in-out
          h-screen overflow-y-auto
          ${isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
          ${className}
        `}
        onKeyDown={handleKeyDown}
        role="navigation"
        aria-label="Main navigation"
      >
        {/* Header with logo and close button */}
        <div className="flex items-center justify-between p-6 pb-4">
          <a
            href="/dashboard"
            className="flex items-center gap-3 font-semibold text-mono-black hover:text-mono-gray-800 transition-colors"
            onClick={(e) => {
              e.preventDefault();
              handleNavigate('/dashboard');
            }}
          >
            {navigation.logoUrl && (
              <img
                src={navigation.logoUrl}
                alt={navigation.appName || 'Logo'}
                className="h-10 w-auto object-contain drop-shadow-sm"
              />
            )}
            
          </a>

          {/* Mobile close button */}
          <button
            type="button"
            className="lg:hidden p-2 rounded-full bg-mono-gray-100 hover:bg-mono-gray-200 text-mono-gray-700 hover:text-mono-black transition-all duration-200 hover:scale-105"
            onClick={onClose}
            aria-label="Close sidebar"
          >
            <X size={20} />
          </button>
        </div>

        {/* Navigation content */}
        <div className="flex flex-col h-full">
          
          {/* Scrollable navigation area */}
          <div className="flex-1 overflow-y-auto px-6 pb-6 custom-scrollbar">
            {enhancedBuckets.filter(bucket => bucket.visible !== false).map((bucket) => (
              <div key={bucket.id} className="mb-6">
                {/* Bucket divider */}
                <div className="text-xs font-semibold uppercase tracking-wider text-mono-gray-500 mb-3 px-1">
                  {bucket.label}
                </div>

                {/* Bucket container */}
                <div className="bg-mono-white border border-mono-gray-200 rounded-2xl p-4 shadow-sm">
                  <ul className="space-y-1">
                    {bucket.items.filter(item => item.visible !== false).map((item) => (
                      <SidebarItem
                        key={item.id}
                        item={item}
                        currentPath={currentPath}
                        onClick={handleNavigate}
                      />
                    ))}
                  </ul>
                </div>
              </div>
            ))}
          </div>
        </div>
      </aside>
    </>
  );
};

export default Sidebar;
