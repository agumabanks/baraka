import React from 'react';
import Card from '../ui/Card';
import Badge from '../ui/Badge';
import type { BranchOpsSnapshot } from '../../types/dashboard';

interface BranchOperationsPanelProps {
  snapshot?: BranchOpsSnapshot | null;
}

const BranchOperationsPanel: React.FC<BranchOperationsPanelProps> = ({ snapshot }) => {
  if (!snapshot) {
    return (
      <Card className="p-6 border border-mono-gray-200 shadow-sm">
        <div className="animate-pulse space-y-4">
          <div className="h-4 w-32 rounded-full bg-mono-gray-200" />
          <div className="h-8 w-1/2 rounded-full bg-mono-gray-200" />
          <div className="grid gap-3 md:grid-cols-3">
            {Array.from({ length: 3 }).map((_, idx) => (
              <div key={idx} className="h-16 rounded-2xl bg-mono-gray-100" />
            ))}
          </div>
        </div>
      </Card>
    );
  }

  const coverageRate = Number(snapshot.coverage_rate ?? 0);
  const alerts = snapshot.alerts?.filter((alert) => alert.count > 0) ?? [];

  return (
    <Card className="p-6 border border-mono-gray-200 shadow-sm">
      <div className="flex flex-col gap-6 lg:flex-row lg:items-start">
        <div className="flex w-full flex-col gap-4 lg:w-1/3">
          <div className="space-y-1">
            <p className="text-xs font-semibold uppercase tracking-[0.35em] text-mono-gray-500">
              Branch Coverage
            </p>
            <h3 className="text-3xl font-semibold text-mono-black">
              {coverageRate.toFixed(1)}%
            </h3>
            <p className="text-sm text-mono-gray-600">
              {snapshot.active_branches} of {snapshot.total_branches} branches active
            </p>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div className="rounded-2xl border border-mono-gray-200 p-3">
              <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Hubs</p>
              <p className="text-2xl font-semibold text-mono-black">{snapshot.hub_count}</p>
            </div>
            <div className="rounded-2xl border border-mono-gray-200 p-3">
              <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Needs Action</p>
              <p className="text-2xl font-semibold text-mono-black">{snapshot.needs_attention}</p>
            </div>
          </div>
          <div className="space-y-2">
            <p className="text-xs uppercase tracking-[0.35em] text-mono-gray-500">Alerts</p>
            {alerts.length === 0 ? (
              <p className="text-sm text-mono-gray-500">Healthy branch network</p>
            ) : (
              <ul className="space-y-2">
                {alerts.map((alert) => (
                  <li key={alert.id} className="flex items-center justify-between rounded-2xl border border-mono-gray-200 px-3 py-2 text-sm">
                    <span>{alert.label}</span>
                    <Badge variant="warning">{alert.count}</Badge>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>

        <div className="h-px w-full bg-mono-gray-200 lg:h-auto lg:w-px" />

        <div className="w-full lg:flex-1">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-xs uppercase tracking-[0.35em] text-mono-gray-500">
                Top Branches
              </p>
              <h3 className="text-lg font-semibold text-mono-black">Live workload</h3>
            </div>
          </div>
          <ul className="mt-4 space-y-3">
            {snapshot.top_branches.length === 0 && (
              <li className="rounded-2xl border border-dashed border-mono-gray-200 px-4 py-6 text-center text-sm text-mono-gray-500">
                Create branches to see live performance.
              </li>
            )}
            {snapshot.top_branches.map((branch) => (
              <li
                key={branch.id}
                className="rounded-2xl border border-mono-gray-200 px-4 py-3 hover:border-mono-gray-300"
              >
                <div className="flex items-center justify-between gap-4">
                  <div>
                    <p className="text-sm font-semibold text-mono-black">{branch.name}</p>
                    <p className="text-xs uppercase tracking-[0.4em] text-mono-gray-500">
                      {branch.status_label}
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="text-sm text-mono-gray-600">Live</p>
                    <p className="text-xl font-semibold text-mono-black">{branch.live_shipments}</p>
                  </div>
                  <div className="text-right">
                    <p className="text-sm text-mono-gray-600">Workers</p>
                    <p className="text-xl font-semibold text-mono-black">{branch.active_workers}</p>
                  </div>
                </div>
              </li>
            ))}
          </ul>
        </div>
      </div>
    </Card>
  );
};

export default BranchOperationsPanel;
