import React from 'react';
import { Card } from '../../ui/Card';
import { Button } from '../../ui/Button';
import { Spinner } from '../../ui/Spinner';
import type { BranchManagement } from '../../../types/branch-operations';

interface BranchListProps {
  branches: BranchManagement[];
  loading: boolean;
  onViewDetails: (branch: BranchManagement) => void;
  onRefresh: () => void;
}

export const BranchList: React.FC<BranchListProps> = ({ branches, loading, onViewDetails, onRefresh }) => {
  if (loading) {
    return (
      <Card className="p-6 flex justify-center">
        <Spinner size="lg" />
      </Card>
    );
  }

  if (!branches.length) {
    return (
      <Card className="p-6 text-center">
        <p className="text-mono-gray-600">No branches available.</p>
        <Button variant="outline" size="sm" className="mt-4" onClick={onRefresh}>
          Refresh
        </Button>
      </Card>
    );
  }

  return (
    <Card className="p-0 overflow-hidden border border-mono-gray-200 shadow-sm">
      <div className="flex items-center justify-between border-b border-mono-gray-200 px-6 py-4">
        <h2 className="text-lg font-semibold text-mono-gray-900">Branch Directory</h2>
        <Button variant="outline" size="sm" onClick={onRefresh}>
          Refresh
        </Button>
      </div>
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-mono-gray-200 text-sm">
          <thead className="bg-mono-gray-50 text-left text-xs uppercase tracking-[0.3em] text-mono-gray-500">
            <tr>
              <th className="px-6 py-3">Name</th>
              <th className="px-6 py-3">Code</th>
              <th className="px-6 py-3">Status</th>
              <th className="px-6 py-3">City</th>
              <th className="px-6 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-mono-gray-100">
            {branches.map((branch) => (
              <tr key={branch.id} className="text-mono-gray-900">
                <td className="px-6 py-4">
                  <div className="font-medium">{branch.name}</div>
                  {branch.address && (
                    <div className="text-xs text-mono-gray-500">{branch.address}</div>
                  )}
                  {branch.manager && (
                    <div className="mt-2 space-y-1 text-xs text-mono-gray-500">
                      <div className="font-semibold text-mono-gray-700">Manager</div>
                      <div className="flex flex-wrap items-center gap-2">
                        <span>{branch.manager.name ?? 'Unassigned'}</span>
                        {branch.manager.preferred_language && (
                          <span className="rounded-full bg-mono-gray-100 px-2 py-0.5 text-[10px] uppercase tracking-[0.2em]">
                            {branch.manager.preferred_language?.toUpperCase()}
                          </span>
                        )}
                      </div>
                    </div>
                  )}
                  {branch.team && (
                    <div className="mt-2 text-xs text-mono-gray-500">
                      Workforce: {branch.team.active_workers}/{branch.team.total_workers} active
                    </div>
                  )}
                </td>
                <td className="px-6 py-4 font-mono text-xs">{branch.code}</td>
                <td className="px-6 py-4 capitalize">
                  {branch.status}
                </td>
                <td className="px-6 py-4">{branch.city}</td>
                <td className="px-6 py-4 text-right">
                  <Button variant="outline" size="sm" onClick={() => onViewDetails(branch)}>
                    View
                  </Button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </Card>
  );
};

export default BranchList;
