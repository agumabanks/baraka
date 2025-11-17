import React, { useState, useCallback, useEffect, useMemo } from 'react';
import * as Icons from 'lucide-react';
import type { SidebarItemProps, NavBadge as NavBadgeType } from '../../types/navigation';
import { convertFaToLucide } from '../../lib/iconMapping';
import { resolveDashboardNavigatePath } from '../../lib/spaNavigation';

/**
 * Badge component for navigation items
 * Matches monochrome styling from Blade sidebar
 */
type RawBadge = NavBadgeType | number | string;

const NavBadgePill: React.FC<{ badge: RawBadge }> = ({ badge }) => {
  const variantClasses = {
    default: 'bg-mono-black text-mono-white',
    success: 'bg-mono-gray-700 text-mono-white',
    warning: 'bg-mono-gray-600 text-mono-white',
    info: 'bg-mono-gray-500 text-mono-white',
    attention: 'bg-mono-black text-mono-white animate-pulse',
    error: 'bg-mono-black text-mono-white'
  };

  const resolvedBadge: NavBadgeType = typeof badge === 'object' && badge !== null
    ? (badge as NavBadgeType)
    : {
        count: (typeof badge === 'number' ? badge : (badge ?? '')) as string | number,
        variant: 'default'
      } as NavBadgeType;

  const variantClass = variantClasses[resolvedBadge.variant || 'default'];
  const displayValue = typeof resolvedBadge.count === 'number'
    ? (resolvedBadge.count > 999 ? '999+' : resolvedBadge.count)
    : resolvedBadge.count;

  return (
    <span
      className={`inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-semibold rounded-full ${variantClass} ml-auto mr-2 shadow-sm transition-all duration-200 group-hover:scale-105`}
      aria-label={resolvedBadge.ariaLabel || `${displayValue} items`}
      title={resolvedBadge.title}
    >
      {displayValue}
    </span>
  );
};

/**
 * SidebarItem Component
 * Renders individual navigation item with support for nested children
 * Enhanced with smooth animations and premium interactions
 */
