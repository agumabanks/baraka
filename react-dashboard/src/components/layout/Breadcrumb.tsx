import React from 'react';
import { ChevronRight, Home } from 'lucide-react';
import type { BreadcrumbProps } from '../../types/header';

/**
 * Breadcrumb Component
 * Navigation breadcrumb with home icon and chevron separators
 */
const Breadcrumb: React.FC<BreadcrumbProps> = ({ items }) => {
  if (!items || items.length === 0) {
    return null;
  }

  return (
    <nav
      className="flex items-center space-x-2 text-sm text-mono-gray-600"
      aria-label="Breadcrumb navigation"
    >
      <ol className="flex items-center space-x-2">
        {/* Home link */}
        <li>
          <a
            href="/dashboard"
            className="flex items-center gap-1 text-mono-gray-500 hover:text-mono-black transition-colors"
            aria-label="Go to dashboard"
          >
            <Home size={16} />
            <span className="sr-only">Home</span>
          </a>
        </li>

        {/* Breadcrumb items */}
        {items.map((item, index) => (
          <li key={index} className="flex items-center">
            <ChevronRight size={14} className="text-mono-gray-400 mx-1" />
            {item.href && !item.active ? (
              <a
                href={item.href}
                className="text-mono-gray-600 hover:text-mono-black transition-colors"
                aria-current={item.active ? 'page' : undefined}
              >
                {item.label}
              </a>
            ) : (
              <span
                className={`${
                  item.active
                    ? 'text-mono-black font-medium'
                    : 'text-mono-gray-500'
                }`}
                aria-current={item.active ? 'page' : undefined}
              >
                {item.label}
              </span>
            )}
          </li>
        ))}
      </ol>
    </nav>
  );
};

export default Breadcrumb;