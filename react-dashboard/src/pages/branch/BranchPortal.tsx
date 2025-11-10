import React from 'react';
import { useQuery } from '@tanstack/react-query';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { branchPortalApi } from '../../services/api';
import type { BranchPortalOverview, BranchPortalShipment } from '../../types/branchPortal';

const BranchPortal: React.FC = () => {
  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['branch-portal-overview'],
    queryFn: () => branchPortalApi.getOverview(),
  });

  if (isLoading) {
    return <LoadingSpinner message="Loading branch workspace" />;
  }

  if (isError || !data?.data) {
    const message = error instanceof Error ? error.message : 'Unable to load branch workspace';
    return (
      <div className="flex min-h-[60vh] flex-col items-center justify-center text-center">
        <Card className="max-w-xl p-8 space-y-4">
          <div className="inline-flex h-16 w-16 items-center justify-center rounded-full bg-mono-black text-white mx-auto">
            <i className="fas fa-exclamation-triangle text-2xl" aria-hidden="true" />
          </div>
          <div>
            <h2 className="text-2xl font-semibold text-mono-black">Unable to load branch data</h2>
            <p className="text-sm text-mono-gray-600">{message}</p>
          </div>
          <Button variant="primary" onClick={() => refetch()} disabled={isFetching}>
            {isFetching ? 'Refreshing...' : 'Try Again'}
          </Button>
        </Card>
      </div>
    );
  }

  const overview: BranchPortalOverview = data.data;

  return (
    <div className="space-y-8">
      <header className="space-y-2">
        <p className="text-xs uppercase tracking-[0.35em] text-mono-gray-500">Branch Operations</p>
        <h1 className="text-3xl font-semibold text-mono-black">
          {overview.branch.name}
        </h1>
        <p className="text-sm text-mono-gray-600">
          {overview.role.type === 'manager'
            ? 'Branch Manager workspace'
            : 'Branch Operations workspace'}
        </p>
      </header>

      <section className="grid gap-6 lg:grid-cols-3">
        <Card className="p-6 border border-mono-gray-200 shadow-sm">
          <div className="text-xs uppercase tracking-[0.35em] text-mono-gray-500">Live Shipments</div>
          <div className="text-4xl font-semibold text-mono-black mt-3">
            {overview.metrics.active_shipments}
          </div>
          <p className="text-sm text-mono-gray-600 mt-2">Currently in motion</p>
        </Card>
        <Card className="p-6 border border-mono-gray-200 shadow-sm">
          <div className="text-xs uppercase tracking-[0.35em] text-mono-gray-500">Delivered Today</div>
          <div className="text-4xl font-semibold text-mono-black mt-3">
            {overview.metrics.delivered_today}
          </div>
          <p className="text-sm text-mono-gray-600 mt-2">Successful handovers</p>
        </Card>
        <Card className="p-6 border border-mono-gray-200 shadow-sm">
          <div className="text-xs uppercase tracking-[0.35em] text-mono-gray-500">Pending Pickups</div>
          <div className="text-4xl font-semibold text-mono-black mt-3">
            {overview.metrics.pending_pickups}
          </div>
          <p className="text-sm text-mono-gray-600 mt-2">Bookings requiring action</p>
        </Card>
      </section>

      <section className="grid gap-6 lg:grid-cols-[2fr,1fr]">
        <Card className="p-0 border border-mono-gray-200 shadow-sm">
          <div className="flex items-center justify-between border-b border-mono-gray-200 px-6 py-4">
            <div>
              <p className="text-xs uppercase tracking-[0.35em] text-mono-gray-500">Recent Activity</p>
              <h2 className="text-xl font-semibold text-mono-black">Latest Shipments</h2>
            </div>
            <Button
              variant="ghost"
              size="sm"
              asChild
              className="uppercase tracking-[0.25em]"
            >
              <a href={overview.links.shipments} target="_blank" rel="noopener noreferrer">
                View All
              </a>
            </Button>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full divide-y divide-mono-gray-200">
              <thead>
                <tr className="text-xs uppercase tracking-[0.25em] text-mono-gray-500 text-left">
                  <th className="px-6 py-3">Tracking</th>
                  <th className="px-6 py-3">Client</th>
                  <th className="px-6 py-3">Status</th>
                  <th className="px-6 py-3 text-right">Amount</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-mono-gray-100">
                {overview.shipments.length === 0 ? (
                  <tr>
                    <td colSpan={4} className="px-6 py-8 text-center text-mono-gray-500">
                      No shipments yet for this branch.
                    </td>
                  </tr>
                ) : (
                  overview.shipments.map((shipment: BranchPortalShipment) => (
                    <tr key={shipment.id} className="text-sm text-mono-black">
                      <td className="px-6 py-4 font-semibold">{shipment.tracking_number}</td>
                      <td className="px-6 py-4">
                        {shipment.client?.business_name ?? '—'}
                      </td>
                      <td className="px-6 py-4">
                        <span className="inline-flex items-center rounded-full border px-2.5 py-1 text-xs uppercase tracking-[0.25em]">
                          {shipment.status.replace(/_/g, ' ')}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-right">
                        {shipment.amount
                          ? `${shipment.currency ?? ''} ${shipment.amount.toFixed(2)}`
                          : '—'}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </Card>

        <Card className="border border-mono-gray-200 shadow-sm space-y-4 p-6">
          <h3 className="text-lg font-semibold text-mono-black">Quick Actions</h3>
          <p className="text-sm text-mono-gray-600">
            Access the tools branch teams use every day.
          </p>
          <div className="space-y-3">
            <Button asChild variant="primary" className="w-full justify-between">
              <a href={overview.links.booking_wizard} target="_blank" rel="noopener noreferrer">
                Launch Booking POS
                <i className="fas fa-external-link-alt ml-2 text-xs" aria-hidden="true" />
              </a>
            </Button>
            <Button asChild variant="secondary" className="w-full justify-between">
              <a href={overview.links.shipments} target="_blank" rel="noopener noreferrer">
                Manage Shipments
                <i className="fas fa-arrow-right ml-2 text-xs" aria-hidden="true" />
              </a>
            </Button>
            <Button asChild variant="ghost" className="w-full justify-between">
              <a href={overview.links.branch_profile} target="_blank" rel="noopener noreferrer">
                View Branch Profile
                <i className="fas fa-arrow-right ml-2 text-xs" aria-hidden="true" />
              </a>
            </Button>
          </div>
        </Card>
      </section>

      <section className="grid gap-6 lg:grid-cols-2">
        <Card className="border border-mono-gray-200 shadow-sm p-6">
          <p className="text-xs uppercase tracking-[0.35em] text-mono-gray-500">Shipment Mix</p>
          <h3 className="text-xl font-semibold text-mono-black mt-2">Branch workload split</h3>
          <div className="mt-4 space-y-4">
            {(overview.mode_distribution ?? []).length === 0 && (
              <p className="text-sm text-mono-gray-500">No mode distribution data yet.</p>
            )}
            {(overview.mode_distribution ?? []).map((entry) => (
              <div key={entry.mode}>
                <div className="flex items-center justify-between text-sm text-mono-gray-600">
                  <span className="font-semibold text-mono-black">{entry.label}</span>
                  <span>{entry.percentage?.toFixed(1)}%</span>
                </div>
                <div className="mt-1 h-2 rounded-full bg-mono-gray-100">
                  <div
                    className="h-full rounded-full bg-mono-black"
                    style={{ width: `${entry.percentage ?? 0}%` }}
                  />
                </div>
                <div className="mt-1 flex items-center justify-between text-xs text-mono-gray-500">
                  <span>{entry.count} total</span>
                  <span>{entry.active} active</span>
                </div>
              </div>
            ))}
          </div>
        </Card>

        <Card className="border border-mono-gray-200 shadow-sm p-6">
          <p className="text-xs uppercase tracking-[0.35em] text-mono-gray-500">Workspace Links</p>
          <h3 className="text-xl font-semibold text-mono-black mt-2">Branch playbook</h3>
          <ul className="mt-4 space-y-3 text-sm text-mono-black">
            <li className="flex items-center justify-between rounded-2xl border border-mono-gray-200 px-4 py-3">
              <span>Launch branch dashboard</span>
              <a href="/dashboard" className="text-mono-gray-500 hover:text-mono-black">Open</a>
            </li>
            <li className="flex items-center justify-between rounded-2xl border border-mono-gray-200 px-4 py-3">
              <span>Visit client portal</span>
              <a href="/portal" className="text-mono-gray-500 hover:text-mono-black">Open</a>
            </li>
          </ul>
        </Card>
      </section>
    </div>
  );
};

export default BranchPortal;
