import React from 'react';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Badge from '../components/ui/Badge';
import LoadingSpinner from '../components/ui/LoadingSpinner';
import { useWorkflowBoard } from '../hooks/useWorkflowBoard';
import type { WorkflowBoardShipment, WorkflowBoardException, WorkflowBoardNotification } from '../types/workflow';

const formatDateTime = (value?: string | null) => {
  if (!value) return '—';
  return new Date(value).toLocaleString();
};

const priorityTone: Record<string, string> = {
  high: 'bg-mono-black text-mono-white',
  medium: 'bg-mono-gray-800 text-mono-white',
  low: 'bg-mono-gray-500 text-mono-white',
};

const Todo: React.FC = () => {
  const { data, isLoading, isError, error, refetch, isFetching } = useWorkflowBoard();

  if (isLoading && !data) {
    return <LoadingSpinner message="Loading workflow actions" />;
  }

  if (isError || !data) {
    const message = error instanceof Error ? error.message : 'Unable to load workflow actions';
    return (
      <div className="flex min-h-[400px] flex-col items-center justify-center">
        <Card className="max-w-md text-center">
          <div className="space-y-4">
            <div className="inline-flex h-16 w-16 items-center justify-center rounded-full bg-mono-black text-mono-white">
              <i className="fas fa-exclamation-triangle text-2xl" aria-hidden="true" />
            </div>
            <div>
              <h2 className="text-2xl font-semibold text-mono-black">Workflow board unavailable</h2>
              <p className="text-sm text-mono-gray-600">{message}</p>
            </div>
            <Button variant="primary" size="md" onClick={() => refetch()}>
              <i className="fas fa-redo mr-2" aria-hidden="true" />
              Retry
            </Button>
          </div>
        </Card>
      </div>
    );
  }

  const shipments = data.queues.unassigned_shipments as WorkflowBoardShipment[];
  const exceptions = data.queues.exceptions as WorkflowBoardException[];
  const notifications = data.notifications as WorkflowBoardNotification[];

  return (
    <div className="space-y-10">
      <header className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="space-y-2">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Control Tower</p>
          <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">Workflow Board</h1>
          <p className="text-sm text-mono-gray-600">
            Coordinate dispatch, resolve exceptions, and keep the network flowing with a single monochrome cockpit.
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-3">
          {isFetching && (
            <span className="text-xs uppercase tracking-[0.3em] text-mono-gray-500" aria-live="polite">
              Refreshing…
            </span>
          )}
          <Button variant="secondary" size="sm" className="uppercase tracking-[0.25em]" onClick={() => refetch()}>
            <i className="fas fa-sync-alt mr-2" aria-hidden="true" />
            Refresh
          </Button>
        </div>
      </header>

      <section className="grid gap-6 lg:grid-cols-3">
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Unassigned Shipments</p>
            <h2 className="text-3xl font-semibold text-mono-black">{shipments.length}</h2>
            <p className="text-sm text-mono-gray-600">Awaiting dispatch allocation</p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Active Exceptions</p>
            <h2 className="text-3xl font-semibold text-mono-black">{exceptions.length}</h2>
            <p className="text-sm text-mono-gray-600">Flagged for immediate investigation</p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Unread Signals</p>
            <h2 className="text-3xl font-semibold text-mono-black">{notifications.length}</h2>
            <p className="text-sm text-mono-gray-600">Operational alerts for the next shift</p>
          </div>
        </Card>
      </section>

      <section className="grid gap-6 lg:grid-cols-[2fr,1fr]">
        <Card className="border border-mono-gray-200">
          <header className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Dispatch Actions</p>
              <h2 className="text-xl font-semibold text-mono-black">Unassigned queue</h2>
            </div>
            <Badge variant="outline" size="sm">{shipments.length} pending</Badge>
          </header>
          <div className="mt-4 divide-y divide-mono-gray-200">
            {shipments.length === 0 ? (
              <p className="py-6 text-sm text-mono-gray-600">Network is fully assigned. Great work.</p>
            ) : (
              shipments.map((shipment) => (
                <div key={shipment.id ?? shipment.tracking_number} className="flex flex-col gap-2 py-4 lg:flex-row lg:items-center lg:justify-between">
                  <div>
                    <p className="text-sm font-semibold text-mono-black">{shipment.tracking_number ?? 'Unknown shipment'}</p>
                    <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">
                      {shipment.origin_branch ?? 'Origin pending'} → {shipment.destination_branch ?? 'Destination pending'}
                    </p>
                  </div>
                  <div className="flex flex-wrap items-center gap-3 text-sm text-mono-gray-600">
                    <span>
                      <i className="fas fa-clock mr-2" aria-hidden="true" />
                      {formatDateTime(shipment.promised_at)}
                    </span>
                    <Badge
                      variant="solid"
                      size="sm"
                      className={priorityTone[shipment.priority ?? 'low'] ?? 'bg-mono-gray-700 text-mono-white'}
                    >
                      {shipment.priority ?? 'Priority TBD'}
                    </Badge>
                  </div>
                </div>
              ))
            )}
          </div>
        </Card>

        <Card className="border border-mono-gray-200">
          <header className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Signals</p>
            <h2 className="text-lg font-semibold text-mono-black">Latest Notifications</h2>
          </header>
          <ul className="mt-4 space-y-3 text-sm text-mono-gray-600">
            {notifications.length === 0 ? (
              <li>No unread notifications.</li>
            ) : (
              notifications.map((notification) => (
                <li key={notification.id} className="rounded-2xl border border-mono-gray-200 p-4">
                  <p className="font-medium text-mono-black">{notification.title ?? 'Operational Alert'}</p>
                  <p>{notification.message ?? 'See control tower for more details.'}</p>
                  <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">{formatDateTime(notification.created_at)}</p>
                </li>
              ))
            )}
          </ul>
        </Card>
      </section>

      <section>
        <Card className="border border-mono-gray-200">
          <header className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Exception Review</p>
              <h2 className="text-xl font-semibold text-mono-black">Priority cases</h2>
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
                  <th scope="col" className="px-4 py-3">Updated</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-mono-gray-100 text-sm text-mono-gray-700">
                {exceptions.length === 0 ? (
                  <tr>
                    <td colSpan={5} className="px-4 py-6 text-center text-sm text-mono-gray-500">
                      All exceptions resolved.
                    </td>
                  </tr>
                ) : (
                  exceptions.map((exception) => (
                    <tr key={exception.id ?? exception.tracking_number}>
                      <td className="px-4 py-3 font-medium text-mono-black">{exception.tracking_number ?? '—'}</td>
                      <td className="px-4 py-3">{exception.exception_type ?? '—'}</td>
                      <td className="px-4 py-3">
                        <Badge variant="solid" size="sm" className={priorityTone[exception.exception_severity ?? 'low'] ?? 'bg-mono-gray-700 text-mono-white'}>
                          {exception.exception_severity ?? 'Unrated'}
                        </Badge>
                      </td>
                      <td className="px-4 py-3">{exception.branch ?? '—'}</td>
                      <td className="px-4 py-3">{formatDateTime(exception.updated_at)}</td>
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

export default Todo;
