import React from 'react';
import KPIGrid from '../components/dashboard/KPIGrid';
import ChartSection from '../components/dashboard/ChartSection';
import WorkflowQueue from '../components/dashboard/WorkflowQueue';
import Card from '../components/ui/Card';
import Badge from '../components/ui/Badge';
import Button from '../components/ui/Button';
import LoadingSpinner from '../components/ui/LoadingSpinner';
import SkeletonCard, { SkeletonStatementCard, SkeletonChart } from '../components/dashboard/SkeletonCard';
import { useDashboardData, transformDashboardData } from '../hooks/useDashboardData';
import { mockDashboardData } from '../data/mockDashboardData';
import type { KPICard as KPICardType, QuickAction } from '../types/dashboard';

/**
 * Dashboard Page Component
 * Main dashboard page with monochrome Steve Jobs design standards
 */
const Dashboard: React.FC = () => {
  // Fetch dashboard data from API
  const { data: apiResponse, isLoading, isError, error, refetch } = useDashboardData();
  
  // Transform API data or use mock data as fallback
  const dashboardData = apiResponse?.success 
    ? transformDashboardData(apiResponse) 
    : mockDashboardData;
  
  // Use environment variable to determine if we should use mock data in development
  const useMockData = import.meta.env.DEV && !apiResponse;

  // Handle KPI card click
  const handleKPIClick = (kpi: KPICardType) => {
    console.log('KPI clicked:', kpi.id);
    // In a real app, this would navigate to the drill-down page
  };

  // Handle workflow item click
  const handleWorkflowItemClick = (itemId: string) => {
    console.log('Workflow item clicked:', itemId);
    // In a real app, this would navigate to the workflow details
  };

  // Show loading spinner on initial load
  if (isLoading && !dashboardData) {
    return <LoadingSpinner message="Loading dashboard data..." />;
  }

  // Show error state with retry option
  if (isError && !useMockData) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px]">
        <Card className="max-w-md text-center">
          <div className="space-y-4">
            <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-mono-black text-mono-white">
              <i className="fas fa-exclamation-triangle text-2xl" aria-hidden="true" />
            </div>
            <div>
              <h2 className="text-xl font-bold text-mono-black mb-2">
                Failed to Load Dashboard
              </h2>
              <p className="text-mono-gray-600 mb-4">
                {error instanceof Error ? error.message : 'Unable to fetch dashboard data. Please try again.'}
              </p>
            </div>
            <Button
              variant="primary"
              size="md"
              onClick={() => refetch()}
            >
              <i className="fas fa-redo mr-2" aria-hidden="true" />
              Retry
            </Button>
          </div>
        </Card>
      </div>
    );
  }

  // Ensure we have data to display
  if (!dashboardData) {
    return <LoadingSpinner message="Preparing dashboard..." />;
  }

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-mono-black">
            Dashboard
          </h1>
          <p className="text-mono-gray-600 mt-1">
            Overview of your logistics operations
          </p>
        </div>

        {/* Date Filter & Refresh */}
        <div className="flex items-center gap-3">
          {useMockData && (
            <Badge variant="solid" size="sm">
              <i className="fas fa-flask mr-2" aria-hidden="true" />
              Demo Mode
            </Badge>
          )}
          <Badge variant="outline" size="sm">
            <i className="fas fa-calendar mr-2" aria-hidden="true" />
            {dashboardData.dateFilter.preset === 'month' ? 'This Month' : 'Custom Range'}
          </Badge>
          <Button
            variant="secondary"
            size="sm"
            onClick={() => refetch()}
            disabled={isLoading}
          >
            <i className={`fas fa-sync mr-2 ${isLoading ? 'animate-spin' : ''}`} aria-hidden="true" />
            Refresh
          </Button>
        </div>
      </div>

      {/* Business Health KPIs - Row 1 */}
      <div className="space-y-4">
        <h2 className="text-lg font-semibold text-mono-black">
          Business Health
        </h2>
        {isLoading ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {[1, 2, 3, 4].map((i) => (
              <SkeletonCard key={i} />
            ))}
          </div>
        ) : (
          <KPIGrid
            kpis={[
              dashboardData.healthKPIs.slaStatus,
              dashboardData.healthKPIs.exceptions,
              dashboardData.healthKPIs.onTimeDelivery,
              dashboardData.healthKPIs.openTickets,
            ].filter((kpi): kpi is KPICardType => kpi !== undefined)}
            loading={false}
            columns={{
              mobile: 1,
              tablet: 2,
              desktop: 4,
              wide: 4,
            }}
            onKPIClick={handleKPIClick}
          />
        )}
      </div>

      {/* Work in Progress - Row 2 */}
      <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
        {/* Today's Queue */}
        <WorkflowQueue
          items={dashboardData.workflowQueue}
          loading={isLoading}
          onItemClick={(item) => handleWorkflowItemClick(item.id)}
          maxItems={5}
        />

        {/* Cash Collection Chart */}
        {isLoading ? (
          <SkeletonChart />
        ) : dashboardData.charts.cashCollection ? (
          <ChartSection
            config={dashboardData.charts.cashCollection}
            loading={isLoading}
            error={isError ? (error instanceof Error ? error.message : 'Failed to load chart data') : undefined}
          />
        ) : null}
      </div>

      {/* Financial Statements - Row 3 */}
      <div className="space-y-4">
        <h2 className="text-lg font-semibold text-mono-black">
          Financial Statements
        </h2>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {/* Delivery Man Statement */}
          {isLoading ? (
            <SkeletonStatementCard />
          ) : dashboardData.statements.deliveryMan ? (
            <Card
              header={
                <h3 className="text-base font-semibold text-mono-black">
                  Delivery Personnel
                </h3>
              }
            >
              <div className="space-y-3">
                <div className="flex justify-between items-center">
                  <span className="text-sm text-mono-gray-600">Income</span>
                  <span className="font-semibold text-mono-black">
                    {dashboardData.statements.deliveryMan.currency}
                    {dashboardData.statements.deliveryMan.income.toLocaleString()}
                  </span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm text-mono-gray-600">Expense</span>
                  <span className="font-semibold text-mono-gray-700">
                    {dashboardData.statements.deliveryMan.currency}
                    {dashboardData.statements.deliveryMan.expense.toLocaleString()}
                  </span>
                </div>
                <hr className="border-mono-gray-200" />
                <div className="flex justify-between items-center">
                  <span className="text-sm font-medium text-mono-gray-900">Balance</span>
                  <span className="font-bold text-mono-black">
                    {dashboardData.statements.deliveryMan.currency}
                    {dashboardData.statements.deliveryMan.balance.toLocaleString()}
                  </span>
                </div>
              </div>
            </Card>
          ) : null}

          {/* Merchant Statement */}
          {isLoading ? (
            <SkeletonStatementCard />
          ) : dashboardData.statements.merchant ? (
            <Card
              header={
                <h3 className="text-base font-semibold text-mono-black">
                  Merchants
                </h3>
              }
            >
              <div className="space-y-3">
                <div className="flex justify-between items-center">
                  <span className="text-sm text-mono-gray-600">Income</span>
                  <span className="font-semibold text-mono-black">
                    {dashboardData.statements.merchant.currency}
                    {dashboardData.statements.merchant.income.toLocaleString()}
                  </span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm text-mono-gray-600">Expense</span>
                  <span className="font-semibold text-mono-gray-700">
                    {dashboardData.statements.merchant.currency}
                    {dashboardData.statements.merchant.expense.toLocaleString()}
                  </span>
                </div>
                <hr className="border-mono-gray-200" />
                <div className="flex justify-between items-center">
                  <span className="text-sm font-medium text-mono-gray-900">Balance</span>
                  <span className="font-bold text-mono-black">
                    {dashboardData.statements.merchant.currency}
                    {dashboardData.statements.merchant.balance.toLocaleString()}
                  </span>
                </div>
              </div>
            </Card>
          ) : null}

          {/* Hub Statement */}
          {isLoading ? (
            <SkeletonStatementCard />
          ) : dashboardData.statements.hub ? (
            <Card
              header={
                <h3 className="text-base font-semibold text-mono-black">
                  Hubs
                </h3>
              }
            >
              <div className="space-y-3">
                <div className="flex justify-between items-center">
                  <span className="text-sm text-mono-gray-600">Income</span>
                  <span className="font-semibold text-mono-black">
                    {dashboardData.statements.hub.currency}
                    {dashboardData.statements.hub.income.toLocaleString()}
                  </span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm text-mono-gray-600">Expense</span>
                  <span className="font-semibold text-mono-gray-700">
                    {dashboardData.statements.hub.currency}
                    {dashboardData.statements.hub.expense.toLocaleString()}
                  </span>
                </div>
                <hr className="border-mono-gray-200" />
                <div className="flex justify-between items-center">
                  <span className="text-sm font-medium text-mono-gray-900">Balance</span>
                  <span className="font-bold text-mono-black">
                    {dashboardData.statements.hub.currency}
                    {dashboardData.statements.hub.balance.toLocaleString()}
                  </span>
                </div>
              </div>
            </Card>
          ) : null}
        </div>
      </div>

      {/* Charts - Row 4 */}
      <div className="space-y-4">
        <h2 className="text-lg font-semibold text-mono-black">
          Analytics & Trends
        </h2>
        <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
          {isLoading ? (
            <>
              <SkeletonChart />
              <SkeletonChart />
            </>
          ) : (
            <>
              {dashboardData.charts.incomeExpense && (
                <ChartSection
                  config={dashboardData.charts.incomeExpense}
                  loading={isLoading}
                  error={isError ? (error instanceof Error ? error.message : 'Failed to load chart data') : undefined}
                />
              )}
              {dashboardData.charts.courierRevenue && (
                <ChartSection
                  config={dashboardData.charts.courierRevenue}
                  loading={isLoading}
                  error={isError ? (error instanceof Error ? error.message : 'Failed to load chart data') : undefined}
                />
              )}
            </>
          )}
        </div>
      </div>

      {/* Core KPIs - Row 5 */}
      <div className="space-y-4">
        <h2 className="text-lg font-semibold text-mono-black">
          Core Metrics
        </h2>
        {isLoading ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
              <SkeletonCard key={i} />
            ))}
          </div>
        ) : (
          <KPIGrid
            kpis={dashboardData.coreKPIs}
            loading={false}
            columns={{
              mobile: 1,
              tablet: 2,
              desktop: 3,
              wide: 4,
            }}
            onKPIClick={handleKPIClick}
          />
        )}
      </div>

      {/* Quick Actions - Row 6 */}
      <div className="space-y-4">
        <h2 className="text-lg font-semibold text-mono-black">
          Quick Actions
        </h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {dashboardData.quickActions.map((action: QuickAction) => (
            <button
              key={action.id}
              className="text-left"
              onClick={() => window.location.href = action.url}
            >
              <Card className="hover:shadow-normal transition-shadow cursor-pointer h-full">
                <div className="text-center">
                  <div className="inline-flex items-center justify-center w-14 h-14 rounded-full bg-mono-black text-mono-white mb-4">
                    <i className={`${action.icon} text-xl`} aria-hidden="true" />
                  </div>
                  <h3 className="font-semibold text-mono-black mb-1">
                    {action.title}
                  </h3>
                  {action.badge && (
                    <Badge variant="solid" size="sm">
                      {action.badge.toLocaleString()}
                    </Badge>
                  )}
                </div>
              </Card>
            </button>
          ))}
        </div>
      </div>
    </div>
  );
};

export default Dashboard;