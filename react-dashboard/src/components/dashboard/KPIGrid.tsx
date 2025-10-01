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
  // Build responsive grid classes
  const gridClasses = [
    'grid gap-4',
    `grid-cols-${columns.mobile}`,
    `md:grid-cols-${columns.tablet}`,
    `lg:grid-cols-${columns.desktop}`,
    `xl:grid-cols-${columns.wide}`,
  ].join(' ');

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