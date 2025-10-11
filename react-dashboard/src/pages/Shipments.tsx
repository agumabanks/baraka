import React, { useState } from 'react';
import Card from '../components/ui/Card';
import Badge from '../components/ui/Badge';
import Button from '../components/ui/Button';
import LoadingSpinner from '../components/ui/LoadingSpinner';
import CreateShipmentModal from '../components/shipments/CreateShipmentModal';
import { useWorkflowBoard, useOperationsInsights } from '../hooks/useWorkflowBoard';
import type { WorkflowBoardDriverQueue, WorkflowBoardException } from '../types/workflow';

const formatPercent = (value?: number | null) => {
  if (value === undefined || value === null || Number.isNaN(Number(value))) {
    return '0%';
  }
  return `${Math.round(Number(value))}%`;
};

const Shipments: React.FC = () => {
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  
  const {
    data: workflow,
    isLoading: isWorkflowLoading,
    isError: isWorkflowError,
    error: workflowError,
    refetch: refetchWorkflow,
    isFetching: isWorkflowFetching,
  } = useWorkflowBoard();
  const {
    data: operations,
    isError: isOperationsError,
    error: operationsError,
    refetch: refetchOperations,
    isFetching: isOperationsFetching,
  } = useOperationsInsights();

  if (isWorkflowLoading && !workflow) {
    return <LoadingSpinner message="Loading operations control centre" />;
  }

  if (isWorkflowError || !workflow) {
    const message = workflowError instanceof Error ? workflowError.message : 'Unable to load operations data';
    return (
      <div className="flex min-h-[400px] flex-col items-center justify-center">
        <Card className="max-w-md text-center">
          <div className="space-y-4">
            <div className="inline-flex h-16 w-16 items-center justify-center rounded-full bg-mono-black text-mono-white">
              <i className="fas fa-exclamation-circle text-2xl" aria-hidden="true" />
            </div>
            <div>
              <h2 className="text-2xl font-semibold text-mono-black">Operations feed unavailable</h2>
              <p className="text-sm text-mono-gray-600">{message}</p>
            </div>
            <Button variant="primary" size="md" onClick={() => refetchWorkflow()}>
              <i className="fas fa-redo mr-2" aria-hidden="true" />
              Retry
            </Button>
          </div>
        </Card>
      </div>
    );
  }

  const shipmentsKpis = (workflow.kpis?.shipments ?? {}) as Record<string, number>;
  const performanceKpis = (workflow.kpis?.performance ?? {}) as Record<string, number>;
  const workerStats = (workflow.kpis?.workers ?? {}) as Record<string, number>;
  const exceptionSummary = (workflow.exception_metrics?.summary ?? {}) as Record<string, number>;
  const loadBalancing = (workflow.queues.load_balancing ?? {}) as Record<string, unknown>;
  const isLoadBalancingActive = Object.keys(loadBalancing ?? {}).length > 0;
  const driverQueues = (workflow.queues.driver_queues ?? []) as WorkflowBoardDriverQueue[];
  const exceptions = (workflow.queues.exceptions ?? []) as WorkflowBoardException[];
  const dispatchSnapshot = (workflow.dispatch_snapshot ?? {}) as Record<string, any>;

  const operationsAlerts = Array.isArray(operations?.alerts)
    ? (operations?.alerts as Array<Record<string, unknown>>)
    : ((workflow.kpis?.alerts ?? []) as Array<Record<string, unknown>>);

  const shipmentMetrics = (operations?.shipmentMetrics ?? workflow.shipment_metrics ?? {}) as Record<string, any>;

  const exceptionRate = (shipmentMetrics.exceptions?.exception_rate as number | undefined) ?? 0;

  const refreshAll = () => {
    refetchWorkflow();
    refetchOperations();
  };

  return (
    <>
      <div className="space-y-10">
        <header className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Operations Command</p>
            <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">Unified Shipment Control Centre</h1>
            <p className="text-sm text-mono-gray-600">
              Monitor dispatch queues, worker capacity, and SLA performance with Steve Jobs-level monochrome precision.
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            {(isWorkflowFetching || isOperationsFetching) && (
              <span className="text-xs uppercase tracking-[0.3em] text-mono-gray-500" aria-live="polite">
                Refreshing…
              </span>
            )}
            <Button 
              variant="primary" 
              size="sm" 
              className="uppercase tracking-[0.25em]" 
              onClick={() => setIsCreateModalOpen(true)}
            >
              <i className="fas fa-plus mr-2" aria-hidden="true" />
              New Shipment
            </Button>
            <Button variant="secondary" size="sm" className="uppercase tracking-[0.25em]" onClick={refreshAll}>
              <i className="fas fa-sync-alt mr-2" aria-hidden="true" />
              Refresh
            </Button>
          </div>
        </header>

      <section className="grid gap-6 lg:grid-cols-4">
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Active Shipments</p>
            <p className="text-3xl font-semibold text-mono-black">{shipmentsKpis.active ?? 0}</p>
            <p className="text-sm text-mono-gray-600">Out of {shipmentsKpis.total ?? 0} network-wide</p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">On-time Delivery</p>
            <p className="text-3xl font-semibold text-mono-black">{formatPercent(performanceKpis.on_time_delivery_rate)}</p>
            <p className="text-sm text-mono-gray-600">Real-time SLA adherence</p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Exception Queue</p>
            <p className="text-3xl font-semibold text-mono-black">{exceptionSummary.total_exceptions ?? 0}</p>
            <p className="text-sm text-mono-gray-600">{exceptionSummary.unresolved_exceptions ?? 0} unresolved cases</p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Workforce Utilisation</p>
            <p className="text-3xl font-semibold text-mono-black">{formatPercent(workerStats.utilization_rate)}</p>
            <p className="text-sm text-mono-gray-600">{workerStats.active ?? 0} active of {workerStats.total ?? 0}</p>
          </div>
        </Card>
      </section>

      <section className="grid gap-6 lg:grid-cols-[2fr,1fr]">
        <Card className="border border-mono-gray-200">
          <header className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Dispatch Board</p>
              <h2 className="text-xl font-semibold text-mono-black">Driver load balancing</h2>
            </div>
            <Badge variant="outline" size="sm">{driverQueues.length} active drivers</Badge>
          </header>
          <div className="mt-4 overflow-x-auto">
            <table className="min-w-full divide-y divide-mono-gray-200 text-left">
              <thead className="bg-mono-gray-50 text-xs uppercase tracking-[0.3em] text-mono-gray-500">
                <tr>
                  <th scope="col" className="px-4 py-3">Driver</th>
                  <th scope="col" className="px-4 py-3">Assignments</th>
                  <th scope="col" className="px-4 py-3">Capacity</th>
                  <th scope="col" className="px-4 py-3">Utilisation</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-mono-gray-100 text-sm text-mono-gray-700">
                {driverQueues.length === 0 ? (
                  <tr>
                    <td colSpan={4} className="px-4 py-6 text-center text-sm text-mono-gray-500">
                      No driver assignments available.
                    </td>
                  </tr>
                ) : (
                  driverQueues.map((queue) => (
                    <tr key={`${queue.worker_id}-${queue.worker_name}`}>
                      <td className="px-4 py-3 font-medium text-mono-black">{queue.worker_name ?? 'Unassigned'}</td>
                      <td className="px-4 py-3">{queue.assigned_shipments}</td>
                      <td className="px-4 py-3">{queue.capacity ?? '—'}</td>
                      <td className="px-4 py-3">{formatPercent(queue.utilization)}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </Card>

        <Card className="border border-mono-gray-200">
          <header className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Network Pulse</p>
            <h2 className="text-lg font-semibold text-mono-black">Load status</h2>
          </header>
          <div className="mt-4 space-y-3 text-sm text-mono-gray-600">
            <div className="rounded-2xl border border-mono-gray-200 p-4">
              <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Queue depth</p>
              <p className="text-mono-black">{(dispatchSnapshot.queue_depth as number | undefined) ?? '—'} shipments</p>
            </div>
            <div className="rounded-2xl border border-mono-gray-200 p-4">
              <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Load balancing</p>
              <p className="text-mono-black">{isLoadBalancingActive ? 'Active balancing in progress' : 'Optimisation pending'}</p>
            </div>
            <div className="rounded-2xl border border-mono-gray-200 p-4">
              <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Exception rate</p>
              <p className="text-mono-black">{formatPercent(exceptionRate)}</p>
            </div>
          </div>
        </Card>
      </section>

      <section className="grid gap-6 lg:grid-cols-[2fr,1fr]">
        <Card className="border border-mono-gray-200">
          <header className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Exception Tower</p>
              <h2 className="text-xl font-semibold text-mono-black">Active exceptions</h2>
            </div>
            <Badge variant="outline" size="sm">{exceptions.length} open</Badge>
          </header>
          <div className="mt-4 overflow-x-auto">
            <table className="min-w-full divide-y divide-mono-gray-200 text-left">
              <thead className="bg-mono-gray-50 text-xs uppercase tracking-[0.3em] text-mono-gray-500">
                <tr>
                  <th scope="col" className="px-4 py-3">Shipment</th>
                  <th scope="col" className="px-4 py-3">Type</th>
                  <th scope="col" className="px-4 py-3">Severity</th>
                  <th scope="col" className="px-4 py-3">Branch</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-mono-gray-100 text-sm text-mono-gray-700">
                {exceptions.length === 0 ? (
                  <tr>
                    <td colSpan={4} className="px-4 py-6 text-center text-sm text-mono-gray-500">
                      All shipments are healthy.
                    </td>
                  </tr>
                ) : (
                  exceptions.map((exception) => (
                    <tr key={exception.id ?? exception.tracking_number}>
                      <td className="px-4 py-3 font-medium text-mono-black">{exception.tracking_number ?? '—'}</td>
                      <td className="px-4 py-3">{exception.exception_type ?? '—'}</td>
                      <td className="px-4 py-3">{exception.exception_severity ?? '—'}</td>
                      <td className="px-4 py-3">{exception.branch ?? '—'}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </Card>

        <Card className="border border-mono-gray-200">
          <header className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Critical Alerts</p>
            <h2 className="text-lg font-semibold text-mono-black">Immediate actions</h2>
          </header>
          <div className="mt-4 space-y-3 text-sm text-mono-gray-600">
            {operationsAlerts?.length ? (
              operationsAlerts.map((alert, index) => (
                <div key={`${alert.type ?? index}`} className="rounded-2xl border border-mono-gray-200 p-4">
                  <p className="text-sm font-semibold text-mono-black">{(alert.title as string) ?? 'Operational Alert'}</p>
                  <p>{(alert.message as string) ?? 'Review in control tower'}</p>
                  <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">{(alert.severity as string)?.toUpperCase() ?? 'INFO'}</p>
                </div>
              ))
            ) : (
              <p>No critical alerts. Keep the cadence.</p>
            )}
            {isOperationsError && (
              <div className="rounded-2xl border border-dashed border-mono-gray-300 p-4 text-xs text-mono-gray-500">
                {(operationsError as Error)?.message ?? 'Operations insight service unavailable.'}
              </div>
            )}
          </div>
        </Card>
      </section>
      </div>

      {/* Create Shipment Modal */}
      <CreateShipmentModal 
        isOpen={isCreateModalOpen} 
        onClose={() => setIsCreateModalOpen(false)} 
      />
    </>
  );
};

export default Shipments;
