/**
 * Executive KPI Grid
 * Specialized KPI grid for executive dashboard with real-time indicators
 */

import React from 'react';
import type { ExecutiveKPIs } from '../../../types/analytics';
import KPICard from '../../dashboard/KPICard';
import LoadingSpinner from '../../ui/LoadingSpinner';
import Card from '../../ui/Card';
import { t } from '../../../lib/i18n';

interface ExecutiveKPIGridProps {
  /** Executive KPIs data */
  kpis: ExecutiveKPIs;
  /** Loading state */
  loading?: boolean;
  /** KPI click handler */
  onKPIClick?: (kpiId: string) => void;
  /** Whether real-time data is active */
  isRealTime?: boolean;
}

/**
 * Executive KPI Grid Component
 * Displays executive-level KPIs with real-time indicators and professional styling
 */
const ExecutiveKPIGrid: React.FC<ExecutiveKPIGridProps> = ({
  kpis,
  loading = false,
  onKPIClick,
  isRealTime = false,
}) => {
  const operationalEfficiencyValue = Number(kpis.operationalEfficiency.value ?? 0);
  const customerSatisfactionValue = Number(kpis.customerSatisfaction.value ?? 0);

  // Transform executive KPIs into KPICard format for consistent display
  const transformKPIs = () => {
    const kpiArray = [
      {
        id: 'revenue',
        title: 'Total Revenue',
        value: kpis.revenue.value,
        subtitle: kpis.revenue.subtitle || 'Current month',
        icon: 'fas fa-dollar-sign',
        trend: kpis.revenue.trend,
        state: kpis.revenue.state,
        drilldownRoute: '/analytics/financial/revenue',
        tooltip: 'Total revenue generated across all operations',
        kpi: 'revenue',
        loading: false,
      },
      {
        id: 'totalShipments',
        title: 'Total Shipments',
        value: kpis.totalShipments.value,
        subtitle: kpis.totalShipments.subtitle || 'This month',
        icon: 'fas fa-box',
        trend: kpis.totalShipments.trend,
        state: kpis.totalShipments.state,
        drilldownRoute: '/analytics/operational/shipments',
        tooltip: 'Total number of shipments processed',
        kpi: 'shipments',
        loading: false,
      },
      {
        id: 'activeCustomers',
        title: 'Active Customers',
        value: kpis.activeCustomers.value,
        subtitle: kpis.activeCustomers.subtitle || 'Last 30 days',
        icon: 'fas fa-users',
        trend: kpis.activeCustomers.trend,
        state: kpis.activeCustomers.state,
        drilldownRoute: '/analytics/customer/intelligence',
        tooltip: 'Number of active customers with recent activity',
        kpi: 'customers',
        loading: false,
      },
      {
        id: 'operationalEfficiency',
        title: 'Operational Efficiency',
        value: kpis.operationalEfficiency.value,
        subtitle: kpis.operationalEfficiency.subtitle || 'Overall score',
        icon: 'fas fa-cogs',
        trend: kpis.operationalEfficiency.trend,
        state: kpis.operationalEfficiency.state,
        drilldownRoute: '/analytics/operational/efficiency',
        tooltip: 'Overall operational efficiency score',
        kpi: 'efficiency',
        loading: false,
      },
      {
        id: 'profitability',
        title: 'Profitability',
        value: kpis.profitability.value,
        subtitle: kpis.profitability.subtitle || 'Net profit margin',
        icon: 'fas fa-chart-line',
        trend: kpis.profitability.trend,
        state: kpis.profitability.state,
        drilldownRoute: '/analytics/financial/profitability',
        tooltip: 'Net profitability and margin analysis',
        kpi: 'profitability',
        loading: false,
      },
      {
        id: 'customerSatisfaction',
        title: 'Customer Satisfaction',
        value: kpis.customerSatisfaction.value,
        subtitle: kpis.customerSatisfaction.subtitle || 'NPS Score',
        icon: 'fas fa-heart',
        trend: kpis.customerSatisfaction.trend,
        state: kpis.customerSatisfaction.state,
        drilldownRoute: '/analytics/customer/satisfaction',
        tooltip: 'Customer satisfaction and NPS scores',
        kpi: 'satisfaction',
        loading: false,
      },
    ];

    return kpiArray;
  };

  const handleKPIClick = (kpiId: string) => {
    if (onKPIClick) {
      onKPIClick(kpiId);
    }
  };

  // Handle loading state
  if (loading) {
    return (
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {[1, 2, 3, 4, 5, 6].map((i) => (
          <Card key={i} className="h-32">
            <LoadingSpinner message="Loading KPI..." />
          </Card>
        ))}
      </div>
    );
  }

  const kpiArray = transformKPIs();

  return (
    <div className="space-y-6">
      {/* Real-time indicator */}
      {isRealTime && (
        <div className="flex items-center justify-center p-3 bg-mono-gray-50 rounded-lg border border-mono-gray-200">
          <div className="flex items-center gap-2">
            <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse" />
            <span className="text-sm text-mono-gray-600 font-medium">
              Live Data Streaming Active
            </span>
            <i className="fas fa-bolt text-yellow-500" aria-hidden="true" />
          </div>
        </div>
      )}

      {/* KPI Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {kpiArray.map((kpi) => (
          <div
            key={kpi.id}
            className="relative"
            role="group"
            aria-label={`Executive KPI: ${kpi.title}`}
          >
            {/* Real-time indicator for individual KPIs */}
            {isRealTime && (
              <div className="absolute -top-2 -right-2 z-10">
                <div className="w-3 h-3 bg-green-500 rounded-full animate-pulse" 
                     title="Real-time data active" />
              </div>
            )}
            
            <KPICard
              {...kpi}
              onClick={() => handleKPIClick(kpi.id)}
            />
          </div>
        ))}
      </div>

      {/* Summary insight */}
      <Card className="p-6 bg-gradient-to-r from-mono-gray-50 to-mono-gray-100">
        <div className="flex items-start justify-between">
          <div className="flex-1">
            <h3 className="text-lg font-semibold text-mono-black mb-2">
              Executive Summary
            </h3>
            <div className="text-sm text-mono-gray-600 space-y-1">
              {kpis.revenue.value && (
                <p>
                  <span className="font-medium">Revenue Growth:</span> 
                  {kpis.revenue.trend ? (
                    <span className={`ml-1 ${kpis.revenue.trend.direction === 'up' ? 'text-green-600' : 'text-red-600'}`}>
                      {kpis.revenue.trend.direction === 'up' ? '+' : ''}{kpis.revenue.trend.value}%
                    </span>
                  ) : 'Data updating...'}
                </p>
              )}
              {kpis.operationalEfficiency.value && (
                <p>
                  <span className="font-medium">Operational Status:</span> 
                  <span className="ml-1">
                    {operationalEfficiencyValue > 85 ? 'Excellent' : 
                     operationalEfficiencyValue > 70 ? 'Good' : 
                     operationalEfficiencyValue > 55 ? 'Fair' : 'Needs Attention'} 
                    ({operationalEfficiencyValue}%)
                  </span>
                </p>
              )}
              {kpis.customerSatisfaction.value && (
                <p>
                  <span className="font-medium">Customer Sentiment:</span> 
                  <span className="ml-1">
                    {customerSatisfactionValue > 80 ? 'Very Positive' : 
                     customerSatisfactionValue > 60 ? 'Positive' : 
                     customerSatisfactionValue > 40 ? 'Neutral' : 'Needs Improvement'} 
                    ({customerSatisfactionValue}%)
                  </span>
                </p>
              )}
            </div>
          </div>
          <div className="text-right">
            <div className="text-xs text-mono-gray-500">
              Last updated
            </div>
            <div className="text-sm font-medium text-mono-black">
              {new Date().toLocaleTimeString()}
            </div>
          </div>
        </div>
      </Card>

      {/* Quick Insights */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card className="p-4">
          <h4 className="text-sm font-semibold text-mono-black mb-3">
            <i className="fas fa-trending-up mr-2 text-mono-gray-600" aria-hidden="true" />
            Top Performing Areas
          </h4>
          <div className="space-y-2">
            {operationalEfficiencyValue > 80 && (
              <div className="flex items-center justify-between text-sm">
                <span className="text-mono-gray-600">Operations</span>
                <span className="text-green-600 font-medium">
                  {operationalEfficiencyValue}%
                </span>
              </div>
            )}
            {customerSatisfactionValue > 75 && (
              <div className="flex items-center justify-between text-sm">
                <span className="text-mono-gray-600">Customer Service</span>
                <span className="text-green-600 font-medium">
                  {customerSatisfactionValue}%
                </span>
              </div>
            )}
            {kpis.profitability.value && (
              <div className="flex items-center justify-between text-sm">
                <span className="text-mono-gray-600">Profitability</span>
                <span className="text-green-600 font-medium">
                  {kpis.profitability.value}
                </span>
              </div>
            )}
          </div>
        </Card>

        <Card className="p-4">
          <h4 className="text-sm font-semibold text-mono-black mb-3">
            <i className="fas fa-exclamation-triangle mr-2 text-mono-gray-600" aria-hidden="true" />
            Areas Needing Attention
          </h4>
          <div className="space-y-2">
            {operationalEfficiencyValue < 70 && (
              <div className="flex items-center justify-between text-sm">
                <span className="text-mono-gray-600">Operations</span>
                <span className="text-red-600 font-medium">
                  {operationalEfficiencyValue}%
                </span>
              </div>
            )}
            {customerSatisfactionValue < 60 && (
              <div className="flex items-center justify-between text-sm">
                <span className="text-mono-gray-600">Customer Service</span>
                <span className="text-red-600 font-medium">
                  {customerSatisfactionValue}%
                </span>
              </div>
            )}
            <div className="text-xs text-mono-gray-500">
              Click on KPIs above for detailed analysis
            </div>
          </div>
        </Card>
      </div>
    </div>
  );
};

export default ExecutiveKPIGrid;
