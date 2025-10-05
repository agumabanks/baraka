import React, { useCallback } from 'react';
import { X } from 'lucide-react';
import type { SidebarProps } from '../../types/navigation';
import SidebarItem from './SidebarItem';

/**
 * Google-Standard Polished Sidebar Component
 * Monochrome Steve Jobs design with smooth animations
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
      {/* Mobile overlay with fade animation */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-black/60 z-40 lg:hidden animate-fadeIn backdrop-blur-sm"
          onClick={onClose}
          aria-hidden="true"
        />
      )}

      {/* Sidebar with smooth slide animation */}
      <aside
        className={`
          fixed lg:static inset-y-0 left-0 z-50
          w-80 lg:w-72 xl:w-80
          bg-white text-gray-900
          border-r border-gray-200
          shadow-2xl lg:shadow-none
          transform transition-all duration-300 ease-out
          flex flex-col
          h-screen
          ${isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
          ${className}
        `}
        onKeyDown={handleKeyDown}
        role="navigation"
        aria-label="Main navigation"
      >
        {/* Header with logo - Google Material style */}
        <div className="sticky top-0 z-10 bg-white border-b border-gray-200 px-6 py-4">
          <div className="flex items-center justify-between">
            <a
              href="/dashboard"
              className="flex items-center gap-3 font-semibold text-black hover:opacity-80 transition-opacity"
              onClick={(e) => {
                e.preventDefault();
                handleNavigate('/dashboard');
              }}
            >
              {navigation.logoUrl && (
                <img
                  src={navigation.logoUrl}
                  alt={navigation.appName || 'Logo'}
                  className="h-8 w-auto object-contain"
                />
              )}
              <span className="text-lg font-semibold">{navigation.appName}</span>
            </a>

            {/* Mobile close button - Google Material Design */}
            <button
              type="button"
              className="lg:hidden p-2 rounded-full hover:bg-gray-100 active:bg-gray-200 text-gray-700 transition-all duration-200"
              onClick={onClose}
              aria-label="Close sidebar"
            >
              <X size={20} />
            </button>
          </div>
        </div>

        {/* Scrollable navigation area with custom scrollbar */}
        <nav className="flex-1 overflow-y-auto px-4 py-4" style={{ scrollbarWidth: 'thin', scrollbarColor: '#9CA3AF transparent' }}>
          {navigation.buckets.filter(bucket => bucket.visible !== false).map((bucket, bucketIndex) => (
            <div
              key={bucket.id}
              className="mb-6 animate-slideInLeft"
              style={{ animationDelay: `${bucketIndex * 50}ms` }}
            >
              {/* Bucket label - Google Material Typography */}
              <div className="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-3 px-3">
                {bucket.label}
              </div>

              {/* Navigation items */}
              <ul className="space-y-0.5">
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
          ))}
        </nav>

        {/* Footer - Version info */}
        <div className="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4">
          <div className="text-xs text-gray-500 text-center">
            <p>Baraka ERP v1.0 by Sanaa</p>
            <p className="mt-1">© 2025 All rights reserved</p>
          </div>
        </div>
      </aside>

      {/* Custom styles for animations */}
      <style>{`
        @keyframes fadeIn {
          from {
            opacity: 0;
          }
          to {
            opacity: 1;
          }
        }

        @keyframes slideInLeft {
          from {
            opacity: 0;
            transform: translateX(-10px);
          }
          to {
            opacity: 1;
            transform: translateX(0);
          }
        }

        .animate-fadeIn {
          animation: fadeIn 0.2s ease-out;
        }

        .animate-slideInLeft {
          animation: slideInLeft 0.3s ease-out forwards;
          opacity: 0;
        }

        /* Custom scrollbar for webkit browsers */
        nav::-webkit-scrollbar {
          width: 6px;
        }

        nav::-webkit-scrollbar-track {
          background: transparent;
        }

        nav::-webkit-scrollbar-thumb {
          background: #9CA3AF;
          border-radius: 3px;
        }

        nav::-webkit-scrollbar-thumb:hover {
          background: #6B7280;
        }
      `}</style>
    </>
  );
};

export default Sidebar;
