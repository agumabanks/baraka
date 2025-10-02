import React from 'react';
import { useNavigate } from 'react-router-dom';
import { t } from '../../lib/i18n';
import { getUserPermissions, hasPermission } from '../../lib/rbac';
import type { QuickAction as DashboardQuickAction } from '../../types/dashboard';

interface QuickActionsProps {
  actions?: DashboardQuickAction[];
  loading?: boolean;
}

/**
 * Quick Actions Component
 * Matches Blade's quick-action-card design with monochrome styling
 * Black icons on white backgrounds with hover effects
 */
const FALLBACK_ACTIONS: DashboardQuickAction[] = [
  {
    id: 'book-shipment',
    title: t('dashboard.book_shipment'),
    icon: 'fas fa-clipboard-check',
    url: '/admin/booking/step1',
    permission: 'booking_create',
    shortcut: 'Ctrl+B',
  },
  {
    id: 'bulk-upload',
    title: t('dashboard.bulk_upload'),
    icon: 'fas fa-file-upload',
    url: '/parcel/parcel-import',
    permission: 'parcel_create',
    shortcut: 'Ctrl+U',
  },
  {
    id: 'view-parcels',
    title: t('dashboard.view_all_parcels'),
    icon: 'fas fa-dolly',
    url: '/parcel/index',
    permission: 'parcel_read',
  },
  {
    id: 'exceptions',
    title: (() => {
      const label = t('dashboard.manage_exceptions');
      return label === 'dashboard.manage_exceptions' ? 'Manage Exceptions' : label;
    })(),
    icon: 'fas fa-exclamation-circle',
    url: '/parcel/exceptions',
    permission: 'parcel_exception',
  },
];

const QuickActions: React.FC<QuickActionsProps> = ({ actions, loading = false }) => {
  const navigate = useNavigate();

  const handleActionClick = (url: string) => {
    if (!url) return;

    if (/^https?:/i.test(url)) {
      window.open(url, '_blank', 'noopener,noreferrer');
      return;
    }

    navigate(url);
  };

  const userPermissions = getUserPermissions();
  const allowAllByDefault = userPermissions.permissions.length === 0;

  const availableActions = (actions && actions.length > 0 ? actions : FALLBACK_ACTIONS)
    .filter((action) =>
      !action.permission || hasPermission(action.permission) || allowAllByDefault
    );

  if (loading) {
    return (
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {[1, 2, 3].map((i) => (
          <div key={i} className="bg-mono-white border border-mono-gray-200 rounded-xl p-6 shadow-lg">
            <div className="text-center">
              <div className="skeleton w-14 h-14 rounded-full mx-auto mb-4"></div>
              <div className="skeleton h-5 w-24 mx-auto"></div>
            </div>
          </div>
        ))}
      </div>
    );
  }

  if (availableActions.length === 0) {
    const emptyLabel = t('dashboard.no_quick_actions');
    return (
      <div className="text-center text-sm text-mono-gray-500 border border-dashed border-mono-gray-300 rounded-2xl py-12">
        {emptyLabel === 'dashboard.no_quick_actions'
          ? 'No quick actions available for your role'
          : emptyLabel}
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      {availableActions.map((action) => {
        const badgeObject = typeof action.badge === 'object' && action.badge !== null
          ? action.badge
          : typeof action.badge === 'number'
            ? { count: action.badge, variant: 'default' as const }
            : undefined;

        const badgeDisplay = badgeObject
          ? typeof badgeObject.count === 'number'
            ? badgeObject.count > 999 ? '999+' : badgeObject.count
            : badgeObject.count
          : null;

        const badgeVariantClasses: Record<string, string> = {
          default: 'bg-mono-black text-mono-white',
          success: 'bg-mono-gray-700 text-mono-white',
          warning: 'bg-mono-gray-600 text-mono-white',
          info: 'bg-mono-gray-500 text-mono-white',
          attention: 'bg-mono-black text-mono-white animate-pulse',
          error: 'bg-mono-black text-mono-white',
        };

        const badgeClasses = badgeObject
          ? badgeVariantClasses[badgeObject.variant || 'default']
          : '';

        return (
        <button
          key={action.id}
          onClick={() => handleActionClick(action.url)}
          className="relative bg-mono-white border border-mono-gray-200 rounded-2xl p-6 shadow-lg transition-all duration-200 transform hover:-translate-y-1 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-mono-black focus:ring-offset-2 text-left group"
          aria-label={`${action.title}${action.shortcut ? ` (${action.shortcut})` : ''}`}
        >
          {badgeObject && (typeof badgeObject.count === 'number' ? badgeObject.count > 0 : !!badgeObject.count) && (
            <span className={`absolute top-4 right-4 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold ${badgeClasses}`}>
              {badgeDisplay}
            </span>
          )}
          <div className="text-center space-y-3">
            <div className="inline-flex items-center justify-center w-14 h-14 rounded-full bg-mono-black text-mono-white group-hover:bg-mono-gray-800 transition-colors duration-200">
              <i className={`${action.icon} text-xl`} aria-hidden="true" />
            </div>
            <div className="space-y-1">
              <h6 className="font-semibold text-mono-gray-900 group-hover:text-mono-black transition-colors duration-200">
                {action.title}
              </h6>
              {action.description && (
                <p className="text-xs text-mono-gray-600">
                  {action.description}
                </p>
              )}
              {action.shortcut && (
                <span className="inline-flex items-center justify-center px-2 py-0.5 border border-mono-gray-300 rounded text-[11px] font-mono text-mono-gray-600">
                  {action.shortcut}
                </span>
              )}
            </div>
          </div>
        </button>
        );
      })}
    </div>
  );
};

export default QuickActions;
