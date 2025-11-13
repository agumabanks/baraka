import React from 'react';
import { Card } from '../../ui/Card';
import { Button } from '../../ui/Button';

interface BranchAlertsPanelProps {
  onRefresh: () => void;
}

export const BranchAlertsPanel: React.FC<BranchAlertsPanelProps> = ({ onRefresh }) => (
  <Card className="p-6 border border-mono-gray-200 shadow-sm">
    <div className="flex items-center justify-between">
      <div>
        <h3 className="text-lg font-semibold text-mono-gray-900">Branch Alerts</h3>
        <p className="text-sm text-mono-gray-600 mt-1">
          Real-time alerts for branch capacity, performance, and maintenance.
        </p>
      </div>
      <Button variant="outline" size="sm" onClick={onRefresh}>
        Refresh
      </Button>
    </div>
    <div className="mt-6 rounded-2xl border border-dashed border-mono-gray-200 p-6 text-center text-sm text-mono-gray-500">
      Alert feed coming soon.
    </div>
  </Card>
);

export default BranchAlertsPanel;
