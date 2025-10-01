import React, { useCallback } from 'react';
import { X } from 'lucide-react';
import type { SidebarProps } from '../../types/navigation';
import SidebarItem from './SidebarItem';

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
            <span className="text-lg tracking-tight">
              {navigation.appName || 'Dashboard'}
            </span>
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
          <div className="px-6 pb-4">
            <div className="rounded-2xl border border-mono-gray-200 bg-mono-gray-50 px-4 py-3">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                Control Pulse
              </p>
              <p className="mt-1 text-sm text-mono-gray-600">
                Navigate the operational spine. Every link is monochrome and deliberate.
              </p>
            </div>
          </div>

          {/* Scrollable navigation area */}
          <div className="flex-1 overflow-y-auto px-6 pb-6 custom-scrollbar">
            {navigation.buckets.filter(bucket => bucket.visible !== false).map((bucket) => (
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
