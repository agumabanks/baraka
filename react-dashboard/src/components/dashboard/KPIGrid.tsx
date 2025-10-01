import React from 'react';
import KPICard from './KPICard';
import type { KPICard as KPICardType } from '../../types/dashboard';

/**
 * KPI Grid Component
 * Responsive grid layout for KPI cards with monochrome styling
 */
interface KPIGridProps {
  /** Array of KPI cards to display */
  kpis: KPICardType[];
  /** Loading state */
  loading?: boolean;
  /** Number of columns for different breakpoints */
  columns?: {
    mobile?: number;
    tablet?: number;
    desktop?: number;
    wide?: number;
  };
  /** Click handler for KPI cards */
  onKPIClick?: (kpi: KPICardType) => void;
}

const KPIGrid: React.FC<KPIGridProps> = ({
  kpis,
  loading = false,
  columns = {
    mobile: 1,
    tablet: 2,
    desktop: 3,
    wide: 4,
  },
  onKPIClick,
}) => {
  const resolveGridClass = (prefix: string | null, count?: number) => {
    if (!count) return null;
    const map: Record<number, string> = {
      1: 'grid-cols-1',
      2: 'grid-cols-2',
      3: 'grid-cols-3',
      4: 'grid-cols-4',
      5: 'grid-cols-5',
      6: 'grid-cols-6',
    };
    const utility = map[count];
    if (!utility) return null;
    return prefix ? `${prefix}:${utility}` : utility;
  };

  // Build responsive grid classes
  const gridClasses = [
    'grid',
    'gap-4',
    resolveGridClass(null, columns.mobile ?? 1),
    resolveGridClass('md', columns.tablet ?? 2),
    resolveGridClass('lg', columns.desktop ?? 3),
    resolveGridClass('xl', columns.wide ?? 4),
  ]
    .filter(Boolean)
    .join(' ');

  // Handle KPI click
  const handleKPIClick = (kpi: KPICardType) => {
    if (onKPIClick) {
      onKPIClick(kpi);
    } else if (kpi.drilldownRoute) {
      // Navigate to drill-down route
      window.location.href = kpi.drilldownRoute;
    }
  };

  return (
    <div
      className={gridClasses}
      role="region"
      aria-label="Key Performance Indicators"
    >
      {kpis.map((kpi) => (
        <KPICard
          key={kpi.id}
          {...kpi}
          loading={loading || kpi.loading}
          onClick={() => handleKPIClick(kpi)}
        />
      ))}
    </div>
  );
};

export default KPIGrid;
