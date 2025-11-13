import React from 'react';
import { Card } from '../../ui/Card';
import { Spinner } from '../../ui/Spinner';

interface BranchPerformancePanelProps {
  data?: any;
  loading: boolean;
  detailed?: boolean;
}

export const BranchPerformancePanel: React.FC<BranchPerformancePanelProps> = ({ data, loading, detailed }) => {
  if (loading) {
    return (
      <Card className="p-6 flex justify-center">
        <Spinner size="lg" />
      </Card>
    );
  }

  if (!data) {
    return (
      <Card className="p-6 text-center">
        <p className="text-mono-gray-600">No performance analytics available.</p>
      </Card>
    );
  }

  const overview = data.overview || {};

  return (
    <Card className="p-6 border border-mono-gray-200 shadow-sm">
      <h3 className="text-lg font-semibold text-mono-gray-900">Branch Performance Overview</h3>
      <div className="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-mono-gray-700">
        <div className="rounded-2xl border border-mono-gray-200 p-4">
          <p className="text-xs uppercase tracking-[0.35em] text-mono-gray-500">Total Branches</p>
          <p className="mt-2 text-2xl font-semibold text-mono-black">{overview.total_branches ?? '—'}</p>
        </div>
        <div className="rounded-2xl border border-mono-gray-200 p-4">
          <p className="text-xs uppercase tracking-[0.35em] text-mono-gray-500">Active Branches</p>
          <p className="mt-2 text-2xl font-semibold text-mono-black">{overview.active_branches ?? '—'}</p>
        </div>
        <div className="rounded-2xl border border-mono-gray-200 p-4">
          <p className="text-xs uppercase tracking-[0.35em] text-mono-gray-500">Average Score</p>
          <p className="mt-2 text-2xl font-semibold text-mono-black">
            {overview.average_performance_score ?? '—'}
          </p>
        </div>
        <div className="rounded-2xl border border-mono-gray-200 p-4">
          <p className="text-xs uppercase tracking-[0.35em] text-mono-gray-500">Needs Attention</p>
          <p className="mt-2 text-2xl font-semibold text-mono-black">
            {overview.inactive_branches ?? 0}
          </p>
        </div>
      </div>

      {detailed && data.performance_ranking && (
        <div className="mt-6">
          <h4 className="text-sm font-semibold text-mono-gray-900">Top Performing Branches</h4>
          <ul className="mt-3 space-y-2 text-sm text-mono-gray-700">
            {data.performance_ranking.slice(0, 5).map((branch: any) => (
              <li key={branch.branch_id} className="flex items-center justify-between rounded-2xl border border-mono-gray-200 px-3 py-2">
                <span>{branch.branch_name}</span>
                <span className="font-semibold text-mono-black">{branch.performance_score}</span>
              </li>
            ))}
          </ul>
        </div>
      )}
    </Card>
  );
};

export default BranchPerformancePanel;
