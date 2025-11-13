import React from 'react';
import { Card } from '../../ui/Card';
import { Button } from '../../ui/Button';
import { Spinner } from '../../ui/Spinner';
import type { BranchMaintenanceWindow, BranchManagement } from '../../../types/branch-operations';

interface BranchMaintenancePanelProps {
  branches: BranchManagement[];
  maintenanceWindows: BranchMaintenanceWindow[];
  loading: boolean;
  onRefresh: () => void;
}

export const BranchMaintenancePanel: React.FC<BranchMaintenancePanelProps> = ({
  maintenanceWindows,
  loading,
  onRefresh,
}) => {
  if (loading) {
    return (
      <Card className="p-6 flex justify-center">
        <Spinner size="lg" />
      </Card>
    );
  }

  return (
    <Card className="p-6 border border-mono-gray-200 shadow-sm space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-semibold text-mono-gray-900">Maintenance Windows</h3>
        <Button variant="outline" size="sm" onClick={onRefresh}>
          Refresh
        </Button>
      </div>
      {maintenanceWindows.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-mono-gray-200 p-6 text-center text-sm text-mono-gray-500">
          No maintenance windows scheduled.
        </div>
      ) : (
        <ul className="space-y-3 text-sm text-mono-gray-700">
          {maintenanceWindows.map((window) => (
            <li key={window.id} className="rounded-2xl border border-mono-gray-200 px-4 py-3">
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium text-mono-gray-900">{window.reason}</p>
                  <p className="text-xs text-mono-gray-500 mt-1">
                    {new Date(window.start_time).toLocaleString()} → {new Date(window.end_time).toLocaleString()}
                  </p>
                </div>
                <span className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">{window.status}</span>
              </div>
              {window.affected_services?.length > 0 && (
                <p className="mt-2 text-xs text-mono-gray-500">
                  Affected: {window.affected_services.join(', ')}
                </p>
              )}
            </li>
          ))}
        </ul>
      )}
    </Card>
  );
};

export default BranchMaintenancePanel;
