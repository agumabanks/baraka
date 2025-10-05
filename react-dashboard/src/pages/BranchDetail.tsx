import React from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Badge from '../components/ui/Badge';
import LoadingSpinner from '../components/ui/LoadingSpinner';
import { useBranchDetail } from '../hooks/useBranches';

const currencyFormatter = new Intl.NumberFormat('en-US', {
  style: 'currency',
  currency: 'UGX',
  maximumFractionDigits: 0,
});

const percentageFormatter = new Intl.NumberFormat('en-US', {
  maximumFractionDigits: 0,
});

const BranchDetail: React.FC = () => {
  const navigate = useNavigate();
  const { branchId } = useParams();

  const {
    data,
    isLoading,
    isError,
    error,
    refetch,
  } = useBranchDetail(branchId ?? null);

  if (isLoading && !data) {
    return <LoadingSpinner message="Loading branch profile" />;
  }

  if (isError || !data) {
    const message = error instanceof Error ? error.message : 'Unable to load branch profile';
    return (
      <div className="flex min-h-[400px] flex-col items-center justify-center">
        <Card className="max-w-md text-center">
          <div className="space-y-4">
            <div className="inline-flex h-16 w-16 items-center justify-center rounded-full bg-mono-black text-mono-white">
              <i className="fas fa-exclamation-circle text-2xl" aria-hidden="true" />
            </div>
            <div className="space-y-2">
              <h2 className="text-2xl font-semibold text-mono-black">Branch unavailable</h2>
              <p className="text-sm text-mono-gray-600">{message}</p>
            </div>
            <div className="flex justify-center gap-3">
              <Button variant="secondary" size="sm" onClick={() => navigate(-1)}>
                Go Back
              </Button>
              <Button variant="primary" size="sm" onClick={() => refetch()}>
                <i className="fas fa-redo mr-2" aria-hidden="true" />
                Retry
              </Button>
            </div>
          </div>
        </Card>
      </div>
    );
  }

  const { branch, capacity, hierarchy } = data;

  const manager = branch.team.manager;
  const workers = branch.team.active_workers;
  const recentShipments = branch.recent_shipments;

  const capacityMetrics: any = capacity?.current_capacity ?? capacity?.utilization ?? {};
  const utilizationRate =
    typeof branch.metrics.capacity_utilization === 'number'
      ? branch.metrics.capacity_utilization
      : 0;

  return (
    <div className="space-y-8">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div className="space-y-2">
          <button
            type="button"
            onClick={() => navigate(-1)}
            className="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500 transition-colors hover:text-mono-black"
          >
            <i className="fas fa-arrow-left" aria-hidden="true" />
            Back to Branches
          </button>
          <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
            {branch.name}
          </h1>
          <p className="text-sm text-mono-gray-600">{branch.hierarchy_path}</p>
        </div>
        <div className="flex flex-wrap items-center gap-3">
          {branch.is_hub && (
            <Badge variant="solid" size="sm" className="bg-mono-black text-mono-white">
              HUB
            </Badge>
          )}
          <Badge variant="outline" size="sm">{branch.type}</Badge>
          <Badge variant="outline" size="sm">{branch.status_label}</Badge>
        </div>
      </div>

      <section className="grid gap-6 xl:grid-cols-3">
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Capacity Utilisation
            </p>
            <p className="text-3xl font-semibold text-mono-black">
              {percentageFormatter.format(Math.round(utilizationRate))}%
            </p>
            <p className="text-sm text-mono-gray-600">Queue load and workforce coverage</p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Active Workforce
            </p>
            <p className="text-3xl font-semibold text-mono-black">
              {workers.length}{' '}
              <span className="text-sm font-normal text-mono-gray-600">team members</span>
            </p>
            <p className="text-sm text-mono-gray-600">
              {branch.metrics.active_clients} active clients served by this site
            </p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Throughput (24h)
            </p>
            <p className="text-3xl font-semibold text-mono-black">
              {branch.metrics.throughput_24h}
            </p>
            <p className="text-sm text-mono-gray-600">Shipments originated over the past 24 hours</p>
          </div>
        </Card>
      </section>

      <section className="grid gap-6 lg:grid-cols-[2fr,1fr]">
        <Card className="border border-mono-gray-200">
          <div className="space-y-4">
            <header className="flex flex-col gap-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Branch Manager</p>
              <h2 className="text-xl font-semibold text-mono-black">{manager?.name ?? 'Not assigned'}</h2>
              <p className="text-sm text-mono-gray-600">{manager?.business_name ?? 'Business profile pending'}</p>
            </header>

            {manager ? (
              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2 text-sm text-mono-gray-600">
                  <div>
                    <span className="block text-xs uppercase tracking-[0.25em] text-mono-gray-500">Email</span>
                    <span>{manager.email ?? '—'}</span>
                  </div>
                  <div>
                    <span className="block text-xs uppercase tracking-[0.25em] text-mono-gray-500">Phone</span>
                    <span>{manager.phone ?? '—'}</span>
                  </div>
                  <div>
                    <span className="block text-xs uppercase tracking-[0.25em] text-mono-gray-500">Pending Requests</span>
                    <span>{manager.pending_requests}</span>
                  </div>
                </div>
                <div className="space-y-2 text-sm text-mono-gray-600">
                  <div>
                    <span className="block text-xs uppercase tracking-[0.25em] text-mono-gray-500">Available Balance</span>
                    <span>{currencyFormatter.format(Number(manager.settlement_summary?.available_balance ?? 0))}</span>
                  </div>
                  <div>
                    <span className="block text-xs uppercase tracking-[0.25em] text-mono-gray-500">Revenue (30d)</span>
                    <span>{currencyFormatter.format(Number(manager.performance_metrics.revenue_last_30_days ?? 0))}</span>
                  </div>
                  <div>
                    <span className="block text-xs uppercase tracking-[0.25em] text-mono-gray-500">On-time delivery</span>
                    <span>{percentageFormatter.format(Math.round(manager.performance_metrics.on_time_delivery_rate ?? 0))}%</span>
                  </div>
                </div>
              </div>
            ) : (
              <div className="rounded-2xl border border-dashed border-mono-gray-300 p-4 text-sm text-mono-gray-600">
                Assign a manager to enable settlement workflows and COD oversight.
              </div>
            )}
          </div>
        </Card>

        <Card className="border border-mono-gray-200">
          <header className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Capacity Outlook</p>
            <p className="text-sm text-mono-gray-600">{capacityMetrics?.capacity_status ?? 'Capacity plan pending'}</p>
          </header>
          <div className="mt-4 space-y-3 text-sm text-mono-gray-600">
            <div className="flex items-center justify-between">
              <span>Available capacity</span>
              <span>{capacityMetrics?.available_capacity ?? '—'}</span>
            </div>
            <div className="flex items-center justify-between">
              <span>Current workload</span>
              <span>{capacityMetrics?.current_workload ?? branch.insights.open_queues}</span>
            </div>
            <div className="flex items-center justify-between">
              <span>Peak frequency</span>
              <span>{percentageFormatter.format(capacityMetrics?.peak_capacity_hours?.peak_frequency ?? 0)}%</span>
            </div>
          </div>
        </Card>
      </section>

      <section className="grid gap-6 lg:grid-cols-[2fr,1fr]">
        <Card className="border border-mono-gray-200">
          <div className="flex items-center justify-between">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Active Workforce</p>
              <h2 className="text-xl font-semibold text-mono-black">Assignment Matrix</h2>
            </div>
            <Button variant="ghost" size="sm" onClick={() => refetch()}>
              <i className="fas fa-sync-alt mr-2" aria-hidden="true" />
              Refresh
            </Button>
          </div>
          <div className="mt-4 divide-y divide-mono-gray-200 border-t border-mono-gray-200">
            {workers.length === 0 ? (
              <div className="py-6 text-sm text-mono-gray-600">No active workers assigned.</div>
            ) : (
              workers.map((worker) => (
                <div key={worker.id} className="flex flex-col gap-2 py-4 md:flex-row md:items-center md:justify-between">
                  <div>
                    <p className="text-sm font-semibold text-mono-black">{worker.name ?? 'Unassigned user'}</p>
                    <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">{worker.role ?? 'Role pending'}</p>
                  </div>
                  <div className="flex flex-wrap items-center gap-4 text-sm text-mono-gray-600">
                    <span title="Active assignments">
                      <i className="fas fa-tasks mr-2" aria-hidden="true" />
                      {worker.active_assignments}
                    </span>
                    <span>
                      <i className="fas fa-phone mr-2" aria-hidden="true" />
                      {worker.phone ?? '—'}
                    </span>
                    <Badge variant="outline" size="sm">{worker.status}</Badge>
                  </div>
                </div>
              ))
            )}
          </div>
        </Card>

        <Card className="border border-mono-gray-200">
          <header className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Hierarchy</p>
            <p className="text-sm text-mono-gray-600">Operates within {hierarchy.ancestors.length} regional layers</p>
          </header>
          <div className="mt-4 space-y-2 text-sm text-mono-gray-600">
            {hierarchy.ancestors.length === 0 ? (
              <p>This branch is at the top of its hierarchy.</p>
            ) : (
              hierarchy.ancestors.map((ancestor) => (
                <div key={ancestor.id} className="flex items-center justify-between rounded-xl border border-mono-gray-200 px-3 py-2">
                  <span>{ancestor.name}</span>
                  <Badge variant="ghost" size="sm">{ancestor.type}</Badge>
                </div>
              ))
            )}
          </div>
        </Card>
      </section>

      <section>
        <Card className="border border-mono-gray-200">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Recent Shipments</p>
              <h2 className="text-xl font-semibold text-mono-black">Outbound Activity</h2>
            </div>
            <Button variant="ghost" size="sm" onClick={() => navigate('/dashboard/unified-shipments')}>
              Open Workflow
            </Button>
          </div>

          <div className="mt-6 overflow-x-auto">
            <table className="min-w-full divide-y divide-mono-gray-200 text-left">
              <thead className="bg-mono-gray-50 text-xs uppercase tracking-[0.3em] text-mono-gray-500">
                <tr>
                  <th scope="col" className="px-4 py-3">Tracking</th>
                  <th scope="col" className="px-4 py-3">Status</th>
                  <th scope="col" className="px-4 py-3">Destination</th>
                  <th scope="col" className="px-4 py-3">Assigned To</th>
                  <th scope="col" className="px-4 py-3">Created</th>
                  <th scope="col" className="px-4 py-3">Expected</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-mono-gray-100 text-sm text-mono-gray-700">
                {recentShipments.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="px-4 py-6 text-center text-sm text-mono-gray-500">
                      No recent shipments to display.
                    </td>
                  </tr>
                ) : (
                  recentShipments.map((shipment) => (
                    <tr key={shipment.id}>
                      <td className="px-4 py-3 font-medium text-mono-black">{shipment.tracking_number ?? '—'}</td>
                      <td className="px-4 py-3">{shipment.status ?? '—'}</td>
                      <td className="px-4 py-3">{shipment.destination_branch?.name ?? '—'}</td>
                      <td className="px-4 py-3">{shipment.assigned_worker ?? 'Unassigned'}</td>
                      <td className="px-4 py-3">{shipment.created_at ?? '—'}</td>
                      <td className="px-4 py-3">{shipment.expected_delivery_date ?? '—'}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </Card>
      </section>
    </div>
  );
};

export default BranchDetail;
