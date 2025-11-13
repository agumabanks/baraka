import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Card } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import { Spinner } from '../../components/ui/Spinner';
import { branchOperationsApi } from '../../services/api';
import type { ApiResponse } from '../../services/api';
import {
  BranchManagement,
  BranchSeedOperation,
  BranchSeedParameters,
  BranchPerformanceMetrics,
  BranchSeedSimulation,
} from '../../types/branch-operations';
import { BranchList } from '../../components/operations/branches/BranchList';
import { BranchSeedingPanel } from '../../components/operations/branches/BranchSeedingPanel';
import { BranchPerformancePanel } from '../../components/operations/branches/BranchPerformancePanel';
import { BranchAlertsPanel } from '../../components/operations/branches/BranchAlertsPanel';
import { BranchMaintenancePanel } from '../../components/operations/branches/BranchMaintenancePanel';
import { toast } from '../../stores/toastStore';

type TabType = 'overview' | 'branches' | 'seeding' | 'performance' | 'alerts' | 'maintenance';

export const BranchOperationsPanel: React.FC = () => {
  const [activeTab, setActiveTab] = useState<TabType>('overview');
  const [selectedBranch, setSelectedBranch] = useState<BranchManagement | null>(null);
  const [showBranchDetails, setShowBranchDetails] = useState(false);
  const [showSeedingPanel, setShowSeedingPanel] = useState(false);
  const [seedingMode, setSeedingMode] = useState<'dry-run' | 'force-execute'>('dry-run');
  const queryClient = useQueryClient();

  // Queries
  const { data: branchesResponse, isLoading: branchesLoading } = useQuery({
    queryKey: ['branch-operations', 'branches'],
    queryFn: () => branchOperationsApi.getBranches(),
  });

  const { data: analyticsResponse, isLoading: analyticsLoading } = useQuery({
    queryKey: ['branch-operations', 'analytics'],
    queryFn: branchOperationsApi.getAnalytics,
  });

  const { data: maintenanceWindowsResponse, isLoading: maintenanceLoading } = useQuery({
    queryKey: ['branch-operations', 'maintenance'],
    queryFn: branchOperationsApi.getMaintenanceWindows,
  });

  const { data: seedOperationsResponse, isLoading: seedOperationsLoading } = useQuery({
    queryKey: ['branch-operations', 'seed-operations'],
    queryFn: branchOperationsApi.getSeedOperations,
  });

  // Mutations
  const seedOperationMutation = useMutation<
    ApiResponse<BranchSeedOperation | BranchSeedSimulation>,
    unknown,
    { payload: BranchSeedParameters; mode: 'dry-run' | 'force-execute' }
  >({
    mutationFn: ({ payload, mode }) =>
      mode === 'dry-run'
        ? branchOperationsApi.dryRunSeed(payload)
        : branchOperationsApi.forceSeedExecute(payload),
    onSuccess: (result, { mode }) => {
      queryClient.invalidateQueries({ queryKey: ['branch-operations', 'seed-operations'] });
      queryClient.invalidateQueries({ queryKey: ['branch-operations', 'branches'] });
      
      if (mode === 'dry-run') {
        const simulation = result.data as BranchSeedSimulation | undefined;
        toast.success({
          title: 'Dry Run Completed',
          description: `Simulation completed: ${simulation?.simulated_results?.total_operations ?? 0} operations simulated.`,
        });
      } else {
        toast.success({
          title: 'Force Seed Executed',
          description: 'Force seed operation has been executed successfully.',
        });
      }
      
      setShowSeedingPanel(false);
    },
    onError: (error: unknown) => {
      toast.error({
        title: 'Operation Failed',
        description: error instanceof Error ? error.message : 'Failed to execute branch operation.',
      });
    },
  });

  const startSeedOperationMutation = useMutation({
    mutationFn: (payload: BranchSeedParameters) => branchOperationsApi.startSeedOperation(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['branch-operations', 'seed-operations'] });
      toast.success({
        title: 'Seed Operation Started',
        description: 'Branch seeding operation has been started successfully.',
      });
    },
    onError: (error: unknown) => {
      toast.error({
        title: 'Operation Failed',
        description: error instanceof Error ? error.message : 'Failed to start seed operation.',
      });
    },
  });

  const handleBranchView = (branch: BranchManagement) => {
    setSelectedBranch(branch);
    setShowBranchDetails(true);
  };

  const handleSeedingOperation = (payload: BranchSeedParameters, mode: 'dry-run' | 'force-execute') => {
    setSeedingMode(mode);
    seedOperationMutation.mutate({ payload, mode });
  };

  const branches = branchesResponse?.data?.data || [];
  const analytics = analyticsResponse?.data;
  const maintenanceWindows = maintenanceWindowsResponse?.data || [];
  const seedOperations = seedOperationsResponse?.data || [];

  const tabs = [
    { id: 'overview', label: 'Overview', icon: 'LayoutDashboard' },
    { id: 'branches', label: 'Branches', icon: 'Building2' },
    { id: 'seeding', label: 'Seeding', icon: 'Seedling' },
    { id: 'performance', label: 'Performance', icon: 'TrendingUp' },
    { id: 'alerts', label: 'Alerts', icon: 'AlertTriangle' },
    { id: 'maintenance', label: 'Maintenance', icon: 'Wrench' },
  ] as const;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-mono-gray-900">Branch Operations Management</h1>
          <p className="text-mono-gray-600 mt-1">
            Monitor branch performance, manage seeding operations, and handle system maintenance
          </p>
        </div>
        <div className="flex gap-2">
          <Button
            onClick={() => setShowSeedingPanel(true)}
            className="bg-green-600 hover:bg-green-700 text-white"
          >
            Start Seeding Operation
          </Button>
        </div>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-6 gap-4">
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-blue-600">{branches.length}</p>
            <p className="text-sm text-mono-gray-600">Total Branches</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-green-600">
              {branches.filter(b => b.status === 'active').length}
            </p>
            <p className="text-sm text-mono-gray-600">Active</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-red-600">
              {branches.filter(b => b.status === 'inactive').length}
            </p>
            <p className="text-sm text-mono-gray-600">Inactive</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-yellow-600">
              {branches.filter(b => b.status === 'maintenance').length}
            </p>
            <p className="text-sm text-mono-gray-600">Maintenance</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-purple-600">{seedOperations.length}</p>
            <p className="text-sm text-mono-gray-600">Seed Operations</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-orange-600">{maintenanceWindows.length}</p>
            <p className="text-sm text-mono-gray-600">Maintenance Windows</p>
          </div>
        </Card>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200">
        <nav className="-mb-px flex space-x-8">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === tab.id
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <div className="flex items-center gap-2">
                {tab.icon === 'LayoutDashboard' && (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                  </svg>
                )}
                {tab.icon === 'Building2' && (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                )}
                {tab.icon === 'Seedling' && (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                  </svg>
                )}
                {tab.icon === 'TrendingUp' && (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                  </svg>
                )}
                {tab.icon === 'AlertTriangle' && (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.268 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                )}
                {tab.icon === 'Wrench' && (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                )}
                {tab.label}
              </div>
            </button>
          ))}
        </nav>
      </div>

      {/* Tab Content */}
      <div className="min-h-[600px]">
        {activeTab === 'overview' && (
          <div className="space-y-6">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <BranchPerformancePanel 
                data={analytics} 
                loading={analyticsLoading} 
              />
              <div className="space-y-4">
                <h3 className="text-lg font-semibold text-mono-gray-900">Recent Seed Operations</h3>
                <Card className="p-6">
                  {seedOperationsLoading ? (
                    <div className="flex justify-center py-8">
                      <Spinner size="md" />
                    </div>
                  ) : (
                    <div className="space-y-3">
                      {seedOperations.slice(0, 5).map((operation: BranchSeedOperation) => (
                        <div key={operation.id} className="flex items-center justify-between p-3 bg-gray-50 rounded">
                          <div className="flex items-center gap-3">
                            <div className={`w-2 h-2 rounded-full ${
                              operation.status === 'completed' ? 'bg-green-500' : 
                              operation.status === 'failed' ? 'bg-red-500' : 'bg-yellow-500'
                            }`} />
                            <div>
                              <p className="text-sm font-medium text-mono-gray-900">Operation #{operation.id}</p>
                              <p className="text-xs text-mono-gray-600">{operation.started_at}</p>
                            </div>
                          </div>
                          <Badge 
                            variant={operation.status === 'completed' ? 'success' : 
                                   operation.status === 'failed' ? 'destructive' : 'default'}
                          >
                            {operation.status}
                          </Badge>
                        </div>
                      ))}
                    </div>
                  )}
                </Card>
              </div>
            </div>
          </div>
        )}

        {activeTab === 'branches' && (
          <BranchList
            branches={branches}
            loading={branchesLoading}
            onViewDetails={handleBranchView}
            onRefresh={() => queryClient.invalidateQueries({ queryKey: ['branch-operations', 'branches'] })}
          />
        )}

        {activeTab === 'seeding' && (
          <BranchSeedingPanel
            branches={branches}
            seedOperations={seedOperations}
            loading={seedOperationsLoading}
            onSeedingOperation={handleSeedingOperation}
            onRefresh={() => queryClient.invalidateQueries({ queryKey: ['branch-operations', 'seed-operations'] })}
          />
        )}

        {activeTab === 'performance' && (
          <BranchPerformancePanel 
            data={analytics} 
            loading={analyticsLoading}
            detailed={true}
          />
        )}

        {activeTab === 'alerts' && (
          <BranchAlertsPanel
            onRefresh={() => queryClient.invalidateQueries({ queryKey: ['branch-operations', 'branches'] })}
          />
        )}

        {activeTab === 'maintenance' && (
          <BranchMaintenancePanel
            branches={branches}
            maintenanceWindows={maintenanceWindows}
            loading={maintenanceLoading}
            onRefresh={() => queryClient.invalidateQueries({ queryKey: ['branch-operations', 'maintenance'] })}
          />
        )}
      </div>

      {/* Seeding Panel Modal */}
      {showSeedingPanel && (
        <BranchSeedingPanel
          branches={branches}
          seedOperations={seedOperations}
          loading={seedOperationsLoading}
          onSeedingOperation={handleSeedingOperation}
          onClose={() => setShowSeedingPanel(false)}
          isModal={true}
          onRefresh={() => queryClient.invalidateQueries({ queryKey: ['branch-operations', 'seed-operations'] })}
        />
      )}
    </div>
  );
};