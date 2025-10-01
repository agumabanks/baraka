import React, { useState, useCallback } from 'react';
import * as Icons from 'lucide-react';
import type { SidebarItemProps, NavBadge } from '../../types/navigation';
// import Badge from '../ui/Badge';

/**
 * Badge component for navigation items
 * Matches monochrome styling from Blade sidebar
 */
const NavBadge: React.FC<{ badge: NavBadge }> = ({ badge }) => {
  const variantClasses = {
    default: 'bg-mono-black text-mono-white',
    success: 'bg-mono-gray-700 text-mono-white',
    warning: 'bg-mono-gray-600 text-mono-white',
    info: 'bg-mono-gray-500 text-mono-white',
    attention: 'bg-mono-black text-mono-white animate-pulse',
    error: 'bg-mono-black text-mono-white'
  };

  const variantClass = variantClasses[badge.variant || 'default'];

  return (
    <span
      className={`inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-semibold rounded-full ${variantClass} ml-auto mr-2 shadow-sm transition-transform duration-200 group-hover:scale-105`}
      aria-label={badge.ariaLabel || `${badge.count} items`}
      title={badge.title}
    >
      {badge.count}
    </span>
  );
};

/**
 * SidebarItem Component
 * Renders individual navigation item with support for nested children
 */
const SidebarItem: React.FC<SidebarItemProps> = ({
  item,
  currentPath = '',
  level = 0,
  onClick,
  className = ''
}) => {
  const [isExpanded, setIsExpanded] = useState(item.expanded || false);
  const hasChildren = item.children && item.children.length > 0;
  const isActive = item.path === currentPath || item.active;
  const isSubmenu = level > 0;

  // Get icon component from lucide-react
  const IconComponent = item.icon ? (Icons as any)[item.icon] : null;

  const handleClick = useCallback((e: React.MouseEvent | React.KeyboardEvent) => {
    e.preventDefault();
    
    if (hasChildren) {
      setIsExpanded(prev => !prev);
    } else if (item.path && onClick) {
      onClick(item.path);
    }
  }, [hasChildren, item.path, onClick]);

  const handleKeyDown = useCallback((e: React.KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      handleClick(e);
    }
  }, [handleClick]);

  // Base classes for nav link
  const baseClasses = 'flex items-center gap-3.5 font-medium rounded-2xl relative transition-all duration-200 justify-start group';
  
  // Size classes based on level
  const sizeClasses = isSubmenu
    ? 'text-[0.95rem] py-2 px-2.5 gap-2.5'
    : 'py-2.5 px-3.5';

  // State-based classes
  const stateClasses = isActive
    ? 'bg-mono-black text-mono-white shadow-lg'
    : 'text-mono-gray-900 hover:bg-mono-gray-50 hover:text-mono-black hover:-translate-y-0.5';

  // Icon container classes
  const iconContainerBaseClasses = 'flex items-center justify-center rounded-xl transition-all duration-200 flex-shrink-0';
  const iconContainerSizeClasses = isSubmenu
    ? 'w-8 h-8 text-sm'
    : 'w-10 h-10 text-base';
  
  const iconContainerStateClasses = isActive
    ? 'bg-white/15 text-mono-white'
    : 'bg-mono-gray-100 text-mono-gray-700 group-hover:bg-mono-gray-200 group-hover:text-mono-black';

  const linkClasses = `${baseClasses} ${sizeClasses} ${stateClasses} ${className}`;
  const iconContainerClasses = `${iconContainerBaseClasses} ${iconContainerSizeClasses} ${iconContainerStateClasses}`;

  return (
    <li className={`nav-item ${isSubmenu ? 'mt-0.5' : 'mt-1'}`}>
      <a
        href={item.path || '#'}
        className={linkClasses}
        onClick={handleClick}
        onKeyDown={handleKeyDown}
        aria-expanded={hasChildren ? isExpanded : undefined}
        aria-current={isActive ? 'page' : undefined}
        role={hasChildren ? 'button' : 'link'}
        tabIndex={0}
      >
        {/* Icon */}
        {IconComponent && (
          <span className={iconContainerClasses} aria-hidden="true">
            <IconComponent size={isSubmenu ? 14 : 18} strokeWidth={2.5} />
          </span>
        )}

        {/* Label */}
        <span className="flex-1 text-left nav-link-text">
          {item.label}
        </span>

        {/* Badge */}
        {item.badge && <NavBadge badge={item.badge} />}

        {/* Chevron for items with children */}
        {hasChildren && (
          <Icons.ChevronRight
            size={16}
            className={`transition-transform duration-300 ${isExpanded ? 'rotate-90' : ''}`}
            aria-hidden="true"
          />
        )}
      </a>

      {/* Submenu */}
      {hasChildren && isExpanded && (
        <div className="submenu border-l-2 border-mono-gray-200 ml-10 pl-4 pt-1 mt-1">
          <ul className="nav flex-col space-y-0.5">
            {item.children?.filter(child => child.visible !== false).map((child) => (
              <SidebarItem
                key={child.id}
                item={child}
                currentPath={currentPath}
                level={level + 1}
                onClick={onClick}
              />
            ))}
          </ul>
        </div>
      )}
    </li>
  );
};

export default SidebarItem;