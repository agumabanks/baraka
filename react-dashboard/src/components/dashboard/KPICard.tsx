import React from 'react';
import type { KPICard as KPICardType } from '../../types/dashboard';
import Spinner from '../ui/Spinner';

/**
 * KPI Card Component
 * Displays key performance indicator with monochrome styling
 */
interface KPICardProps extends KPICardType {
  /** Click handler for drill-down */
  onClick?: () => void;
}

const KPICard: React.FC<KPICardProps> = ({
  title,
  value,
  subtitle,
  icon,
  trend,
  state = 'neutral',
  drilldownRoute,
  tooltip,
  loading = false,
  onClick,
}) => {
  // State-specific styling (monochrome approach)
  const stateStyles = {
    success: 'border-l-4 border-l-mono-black',
    warning: 'border-l-4 border-l-mono-gray-600',
    neutral: 'border-l-4 border-l-mono-gray-300',
    critical: 'border-l-4 border-l-mono-black',
  };

  // Trend icon based on direction
  const getTrendIcon = () => {
    if (!trend) return null;
    
    switch (trend.direction) {
      case 'up':
        return <i className="fas fa-arrow-up text-xs" aria-hidden="true" />;
      case 'down':
        return <i className="fas fa-arrow-down text-xs" aria-hidden="true" />;
      case 'stable':
        return <i className="fas fa-minus text-xs" aria-hidden="true" />;
      default:
        return null;
    }
  };

  // Format value
  const formatValue = (val: number | string): string => {
    if (typeof val === 'number') {
      return val.toLocaleString();
    }
    return val;
  };

  const isClickable = !!drilldownRoute || !!onClick;
  const CardWrapper = isClickable ? 'button' : 'div';
  const wrapperProps = isClickable
    ? {
        onClick,
        type: 'button' as const,
        className: `w-full text-left transition-all hover:shadow-normal focus:outline-none focus:ring-2 focus:ring-mono-black focus:ring-offset-2 ${
          loading ? 'pointer-events-none' : ''
        }`,
      }
    : {
        className: 'w-full',
      };

  return (
    <CardWrapper {...wrapperProps}>
      <div
        className={`
          bg-mono-white border border-mono-gray-200 rounded-lg p-5 
          shadow-subtle transition-shadow h-full
          ${stateStyles[state]}
          ${isClickable && !loading ? 'hover:shadow-normal cursor-pointer' : ''}
        `}
        title={tooltip}
        role={isClickable ? 'button' : undefined}
        aria-label={`${title}: ${value}${subtitle ? `, ${subtitle}` : ''}${
          trend ? `, trend ${trend.direction} by ${trend.value}%` : ''
        }`}
      >
        {loading ? (
          // Loading state
          <div className="flex items-center justify-center h-24" aria-live="polite" aria-busy="true">
            <Spinner size="md" />
            <span className="sr-only">Loading {title}</span>
          </div>
        ) : (
          <div className="flex items-start gap-4">
            {/* Icon */}
            {icon && (
              <div
                className="flex-shrink-0 w-12 h-12 rounded-full bg-mono-gray-100 flex items-center justify-center"
                aria-hidden="true"
              >
                <i className={`${icon} text-mono-gray-700 text-xl`} />
              </div>
            )}

            {/* Content */}
            <div className="flex-1 min-w-0">
              {/* Title */}
              <h3 className="text-sm font-medium text-mono-gray-600 mb-1 truncate">
                {title}
              </h3>

              {/* Value */}
              <p className="text-2xl font-bold text-mono-black mb-1 truncate">
                {formatValue(value)}
              </p>

              {/* Subtitle and Trend */}
              <div className="flex items-center gap-2 text-xs">
                {subtitle && (
                  <span className="text-mono-gray-500">
                    {subtitle}
                  </span>
                )}

                {trend && (
                  <span
                    className={`
                      inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                      ${
                        trend.direction === 'up'
                          ? 'bg-mono-gray-900 text-mono-white'
                          : trend.direction === 'down'
                          ? 'bg-mono-gray-400 text-mono-white'
                          : 'bg-mono-gray-200 text-mono-gray-700'
                      }
                    `}
                    aria-label={`${trend.direction} by ${trend.value}%`}
                  >
                    {getTrendIcon()}
                    <span className="font-medium">
                      {trend.value}%
                    </span>
                  </span>
                )}
              </div>
            </div>

            {/* Drill-down indicator */}
            {isClickable && (
              <div className="flex-shrink-0 text-mono-gray-400" aria-hidden="true">
                <i className="fas fa-chevron-right text-sm" />
              </div>
            )}
          </div>
        )}
      </div>
    </CardWrapper>
  );
};

export default KPICard;