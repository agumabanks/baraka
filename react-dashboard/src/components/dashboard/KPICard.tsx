import React, { type ReactNode } from 'react';
import { useNavigate } from 'react-router-dom';
import type { KPICard as KPICardType } from '../../types/dashboard';

/**
 * KPI Card Component
 * Matches exact Blade kpi-card design with skeleton loading states
 * Pure monochrome styling with drill-down functionality
 */
interface KPICardProps extends KPICardType {
  /** Click handler for drill-down */
  onClick?: () => void;
  /** Loading state */
  loading?: boolean;
  /** Optional custom content (e.g., SLA gauge) */
  children?: ReactNode;
}

const KPICard: React.FC<KPICardProps> = ({
  title,
  value,
  subtitle,
  trend,
  state = 'loading',
  drilldownRoute,
  tooltip,
  kpi,
  loading = false,
  onClick,
  children,
}) => {
  const navigate = useNavigate();

  // Trend display logic
  const trendDirection = trend?.direction ?? 'stable';
  const trendValue = trend?.value ?? 0;
  const trendColor = trendDirection === 'up'
    ? 'text-mono-black'
    : trendDirection === 'down'
      ? 'text-mono-gray-600'
      : 'text-mono-gray-500';

  const displayValue = typeof value === 'number' ? value.toLocaleString() : value;

  // Handle drill-down click
  const handleClick = () => {
    if (drilldownRoute) {
      // Store drill-down data in sessionStorage (matching Blade implementation)
      const drilldownData = {
        url: drilldownRoute,
        title,
        kpi,
        filters: new URLSearchParams(window.location.search).toString()
      };

      sessionStorage.setItem('drilldown-filters', JSON.stringify(drilldownData.filters));
      sessionStorage.setItem('breadcrumb-path', JSON.stringify([
        {
          title: 'Dashboard',
          url: '/dashboard',
          active: false,
          icon: 'fas fa-tachometer-alt'
        },
        {
          title,
          url: drilldownRoute,
          active: true,
          icon: 'fas fa-chart-bar'
        }
      ]));

      navigate(drilldownRoute);
    }

    if (onClick) {
      onClick();
    }
  };

  const isClickable = !!drilldownRoute || !!onClick;
  const cardClasses = [
    'kpi-card',
    isClickable ? 'kpi-card--interactive' : '',
    state ? `kpi-card--${state}` : '',
  ].filter(Boolean).join(' ');

  if (loading || state === 'loading') {
    return (
      <div
        className="kpi-card"
        data-loading="true"
        aria-live="polite"
        aria-label={`Loading ${title}`}
      >
        <div className="kpi-header">
          <h3 className="kpi-title">
            <div className="skeleton skeleton-title"></div>
          </h3>
        </div>
        <div className="kpi-value">
          <div className="skeleton skeleton-value"></div>
        </div>
        <div className="kpi-subtitle">
          <div className="skeleton skeleton-subtitle"></div>
        </div>
      </div>
    );
  }

  return (
    <div
      className={cardClasses}
      data-kpi={kpi}
      title={tooltip ?? undefined}
      onClick={isClickable ? handleClick : undefined}
      role={isClickable ? 'button' : undefined}
      tabIndex={isClickable ? 0 : undefined}
      onKeyDown={isClickable ? (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          handleClick();
        }
      } : undefined}
      aria-label={`${title}: ${value}${subtitle ? `, ${subtitle}` : ''}${
        trend ? `, trend ${trendDirection} by ${trendValue}%` : ''
      }`}
    >
      <div className="kpi-header">
        <h3 className="kpi-title">
          {title}
        </h3>
        {trend && (
          <div className="kpi-trend">
            <span className={`trend-indicator ${trendColor}`}>
              {trendDirection === 'up' ? '↑' : trendDirection === 'down' ? '↓' : '→'}
            </span>
            <span className={`trend-value ${trendColor}`}>
              {trendDirection === 'up' ? '+' : trendDirection === 'down' ? '-' : ''}{Math.abs(trendValue)}%
            </span>
          </div>
        )}
      </div>

      <div className="kpi-value">
        {state === 'empty' ? (
          <span className="text-muted">--</span>
        ) : (
          displayValue
        )}
      </div>

      <div className="kpi-subtitle">
        {subtitle}
      </div>

      {children && (
        <div className="kpi-custom-content">
          {children}
        </div>
      )}

      {drilldownRoute && (
        <a
          href={drilldownRoute}
          className="kpi-drilldown"
          aria-label={`View details for ${title}`}
          data-drilldown={JSON.stringify({
            url: drilldownRoute,
            title,
            kpi,
            filters: new URLSearchParams(window.location.search).toString()
          })}
          data-breadcrumb-update="true"
          onClick={(e) => e.stopPropagation()}
        >
          View Details
        </a>
      )}
    </div>
  );
};

export default KPICard;