const SidebarItem: React.FC<SidebarItemProps> = ({
  item,
  currentPath = '',
  level = 0,
  onClick,
  className = ''
}) => {
  const extractPath = (candidate?: string) => {
    if (!candidate) {
      return undefined;
    }

    if (candidate.startsWith('/')) {
      return candidate.length > 1 && candidate.endsWith('/')
        ? candidate.slice(0, -1)
        : candidate;
    }

    if (typeof window !== 'undefined') {
      try {
        const parsed = new URL(candidate, window.location.origin);
        const pathname = parsed.pathname;
        return pathname.length > 1 && pathname.endsWith('/')
          ? pathname.slice(0, -1)
          : pathname;
      } catch (error) {
        return undefined;
      }
    }

    return undefined;
  };

  const normalisedCurrentPath = extractPath(currentPath) ?? currentPath;

  const visibleChildren = useMemo(
    () => item.children?.filter((child) => child.visible !== false) ?? [],
    [item.children]
  );

  const hasChildren = visibleChildren.length > 0;
  const childActive = hasChildren
    ? visibleChildren.some((child) => {
        const childPath = extractPath(child.path) ?? extractPath(child.url);
        if (!childPath) {
          return false;
        }
        const normalisedChildPath = extractPath(resolveDashboardNavigatePath(childPath)) ?? childPath;
        return normalisedChildPath
          ? normalisedCurrentPath === normalisedChildPath || normalisedCurrentPath.startsWith(`${normalisedChildPath}/`)
          : false;
      }) ?? false
    : false;
  const [isExpanded, setIsExpanded] = useState(item.expanded || childActive || false);
  const isSubmenu = level > 0;

  // Initialize expanded state from navigation config
  useEffect(() => {
    if (item.expanded !== undefined) {
      setIsExpanded(item.expanded);
    }
  }, [item.expanded]);

  useEffect(() => {
    if (childActive) {
      setIsExpanded(true);
    }
  }, [childActive]);

  // Get icon component - handle both Lucide and Font Awesome icons
  const iconName = convertFaToLucide(item.icon || '');
  const IconComponent = (Icons as any)[iconName];
  const isFontAwesomeIcon = typeof item.icon === 'string' && /fa[\w-]*\s/i.test(item.icon);

  const resolvedPath = extractPath(item.path) ?? extractPath(item.url);
  const spaPath = resolvedPath ? resolveDashboardNavigatePath(resolvedPath) : undefined;
  const normalisedTargetPath = spaPath ? extractPath(spaPath) : resolvedPath;
  const isExternalLink = Boolean(item.external);
  const externalTarget = isExternalLink ? (item.path || item.url) : undefined;

  const defaultChildPath = useMemo(() => {
    if (!hasChildren) {
      return undefined;
    }

    const primaryChild = visibleChildren[0];
    if (!primaryChild) {
      return undefined;
    }

    const childResolved = extractPath(primaryChild.path) ?? extractPath(primaryChild.url);
    return childResolved ? resolveDashboardNavigatePath(childResolved) : undefined;
  }, [hasChildren, visibleChildren]);

  const handleClick = useCallback((e: React.MouseEvent | React.KeyboardEvent) => {
    if (hasChildren) {
      e.preventDefault();
      const willExpand = !isExpanded;
      setIsExpanded(willExpand);

      if (willExpand && defaultChildPath && onClick) {
        onClick(defaultChildPath);
      }
      return;
    }

    if (isExternalLink && externalTarget) {
      return; // allow default browser navigation to proceed
    }

    e.preventDefault();

    if (onClick && (spaPath || item.path)) {
      onClick(spaPath ?? item.path);
    } else if (item.url) {
      window.location.href = item.url;
    }
  }, [hasChildren, spaPath, item.path, item.url, onClick, isExpanded, defaultChildPath, isExternalLink, externalTarget]);

  const handleKeyDown = useCallback((e: React.KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ' ') {
      if (isExternalLink && externalTarget) {
        return;
      }
      e.preventDefault();
      handleClick(e);
    }
  }, [handleClick, isExternalLink, externalTarget]);

  // Base classes for nav link
  const baseClasses = 'flex items-center gap-3.5 font-medium rounded-2xl relative transition-all duration-200 justify-start group cursor-pointer';
  
  // Size classes based on level
  const sizeClasses = isSubmenu
    ? 'text-[0.95rem] py-2 px-2.5 gap-2.5'
    : 'py-2.5 px-3.5';

  // State-based classes
  const isActive = normalisedTargetPath
    ? normalisedTargetPath === '/dashboard' || normalisedTargetPath === '/'
      ? normalisedCurrentPath === normalisedTargetPath
      : normalisedCurrentPath === normalisedTargetPath || normalisedCurrentPath.startsWith(`${normalisedTargetPath}/`) || childActive
    : (item.active || childActive || false);

  const stateClasses = isActive
    ? 'bg-mono-black text-mono-white shadow-lg scale-[1.02]'
    : 'text-mono-gray-900 hover:bg-mono-gray-50 hover:text-mono-black hover:-translate-y-0.5 hover:shadow-md hover:scale-[1.01]';

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
        href={(item as any).external ? (externalTarget || resolvedPath || item.url) : (spaPath || resolvedPath || item.url || '#')}
        className={linkClasses}
        onClick={handleClick}
        onKeyDown={handleKeyDown}
        aria-expanded={hasChildren ? isExpanded : undefined}
        aria-current={isActive ? 'page' : undefined}
        role={hasChildren ? 'button' : 'link'}
        tabIndex={0}
        target={(item as any).external ? '_self' : undefined}
        rel={(item as any).external ? 'noopener' : undefined}
      >
        {/* Icon */}
        {item.icon && (
          <span className={iconContainerClasses} aria-hidden="true">
            {isFontAwesomeIcon ? (
              <i className={item.icon} />
            ) : IconComponent ? (
              <IconComponent size={isSubmenu ? 14 : 18} strokeWidth={2.5} />
            ) : (
              <Icons.Circle size={isSubmenu ? 14 : 18} strokeWidth={2.5} />
            )}
          </span>
        )}

        {/* Label */}
        <span className="flex-1 text-left nav-link-text">
          {item.label}
        </span>

        {/* Badge */}
        {item.badge && <NavBadgePill badge={item.badge} />}

        {/* Chevron for items with children */}
        {hasChildren && (
          <Icons.ChevronRight
            size={16}
            className={`transition-transform duration-250 ease-in-out ${isExpanded ? 'rotate-90' : ''}`}
            aria-hidden="true"
          />
        )}
      </a>

      {/* Submenu with enhanced animations */}
      {hasChildren && (
        <div 
          className={`overflow-hidden transition-all duration-250 ease-in-out ${
            isExpanded ? 'max-h-96 opacity-100' : 'max-h-0 opacity-0'
          }`}
          style={{
            transitionTimingFunction: 'cubic-bezier(0.4, 0, 0.2, 1)'
          }}
        >
          <div className={`submenu border-l border-gray-200/70 ml-10 pl-4 pt-1 mt-1 ${
            isExpanded ? 'animate-in fade-in slide-in-from-top-1 duration-200' : ''
          }`}>
            <ul className="nav flex-col space-y-0.5">
              {visibleChildren.map((child) => (
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
        </div>
      )}
    </li>
  );
};

export default SidebarItem;
