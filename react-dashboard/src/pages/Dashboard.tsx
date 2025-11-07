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
import Button from '../components/ui/Button';
import LoadingSpinner from '../components/ui/LoadingSpinner';
import SkeletonCard from '../components/dashboard/SkeletonCard';
import { useDashboardData, transformDashboardData } from '../hooks/useDashboardData';
import { useOperationalMetrics } from '../hooks/useAnalytics';
import { useWorkflowQueue } from '../hooks/useWorkflowQueue';
import useWorkflowStore, { type WorkflowState } from '../stores/workflowStore';
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
  const {
    data: operationalResponse,
    isLoading: opsLoading,
    isError: opsError,
    error: opsErrorMessage,
    refetch: refetchOperational,
  } = useOperationalMetrics();
  const operationalMetrics = operationalResponse?.data;
  
  // Fetch real-time workflow queue data
  const workflowQueueQuery = useWorkflowQueue();
  const workflowQueueData = workflowQueueQuery.data;
  const isWorkflowLoading = workflowQueueQuery.isLoading;
  const queueFromStore = useWorkflowStore((state: WorkflowState) => state.queue);
  const storeSyncing = useWorkflowStore((state: WorkflowState) => state.isSyncing);
  
  // Transform API data or use mock data as fallback
  const dashboardData = apiResponse?.success
    ? transformDashboardData(apiResponse)
    : null;
  
  // Use real-time workflow data if available, fallback to dashboard data
  const workflowItems = queueFromStore.length
    ? queueFromStore
    : workflowQueueData?.tasks ?? dashboardData?.workflowQueue ?? [];

  // Handle KPI card click
  const handleKPIClick = (kpi: KPICardType) => {
    console.log('KPI clicked:', kpi.id);
    // Drill-down functionality handled in KPICard component
  };

  // Handle workflow item click
  const handleWorkflowItemClick = (itemId: string) => {
    console.log('Workflow item clicked:', itemId);
  };

  const formatCoverage = (value: number) => {
    if (!Number.isFinite(value)) return '0%';
    return `${value.toFixed(1)}%`;
  };

  const formatActivityTimestamp = (value?: string | null) => {
    if (!value) return '—';
    try {
      return new Intl.DateTimeFormat(undefined, {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
      }).format(new Date(value));
    } catch (err) {
      console.error('Failed to format activity timestamp', err);
      return value;
    }
  };

  const formatActionLabel = (action: string) => {
    if (!action) return 'Update';
    return action
      .split('_')
      .map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1))
      .join(' ');
  };

  const extractActivitySummary = (details?: Record<string, unknown>) => {
    if (!details) return null;
    const candidates = ['summary', 'message', 'note', 'description'];
    for (const key of candidates) {
      const value = details[key as keyof typeof details];
      if (typeof value === 'string' && value.trim().length > 0) {
        return value;
      }
    }
    return null;
  };

  // Show loading spinner on initial load
  if (isLoading && !dashboardData) {
    return <LoadingSpinner message="Loading dashboard data..." />;
  }

  // Show error state with retry option
  if (isError && !dashboardData) {
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
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px]">
        <Card className="max-w-md text-center">
          <div className="space-y-4">
            <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-mono-black text-mono-white">
              <i className="fas fa-database text-2xl" aria-hidden="true" />
            </div>
            <div>
              <h2 className="text-2xl font-bold text-mono-black mb-2">
                No Dashboard Data Available
              </h2>
              <p className="text-mono-gray-600 mb-4">
                We could not retrieve dashboard metrics for the selected period.
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

  const chartEntries = Object.entries(dashboardData.charts ?? {}).filter(([, chart]) => chart);
  const primaryChartEntry = chartEntries.find(([key]) => key === 'cashCollection') ?? chartEntries[0];
  const secondaryCharts = chartEntries.filter((entry) => entry !== primaryChartEntry);

  const topRoutes = operationalMetrics?.originDestinationAnalytics.topRoutes ?? [];
  const totalExceptions = operationalMetrics?.exceptionAnalysis.totalExceptions ?? 0;
  const onTimeRate = operationalMetrics?.onTimeDelivery.rate ?? 0;
  const averageTransitTime = operationalMetrics?.transitTimeAnalysis.averageTime ?? 0;
  const totalVolume = operationalMetrics?.originDestinationAnalytics.totalVolume ?? 0;

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
            items={workflowItems}
            loading={isLoading || isWorkflowLoading || storeSyncing}
            onItemClick={(item) => handleWorkflowItemClick(item.id)}
            maxItems={5}
          />

          {isLoading ? (
            <SkeletonCard />
          ) : primaryChartEntry ? (
            <ChartSection
              config={primaryChartEntry[1]!}
              loading={isLoading}
              error={isError ? (error instanceof Error ? error.message : 'Failed to load chart data') : undefined}
            />
          ) : (
            <Card>
              <div className="flex h-64 items-center justify-center text-sm text-mono-gray-500">
                No charts available for this period.
              </div>
            </Card>
          )}
        </section>

        {/* Team Operations Overview & Activity Timeline */}
        <section className="grid grid-cols-1 xl:grid-cols-[2fr_1fr] gap-6">
          <Card className="space-y-5 border border-mono-gray-200 p-6">
            <div className="flex items-start justify-between gap-3">
              <div>
                <h2 className="text-lg font-semibold text-mono-black">Team Operations Overview</h2>
                <p className="text-sm text-mono-gray-600">Headcount distribution across your squads</p>
              </div>
              <span className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">{dashboardData.teamOverview.length} teams</span>
            </div>
            <div className="space-y-4">
              {dashboardData.teamOverview.length > 0 ? (
                dashboardData.teamOverview.map((team) => (
                  <div key={team.id} className="rounded-2xl border border-mono-gray-200 p-4">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <p className="text-sm font-semibold text-mono-black">{team.label}</p>
                        <p className="text-xs text-mono-gray-500">
                          {team.department?.title ?? 'No department'}
                          {team.hub ? ` • ${team.hub.name}` : ''}
                        </p>
                      </div>
                      <div className="text-right">
                        <p className="text-2xl font-semibold text-mono-black">{team.active}</p>
                        <p className="text-xs text-mono-gray-500">Active of {team.total}</p>
                      </div>
                    </div>
                    <div className="mt-3 h-2 rounded-full bg-mono-gray-200">
                      <div
                        className="h-2 rounded-full bg-mono-black"
                        style={{ width: `${Math.min(team.active_ratio, 100)}%` }}
                      />
                    </div>
                    <div className="mt-3 flex items-center justify-between text-xs text-mono-gray-500">
                      <span>{formatCoverage(team.active_ratio)} coverage</span>
                      <span>{team.recent_hires} recent hire{team.recent_hires === 1 ? '' : 's'}</span>
                    </div>
                    {team.sample_users.length > 0 && (
                      <p className="mt-3 text-xs text-mono-gray-600">
                        Key members: {team.sample_users.map((member) => member.name).join(', ')}
                      </p>
                    )}
                  </div>
                ))
              ) : (
                <div className="rounded-xl border border-dashed border-mono-gray-300 p-6 text-sm text-mono-gray-600">
                  No team assignments recorded yet.
                </div>
              )}
            </div>
          </Card>

          <Card className="space-y-5 border border-mono-gray-200 p-6">
            <div>
              <h2 className="text-lg font-semibold text-mono-black">Activity Timeline</h2>
              <p className="text-sm text-mono-gray-600">Latest workflow signals across operations</p>
            </div>
            <div className="space-y-3">
              {dashboardData.activityTimeline.length > 0 ? (
                dashboardData.activityTimeline.map((activity) => {
                  const summary = extractActivitySummary(activity.details);
                  return (
                    <div key={activity.id} className="rounded-2xl border border-mono-gray-200 p-4">
                      <div className="flex items-start justify-between gap-3">
                        <div className="space-y-1">
                          <p className="text-sm font-semibold text-mono-black">{activity.actor?.name ?? 'System'}</p>
                          <p className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">{formatActionLabel(activity.action)}</p>
                          {activity.task?.title && (
                            <p className="text-xs text-mono-gray-600">Task: {activity.task.title}</p>
                          )}
                          {summary && (
                            <p className="text-xs text-mono-gray-600">{summary}</p>
                          )}
                        </div>
                        <span className="text-xs text-mono-gray-400 whitespace-nowrap">
                          {formatActivityTimestamp(activity.createdAt)}
                        </span>
                      </div>
                    </div>
                  );
                })
              ) : (
                <div className="rounded-xl border border-dashed border-mono-gray-300 p-6 text-sm text-mono-gray-600">
                  No recent workflow activity captured.
                </div>
              )}
            </div>
          </Card>
        </section>

        {secondaryCharts.length > 0 && (
          <section className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {secondaryCharts.map(([key, chart]) => (
              chart ? (
                <ChartSection
                  key={key}
                  config={chart}
                  loading={isLoading}
                  error={isError ? (error instanceof Error ? error.message : 'Failed to load chart data') : undefined}
                />
              ) : null
            ))}
          </section>
        )}

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

        <section className="space-y-4">
          <header className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 className="text-lg font-semibold text-mono-black">Operational Analytics</h2>
              <p className="text-sm text-mono-gray-600">Shipment performance insights for the current window</p>
            </div>
            <div className="flex items-center gap-3">
              {opsLoading && <span className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Refreshing…</span>}
              <Button variant="secondary" size="sm" onClick={() => refetchOperational()}>
                <i className="fas fa-sync-alt mr-2" aria-hidden="true" />
                Refresh
              </Button>
            </div>
          </header>

          {opsError && !operationalMetrics ? (
            <Card className="p-6">
              <h3 className="text-lg font-semibold text-mono-black">Operational analytics unavailable</h3>
              <p className="mt-2 text-sm text-mono-gray-600">
                {opsErrorMessage instanceof Error ? opsErrorMessage.message : 'Unable to load operational metrics. Please retry.'}
              </p>
              <Button className="mt-4" variant="primary" size="sm" onClick={() => refetchOperational()}>
                Retry
              </Button>
            </Card>
          ) : operationalMetrics ? (
            <div className="space-y-4">
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card className="border border-mono-gray-200 p-6">
                  <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Total shipments</p>
                  <p className="text-3xl font-semibold text-mono-black mt-2">{totalVolume.toLocaleString()}</p>
                </Card>
                <Card className="border border-mono-gray-200 p-6">
                  <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">On-time rate</p>
                  <p className="text-3xl font-semibold text-mono-black mt-2">{formatCoverage(onTimeRate)}</p>
                </Card>
                <Card className="border border-mono-gray-200 p-6">
                  <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Avg transit time (hrs)</p>
                  <p className="text-3xl font-semibold text-mono-black mt-2">{averageTransitTime.toFixed(1)}</p>
                </Card>
                <Card className="border border-mono-gray-200 p-6">
                  <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Exceptions</p>
                  <p className="text-3xl font-semibold text-mono-black mt-2">{totalExceptions.toLocaleString()}</p>
                </Card>
              </div>

              <Card className="border border-mono-gray-200 p-6">
                <div className="flex items-center justify-between">
                  <h3 className="text-lg font-semibold text-mono-black">Top Routes</h3>
                  <span className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Volume leaders</span>
                </div>
                <div className="mt-4 overflow-x-auto">
                  <table className="min-w-full divide-y divide-mono-gray-200 text-left text-sm">
                    <thead>
                      <tr className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">
                        <th className="px-4 py-3">Route</th>
                        <th className="px-4 py-3">Shipments</th>
                        <th className="px-4 py-3">On-time %</th>
                        <th className="px-4 py-3">Revenue</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-mono-gray-100">
                      {topRoutes.map((route) => (
                        <tr key={route.route} className="hover:bg-mono-gray-50">
                          <td className="px-4 py-3 font-medium text-mono-black">{route.route}</td>
                          <td className="px-4 py-3">{route.volume.toLocaleString()}</td>
                          <td className="px-4 py-3">{formatCoverage(route.efficiency)}</td>
                          <td className="px-4 py-3">{route.revenue ? route.revenue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—'}</td>
                        </tr>
                      ))}
                      {topRoutes.length === 0 && (
                        <tr>
                          <td colSpan={4} className="px-4 py-6 text-center text-mono-gray-500">
                            No route analytics available for the selected window.
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </Card>
            </div>
          ) : (
            <LoadingSpinner message="Loading operational analytics..." />
          )}
        </section>
      </div>
    </div>
  );
};

export default Dashboard;
