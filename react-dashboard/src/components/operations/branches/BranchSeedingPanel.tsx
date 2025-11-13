import React, { useState } from 'react';
import { Card } from '../../ui/Card';
import { Button } from '../../ui/Button';
import { Badge } from '../../ui/Badge';
import { Input } from '../../ui/Input';
import { Spinner } from '../../ui/Spinner';
import { BranchManagement, BranchSeedOperation, BranchSeedParameters } from '../../../types/branch-operations';

interface BranchSeedingPanelProps {
  branches: BranchManagement[];
  seedOperations: BranchSeedOperation[];
  loading: boolean;
  onSeedingOperation: (payload: BranchSeedParameters, mode: 'dry-run' | 'force-execute') => void;
  onClose?: () => void;
  onRefresh: () => void;
  isModal?: boolean;
}

export const BranchSeedingPanel: React.FC<BranchSeedingPanelProps> = ({
  branches,
  seedOperations,
  loading,
  onSeedingOperation,
  onClose,
  onRefresh,
  isModal = false,
}) => {
  const [selectedBranches, setSelectedBranches] = useState<string[]>([]);
  const [seedingMode, setSeedingMode] = useState<'dry-run' | 'force-execute'>('dry-run');
  const [parameters, setParameters] = useState<BranchSeedParameters>({
    branch_ids: [],
    include_inactive: false,
    force_update: false,
    backup_data: true,
    parallel_processing: true,
    batch_size: 100,
    max_retries: 3,
  });
  const [showAdvanced, setShowAdvanced] = useState(false);

  const handleBranchToggle = (branchId: string) => {
    setSelectedBranches(prev => 
      prev.includes(branchId) 
        ? prev.filter(id => id !== branchId)
        : [...prev, branchId]
    );
  };

  const handleSelectAll = () => {
    if (selectedBranches.length === branches.length) {
      setSelectedBranches([]);
    } else {
      setSelectedBranches(branches.map(branch => branch.id));
    }
  };

  const handleSeedingSubmit = () => {
    const payload: BranchSeedParameters = {
      ...parameters,
      branch_ids: selectedBranches,
    };

    if (selectedBranches.length === 0) {
      alert('Please select at least one branch to seed.');
      return;
    }

    onSeedingOperation(payload, seedingMode);
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'pending':
        return <Badge variant="default">Pending</Badge>;
      case 'running':
        return <Badge variant="warning">Running</Badge>;
      case 'completed':
        return <Badge variant="success">Completed</Badge>;
      case 'failed':
        return <Badge variant="destructive">Failed</Badge>;
      case 'cancelled':
        return <Badge variant="default">Cancelled</Badge>;
      default:
        return <Badge variant="default">{status}</Badge>;
    }
  };

  const content = (
    <div className="space-y-6">
      {/* Seeding Parameters */}
      <Card className="p-6">
        <h3 className="text-lg font-semibold text-mono-gray-900 mb-4">Seeding Parameters</h3>
        
        {/* Branch Selection */}
        <div className="mb-6">
          <div className="flex items-center justify-between mb-3">
            <label className="text-sm font-medium text-mono-gray-700">
              Select Branches to Seed ({selectedBranches.length} selected)
            </label>
            <Button
              onClick={handleSelectAll}
              variant="outline"
              size="sm"
            >
              {selectedBranches.length === branches.length ? 'Deselect All' : 'Select All'}
            </Button>
          </div>
          
          <div className="max-h-48 overflow-y-auto border border-gray-200 rounded p-3">
            {branches.map((branch) => (
              <label
                key={branch.id}
                className="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded cursor-pointer"
              >
                <input
                  type="checkbox"
                  checked={selectedBranches.includes(branch.id)}
                  onChange={() => handleBranchToggle(branch.id)}
                  className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <div className="flex-1">
                  <div className="flex items-center gap-2">
                    <span className="font-medium text-mono-gray-900">{branch.name}</span>
                    <Badge 
                      variant={branch.status === 'active' ? 'success' : 
                             branch.status === 'inactive' ? 'default' : 'warning'}
                    >
                      {branch.status}
                    </Badge>
                  </div>
                  <p className="text-sm text-mono-gray-600">{branch.location}</p>
                </div>
              </label>
            ))}
          </div>
        </div>

        {/* Mode Selection */}
        <div className="mb-6">
          <label className="text-sm font-medium text-mono-gray-700 mb-3 block">
            Seeding Mode
          </label>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            <label className="flex items-center space-x-3 p-4 border rounded cursor-pointer hover:bg-gray-50">
              <input
                type="radio"
                checked={seedingMode === 'dry-run'}
                onChange={() => setSeedingMode('dry-run')}
                className="text-blue-600 focus:ring-blue-500"
              />
              <div>
                <div className="font-medium text-mono-gray-900">Dry Run (Simulation)</div>
                <div className="text-sm text-mono-gray-600">
                  Test operation without making changes. Shows what would happen.
                </div>
              </div>
            </label>
            <label className="flex items-center space-x-3 p-4 border rounded cursor-pointer hover:bg-gray-50">
              <input
                type="radio"
                checked={seedingMode === 'force-execute'}
                onChange={() => setSeedingMode('force-execute')}
                className="text-blue-600 focus:ring-blue-500"
              />
              <div>
                <div className="font-medium text-mono-gray-900">Force Execute</div>
                <div className="text-sm text-mono-gray-600">
                  Actually perform the seeding operation. This cannot be undone.
                </div>
              </div>
            </label>
          </div>
        </div>

        {/* Basic Options */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          <div>
            <label className="flex items-center space-x-2">
              <input
                type="checkbox"
                checked={parameters.include_inactive}
                onChange={(e) => setParameters(prev => ({ ...prev, include_inactive: e.target.checked }))}
                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span className="text-sm font-medium text-mono-gray-700">
                Include Inactive Branches
              </span>
            </label>
          </div>
          <div>
            <label className="flex items-center space-x-2">
              <input
                type="checkbox"
                checked={parameters.force_update}
                onChange={(e) => setParameters(prev => ({ ...prev, force_update: e.target.checked }))}
                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span className="text-sm font-medium text-mono-gray-700">
                Force Update Existing Data
              </span>
            </label>
          </div>
          <div>
            <label className="flex items-center space-x-2">
              <input
                type="checkbox"
                checked={parameters.backup_data}
                onChange={(e) => setParameters(prev => ({ ...prev, backup_data: e.target.checked }))}
                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span className="text-sm font-medium text-mono-gray-700">
                Backup Data Before Seeding
              </span>
            </label>
          </div>
          <div>
            <label className="flex items-center space-x-2">
              <input
                type="checkbox"
                checked={parameters.parallel_processing}
                onChange={(e) => setParameters(prev => ({ ...prev, parallel_processing: e.target.checked }))}
                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span className="text-sm font-medium text-mono-gray-700">
                Enable Parallel Processing
              </span>
            </label>
          </div>
        </div>

        {/* Advanced Options */}
        <div className="mb-4">
          <Button
            onClick={() => setShowAdvanced(!showAdvanced)}
            variant="outline"
            size="sm"
          >
            {showAdvanced ? 'Hide' : 'Show'} Advanced Options
          </Button>
        </div>

        {showAdvanced && (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-gray-50 rounded">
            <div>
              <label className="block text-sm font-medium text-mono-gray-700 mb-1">
                Batch Size
              </label>
              <Input
                type="number"
                value={parameters.batch_size}
                onChange={(e) => setParameters(prev => ({ 
                  ...prev, 
                  batch_size: parseInt(e.target.value) || 100 
                }))}
                min={1}
                max={1000}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-mono-gray-700 mb-1">
                Max Retries
              </label>
              <Input
                type="number"
                value={parameters.max_retries}
                onChange={(e) => setParameters(prev => ({ 
                  ...prev, 
                  max_retries: parseInt(e.target.value) || 3 
                }))}
                min={0}
                max={10}
              />
            </div>
          </div>
        )}

        {/* Action Buttons */}
        <div className="flex justify-end space-x-3">
          {isModal && onClose && (
            <Button
              onClick={onClose}
              variant="outline"
            >
              Cancel
            </Button>
          )}
          <Button
            onClick={handleSeedingSubmit}
            className={seedingMode === 'dry-run' 
              ? 'bg-blue-600 hover:bg-blue-700 text-white'
              : 'bg-red-600 hover:bg-red-700 text-white'
            }
            disabled={selectedBranches.length === 0 || loading}
          >
            {loading ? 'Processing...' : 
             seedingMode === 'dry-run' ? 'Run Dry Run' : 'Force Execute Seeding'}
          </Button>
        </div>
      </Card>

      {/* Seed Operations History */}
      <Card className="p-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-lg font-semibold text-mono-gray-900">Seeding Operations History</h3>
          <Button
            onClick={onRefresh}
            variant="outline"
            size="sm"
          >
            Refresh
          </Button>
        </div>

        {loading ? (
          <div className="flex justify-center py-8">
            <Spinner size="lg" />
          </div>
        ) : (
          <div className="space-y-4">
            {seedOperations.length === 0 ? (
              <p className="text-mono-gray-600 text-center py-8">No seeding operations found.</p>
            ) : (
              seedOperations.map((operation) => (
                <div key={operation.id} className="border rounded p-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="flex items-center gap-3">
                        <h4 className="font-medium text-mono-gray-900">
                          Operation #{operation.id}
                        </h4>
                        {getStatusBadge(operation.status)}
                        <Badge 
                          variant={operation.mode === 'dry_run' ? 'default' : 'warning'}
                        >
                          {operation.mode === 'dry_run' ? 'Dry Run' : 'Force Execute'}
                        </Badge>
                      </div>
                      <div className="text-sm text-mono-gray-600 mt-1">
                        Started: {formatDate(operation.started_at)} • 
                        {operation.completed_at ? ` Completed: ${formatDate(operation.completed_at)}` : ' In Progress'}
                      </div>
                      <div className="text-sm text-mono-gray-600">
                        Branches: {operation.branch_count} • 
                        Operations: {operation.total_operations}
                      </div>
                    </div>
                    <div className="text-right">
                      {operation.status === 'completed' && (
                        <div className="text-sm">
                          <div className="text-green-600">Success: {operation.successful_operations}</div>
                          <div className="text-red-600">Failed: {operation.failed_operations}</div>
                        </div>
                      )}
                    </div>
                  </div>

                  {operation.error_message && (
                    <div className="mt-3 p-3 bg-red-50 border border-red-200 rounded">
                      <p className="text-sm text-red-800">{operation.error_message}</p>
                    </div>
                  )}

                  {operation.logs && operation.logs.length > 0 && (
                    <div className="mt-3">
                      <details className="text-sm">
                        <summary className="cursor-pointer text-mono-gray-700 hover:text-mono-gray-900">
                          View Logs ({operation.logs.length} entries)
                        </summary>
                        <div className="mt-2 p-3 bg-gray-50 rounded max-h-32 overflow-y-auto">
                          {operation.logs.map((log, index) => (
                            <div key={index} className="font-mono text-xs text-mono-gray-600">
                              {formatDate(log.timestamp)}: {log.message}
                            </div>
                          ))}
                        </div>
                      </details>
                    </div>
                  )}
                </div>
              ))
            )}
          </div>
        )}
      </Card>
    </div>
  );

  if (isModal) {
    return (
      <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div className="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
          <div className="p-6">
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-2xl font-bold text-mono-gray-900">Branch Seeding Operations</h2>
              {onClose && (
                <Button
                  onClick={onClose}
                  variant="ghost"
                  size="sm"
                >
                  ×
                </Button>
              )}
            </div>
            {content}
          </div>
        </div>
      </div>
    );
  }

  return content;
};