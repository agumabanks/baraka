/**
 * Executive Chart Section
 * High-level business performance charts for executive dashboard
 */

import React, { useMemo } from 'react';
import Card from '../../ui/Card';
import LineChart from '../../dashboard/charts/LineChart';
import BarChart from '../../dashboard/charts/BarChart';
import AreaChart from '../../dashboard/charts/AreaChart';
import PieChart from '../../dashboard/charts/PieChart';
import type { ExecutiveKPIs, FilterConfig, ChartDataPoint } from '../../../types/analytics';
import { CHART_SIZING } from '../../../utils/chartConfig';

interface ExecutiveChartSectionProps {
  /** Executive KPIs data */
  kpisData: ExecutiveKPIs;
  /** Active filters */
  filters: FilterConfig[];
  /** Loading state */
  loading?: boolean;
}

/**
 * Executive Chart Section Component
 * Displays high-level business performance charts with executive-level insights
 */
const ExecutiveChartSection: React.FC<ExecutiveChartSectionProps> = ({
  kpisData,
  filters,
  loading = false,
}) => {
  // Generate mock data for charts based on KPIs (in real implementation, this would come from API)
  const chartData = useMemo(() => {
    const generateMockData = (baseValue: number, trend: 'up' | 'down' | 'stable') => {
      const data: ChartDataPoint[] = [];
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
      const trendMultiplier = trend === 'up' ? 1.1 : trend === 'down' ? 0.9 : 1.0;
      
      months.forEach((month, index) => {
        const value = baseValue * Math.pow(trendMultiplier, index / 6) * (0.8 + Math.random() * 0.4);
        data.push({
          label: month,
          value: Math.round(value),
        });
      });
      
      return data;
    };

    return {
      revenue: generateMockData(
        typeof kpisData.revenue.value === 'number' ? kpisData.revenue.value / 6 : 100000, 
        kpisData.revenue.trend?.direction || 'up'
      ),
      shipments: generateMockData(
        typeof kpisData.totalShipments.value === 'number' ? kpisData.totalShipments.value / 6 : 1000,
        'up'
      ),
      customers: generateMockData(
        typeof kpisData.activeCustomers.value === 'number' ? kpisData.activeCustomers.value / 6 : 500,
        kpisData.activeCustomers.trend?.direction || 'up'
      ),
      efficiency: generateMockData(
        typeof kpisData.operationalEfficiency.value === 'number' ? kpisData.operationalEfficiency.value : 75,
        kpisData.operationalEfficiency.trend?.direction || 'up'
      ),
      profitability: generateMockData(
        typeof kpisData.profitability.value === 'number' ? kpisData.profitability.value : 20,
        kpisData.profitability.trend?.direction || 'up'
      ),
      satisfaction: generateMockData(
        typeof kpisData.customerSatisfaction.value === 'number' ? kpisData.customerSatisfaction.value : 80,
        kpisData.customerSatisfaction.trend?.direction || 'up'
      ),
    };
  }, [kpisData]);

  // Mock distribution data for pie charts
  const distributionData = useMemo(() => {
    return [
      { label: 'Revenue', value: 45 },
      { label: 'Operations', value: 25 },
      { label: 'Customer Service', value: 20 },
      { label: 'Technology', value: 10 },
    ];
  }, []);

  return (
    <section className="space-y-6">
      <header>
        <h2 className="text-lg font-semibold text-mono-black">
          Business Performance Trends
        </h2>
        <p className="text-sm text-mono-gray-600">
          Executive-level performance metrics and trend analysis
        </p>
      </header>

      {/* Primary Charts Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Revenue Trend */}
        <Card className="p-6">
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-semibold text-mono-black">
                Revenue Performance
              </h3>
              <div className="flex items-center gap-2">
                {kpisData.revenue.trend && (
                  <span className={`text-sm font-medium ${
                    kpisData.revenue.trend.direction === 'up' ? 'text-green-600' : 'text-red-600'
                  }`}>
                    <i className={`fas fa-arrow-${kpisData.revenue.trend.direction} mr-1`} />
                    {kpisData.revenue.trend.value}%
                  </span>
                )}
                <i className="fas fa-dollar-sign text-mono-gray-500" />
              </div>
            </div>
            <LineChart
              data={chartData.revenue}
              height={CHART_SIZING.responsive.mobile.height}
              loading={loading}
              ariaLabel="Revenue performance trend over time"
            />
          </div>
        </Card>

        {/* Shipment Volume Trend */}
        <Card className="p-6">
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-semibold text-mono-black">
                Shipment Volume
              </h3>
              <div className="flex items-center gap-2">
                <span className="text-sm font-medium text-mono-black">
                  {kpisData.totalShipments.value}
                </span>
                <i className="fas fa-box text-mono-gray-500" />
              </div>
            </div>
            <BarChart
              data={chartData.shipments}
              height={CHART_SIZING.responsive.mobile.height}
              loading={loading}
              ariaLabel="Monthly shipment volume"
            />
          </div>
        </Card>
      </div>

      {/* Secondary Charts Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Customer Growth */}
        <Card className="p-6">
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-semibold text-mono-black">
                Customer Growth
              </h3>
              <div className="flex items-center gap-2">
                {kpisData.activeCustomers.trend && (
                  <span className={`text-sm font-medium ${
                    kpisData.activeCustomers.trend.direction === 'up' ? 'text-green-600' : 'text-red-600'
                  }`}>
                    {kpisData.activeCustomers.trend.value}%
                  </span>
                )}
                <i className="fas fa-users text-mono-gray-500" />
              </div>
            </div>
            <AreaChart
              data={chartData.customers}
              height={200}
              loading={loading}
              ariaLabel="Customer growth over time"
            />
          </div>
        </Card>

        {/* Operational Efficiency */}
        <Card className="p-6">
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-semibold text-mono-black">
                Efficiency Score
              </h3>
              <div className="flex items-center gap-2">
                <span className="text-lg font-bold text-mono-black">
                  {kpisData.operationalEfficiency.value}%
                </span>
                <i className="fas fa-cogs text-mono-gray-500" />
              </div>
            </div>
            <PieChart
              data={[
                { label: 'Efficiency', value: kpisData.operationalEfficiency.value as number || 75 },
                { label: 'Opportunity', value: 100 - (kpisData.operationalEfficiency.value as number || 75) },
              ]}
              donut
              height={200}
              loading={loading}
              ariaLabel="Operational efficiency breakdown"
            />
          </div>
        </Card>

        {/* Customer Satisfaction */}
        <Card className="p-6">
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-semibold text-mono-black">
                Satisfaction Trend
              </h3>
              <div className="flex items-center gap-2">
                {kpisData.customerSatisfaction.trend && (
                  <span className={`text-sm font-medium ${
                    kpisData.customerSatisfaction.trend.direction === 'up' ? 'text-green-600' : 'text-red-600'
                  }`}>
                    {kpisData.customerSatisfaction.trend.value}%
                  </span>
                )}
                <i className="fas fa-heart text-mono-gray-500" />
              </div>
            </div>
            <LineChart
              data={chartData.satisfaction}
              height={200}
              loading={loading}
              ariaLabel="Customer satisfaction trend"
            />
          </div>
        </Card>
      </div>

      {/* Business Distribution Overview */}
      <Card className="p-6">
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <h3 className="text-lg font-semibold text-mono-black">
              Business Performance Distribution
            </h3>
            <span className="text-sm text-mono-gray-600">
              Current Period Analysis
            </span>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h4 className="text-sm font-semibold text-mono-black mb-3">
                Performance by Category
              </h4>
              <PieChart
                data={distributionData}
                height={250}
                loading={loading}
                ariaLabel="Business performance distribution by category"
              />
            </div>
            <div className="space-y-4">
              <h4 className="text-sm font-semibold text-mono-black">
                Key Insights
              </h4>
              <div className="space-y-3">
                <div className="flex items-center justify-between p-3 bg-mono-gray-50 rounded-lg">
                  <span className="text-sm text-mono-gray-600">Revenue Growth</span>
                  <span className="text-sm font-medium text-green-600">
                    {kpisData.revenue.trend?.direction === 'up' ? '+' : ''}{kpisData.revenue.trend?.value || 0}%
                  </span>
                </div>
                <div className="flex items-center justify-between p-3 bg-mono-gray-50 rounded-lg">
                  <span className="text-sm text-mono-gray-600">Operational Efficiency</span>
                  <span className="text-sm font-medium text-mono-black">
                    {kpisData.operationalEfficiency.value}%
                  </span>
                </div>
                <div className="flex items-center justify-between p-3 bg-mono-gray-50 rounded-lg">
                  <span className="text-sm text-mono-gray-600">Customer Satisfaction</span>
                  <span className="text-sm font-medium text-mono-black">
                    {kpisData.customerSatisfaction.value}%
                  </span>
                </div>
                <div className="flex items-center justify-between p-3 bg-mono-gray-50 rounded-lg">
                  <span className="text-sm text-mono-gray-600">Profitability</span>
                  <span className="text-sm font-medium text-mono-black">
                    {kpisData.profitability.value}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Card>

      {/* Executive Summary */}
      <Card className="p-6 bg-gradient-to-r from-blue-50 to-indigo-50">
        <div className="space-y-4">
          <h3 className="text-lg font-semibold text-mono-black">
            Executive Summary
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h4 className="text-sm font-semibold text-mono-black mb-2">Strengths</h4>
              <ul className="text-sm text-mono-gray-700 space-y-1">
                {kpisData.revenue.trend?.direction === 'up' && (
                  <li className="flex items-center gap-2">
                    <i className="fas fa-check-circle text-green-600" />
                    Strong revenue growth momentum
                  </li>
                )}
                {Number(kpisData.operationalEfficiency.value ?? 0) > 80 && (
                  <li className="flex items-center gap-2">
                    <i className="fas fa-check-circle text-green-600" />
                    High operational efficiency
                  </li>
                )}
                {Number(kpisData.customerSatisfaction.value ?? 0) > 80 && (
                  <li className="flex items-center gap-2">
                    <i className="fas fa-check-circle text-green-600" />
                    Excellent customer satisfaction
                  </li>
                )}
                <li className="flex items-center gap-2">
                  <i className="fas fa-check-circle text-green-600" />
                  Active customer base growth
                </li>
              </ul>
            </div>
            <div>
              <h4 className="text-sm font-semibold text-mono-black mb-2">Focus Areas</h4>
              <ul className="text-sm text-mono-gray-700 space-y-1">
                {Number(kpisData.operationalEfficiency.value ?? 0) < 75 && (
                  <li className="flex items-center gap-2">
                    <i className="fas fa-exclamation-triangle text-yellow-600" />
                    Improve operational efficiency
                  </li>
                )}
                {Number(kpisData.customerSatisfaction.value ?? 0) < 70 && (
                  <li className="flex items-center gap-2">
                    <i className="fas fa-exclamation-triangle text-yellow-600" />
                    Enhance customer satisfaction
                  </li>
                )}
                {kpisData.revenue.trend?.direction === 'down' && (
                  <li className="flex items-center gap-2">
                    <i className="fas fa-exclamation-triangle text-yellow-600" />
                    Address revenue decline
                  </li>
                )}
                <li className="flex items-center gap-2">
                  <i className="fas fa-lightbulb text-blue-600" />
                  Optimize delivery processes
                </li>
              </ul>
            </div>
          </div>
        </div>
      </Card>
    </section>
  );
};

export default ExecutiveChartSection;
