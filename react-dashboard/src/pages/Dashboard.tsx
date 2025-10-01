import React from 'react';
import { useSearchParams } from 'react-router-dom';
import KPIGrid from '../components/dashboard/KPIGrid';
import KPICard from '../components/dashboard/KPICard';
import ChartSection from '../components/dashboard/ChartSection';
import WorkflowQueue from '../components/dashboard/WorkflowQueue';
import StatementCard from '../components/dashboard/StatementCard';
import DateRangeFilter from '../components/dashboard/DateRangeFilter';
import QuickActions from '../components/dashboard/QuickActions';
import Card from '../components/ui/Card';
import Badge from '../components/ui/Badge';
import Button from '../components/ui/Button';
import LoadingSpinner from '../components/ui/LoadingSpinner';
import SkeletonCard from '../components/dashboard/SkeletonCard';
import { useDashboardData, transformDashboardData } from '../hooks/useDashboardData';
import { mockDashboardData } from '../data/mockDashboardData';
import { t } from '../lib/i18n';
import Can from '../components/rbac/Can';
import type { KPICard as KPICardType } from '../types/dashboard';

/**
 * Dashboard Page Component
 * Matches exact Blade dashboard layout with monochrome design
 */
const Dashboard: React.FC = () => {
  const [searchParams] = useSearchParams();
  const filterDate = searchParams.get('filter_date') || '';

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
    // Drill-down functionality handled in KPICard component
  };

  // Handle workflow item click
  const handleWorkflowItemClick = (itemId: string) => {
    console.log('Workflow item clicked:', itemId);
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
              <h2 className="text-2xl font-bold text-mono-black mb-2">
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
      <div className="bg-gradient-to-b from-mono-gray-25 via-mono-gray-50 to-mono-gray-100 border border-mono-gray-200 shadow-xl rounded-3xl p-6 sm:p-8 space-y-10">
        {/* Date Filter */}
        <div className="flex justify-end">
          <DateRangeFilter
            value={filterDate}
            loading={isLoading}
          />
        </div>

        {/* Business Health KPIs - Row 1 */}
        <section className="space-y-4">
          <header>
            <h2 className="text-lg font-semibold text-mono-black">
              Business Health
            </h2>
            <p className="text-sm text-mono-gray-600">Operational vitals for the current monitoring window</p>
          </header>
          {isLoading ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              {[1, 2, 3, 4].map((i) => (
                <SkeletonCard key={i} />
              ))}
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <Can permission="sla_status">
                {dashboardData.healthKPIs.slaStatus && (
                  <KPICard
                    {...dashboardData.healthKPIs.slaStatus}
                    onClick={() => handleKPIClick(dashboardData.healthKPIs.slaStatus!)}
                  />
                )}
              </Can>
              <Can permission="exceptions">
                {dashboardData.healthKPIs.exceptions && (
                  <KPICard
                    {...dashboardData.healthKPIs.exceptions}
                    onClick={() => handleKPIClick(dashboardData.healthKPIs.exceptions!)}
                  />
                )}
              </Can>
              <Can permission="on_time_delivery">
                {dashboardData.healthKPIs.onTimeDelivery && (
                  <KPICard
                    {...dashboardData.healthKPIs.onTimeDelivery}
                    onClick={() => handleKPIClick(dashboardData.healthKPIs.onTimeDelivery!)}
                  />
                )}
              </Can>
              <Can permission="open_tickets">
                {dashboardData.healthKPIs.openTickets && (
                  <KPICard
                    {...dashboardData.healthKPIs.openTickets}
                    onClick={() => handleKPIClick(dashboardData.healthKPIs.openTickets!)}
                  />
                )}
              </Can>
            </div>
          )}
        </section>

        {/* Work in Progress - Row 2 */}
        <section className="grid grid-cols-1 xl:grid-cols-2 gap-6">
          <WorkflowQueue
            items={dashboardData.workflowQueue}
            loading={isLoading}
            onItemClick={(item) => handleWorkflowItemClick(item.id)}
            maxItems={5}
          />

          {isLoading ? (
            <SkeletonCard />
          ) : dashboardData.charts.cashCollection ? (
            <ChartSection
              config={dashboardData.charts.cashCollection}
              loading={isLoading}
              error={isError ? (error instanceof Error ? error.message : 'Failed to load chart data') : undefined}
            />
          ) : null}
        </section>

        {/* Financial Statements - Row 3 */}
        <section className="space-y-4">
          <header>
            <h2 className="text-lg font-semibold text-mono-black">
              {t('dashboard.statements')}
            </h2>
            <p className="text-sm text-mono-gray-600">Balance snapshots across your primary stakeholders</p>
          </header>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Can permission="all_statements">
              {dashboardData.statements.deliveryMan ? (
                <StatementCard
                  title={`${t('dashboard.delivery_man')} ${t('dashboard.statements')}`}
                  income={dashboardData.statements.deliveryMan.income}
                  expense={dashboardData.statements.deliveryMan.expense}
                  currency={dashboardData.statements.deliveryMan.currency}
                  loading={isLoading}
                />
              ) : null}
            </Can>
            <Can permission="all_statements">
              {dashboardData.statements.merchant ? (
                <StatementCard
                  title={`${t('dashboard.merchant')} ${t('dashboard.statements')}`}
                  income={dashboardData.statements.merchant.income}
                  expense={dashboardData.statements.merchant.expense}
                  currency={dashboardData.statements.merchant.currency}
                  loading={isLoading}
                />
              ) : null}
            </Can>
            <Can permission="all_statements">
              {dashboardData.statements.hub ? (
                <StatementCard
                  title={`${t('hub.title')} ${t('dashboard.statements')}`}
                  income={dashboardData.statements.hub.income}
                  expense={dashboardData.statements.hub.expense}
                  currency={dashboardData.statements.hub.currency}
                  loading={isLoading}
                />
              ) : null}
            </Can>
          </div>
        </section>

        {/* Analytics & Trends - Row 4 */}
        <section className="space-y-4">
          <header>
            <h2 className="text-lg font-semibold text-mono-black">
              {t('dashboard.trends')}
            </h2>
            <p className="text-sm text-mono-gray-600">Long-range performance indicators and revenue momentum</p>
          </header>
          <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <Can permission="income_expense_charts">
              {isLoading ? (
                <SkeletonCard />
              ) : dashboardData.charts.incomeExpense ? (
                <ChartSection
                  config={dashboardData.charts.incomeExpense}
                  loading={isLoading}
                  error={isError ? (error instanceof Error ? error.message : 'Failed to load chart data') : undefined}
                />
              ) : null}
            </Can>
            <Can permission="courier_revenue_charts">
              {isLoading ? (
                <SkeletonCard />
              ) : dashboardData.charts.courierRevenue ? (
                <ChartSection
                  config={dashboardData.charts.courierRevenue}
                  loading={isLoading}
                  error={isError ? (error instanceof Error ? error.message : 'Failed to load chart data') : undefined}
                />
              ) : null}
            </Can>
          </div>
        </section>

        {/* Core KPIs Grid - Row 5 */}
        <section className="space-y-4">
          <header>
            <h2 className="text-lg font-semibold text-mono-black">
              Key Metrics
            </h2>
            <p className="text-sm text-mono-gray-600">Expanded performance grid across operations and finance</p>
          </header>
          {isLoading ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
                <SkeletonCard key={i} />
              ))}
            </div>
          ) : (
            <KPIGrid
              kpis={dashboardData.coreKPIs.filter((kpi): kpi is KPICardType => kpi !== undefined)}
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
        </section>

        {/* Quick Actions - Row 6 */}
        <section className="space-y-4">
          <header>
            <h2 className="text-lg font-semibold text-mono-black">
              Quick Actions
            </h2>
            <p className="text-sm text-mono-gray-600">Frequently used flows tailored to your current context</p>
          </header>
          <QuickActions
            actions={dashboardData.quickActions}
            loading={isLoading}
          />
        </section>
      </div>

      {useMockData && (
        <div className="fixed bottom-4 right-4 z-50">
          <Badge variant="solid" size="sm">
            <i className="fas fa-flask mr-2" aria-hidden="true" />
            Demo Mode
          </Badge>
        </div>
      )}
    </div>
  );
};

export default Dashboard;
