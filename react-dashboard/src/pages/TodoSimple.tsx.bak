import React, { useState, useMemo } from 'react';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Badge from '../components/ui/Badge';
import LoadingSpinner from '../components/ui/LoadingSpinner';
import CreateWorkflowModal, { type WorkflowFormData } from '../components/workflow/CreateWorkflowModal';
import { useWorkflowBoard } from '../hooks/useWorkflowBoard';
import { useCreateWorkflowItem } from '../hooks/useWorkflowQueue';
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

type SortField = 'priority' | 'status' | 'created_at' | 'tracking_number';
type SortDirection = 'asc' | 'desc';

interface FilterState {
  priority: string;
  status: string;
  severity: string;
  search: string;
}

const TodoEnhanced: React.FC = () => {
  const { data, isLoading, isError, error, refetch, isFetching } = useWorkflowBoard();
  const createMutation = useCreateWorkflowItem();
  // TODO: Implement edit and delete functionality
  // const updateMutation = useUpdateWorkflowItem();
  // const deleteMutation = useDeleteWorkflowItem();
  // const updateStatusMutation = useUpdateWorkflowStatus();

  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [sortField, setSortField] = useState<SortField>('priority');
  const [sortDirection, setSortDirection] = useState<SortDirection>('desc');
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;

  const [filters, setFilters] = useState<FilterState>({
    priority: 'all',
    status: 'all',
    severity: 'all',
    search: '',
  });

  const handleCreateTask = async (formData: WorkflowFormData) => {
    try {
      await createMutation.mutateAsync(formData);
      setIsCreateModalOpen(false);
    } catch (error) {
      console.error('Failed to create task:', error);
    }
  };

  // TODO: Implement update/delete handlers
  // const handleUpdateStatus = async (id: string, status: 'pending' | 'in_progress' | 'completed' | 'delayed') => {
  //   try {
  //     await updateStatusMutation.mutateAsync({ id, status });
  //   } catch (error) {
  //     console.error('Failed to update status:', error);
  //   }
  // };

  const handleSort = (field: SortField) => {
    if (sortField === field) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
      setSortField(field);
      setSortDirection('desc');
    }
  };

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

  // Filter shipments
  const filteredShipments = useMemo(() => {
    return shipments.filter((shipment) => {
      if (filters.priority !== 'all' && shipment.priority !== filters.priority) return false;
      if (filters.search && !shipment.tracking_number?.toLowerCase().includes(filters.search.toLowerCase())) return false;
      return true;
    });
  }, [shipments, filters]);

  // Filter exceptions
  const filteredExceptions = useMemo(() => {
    return exceptions.filter((exception) => {
      if (filters.severity !== 'all' && exception.exception_severity !== filters.severity) return false;
      if (filters.search && !exception.tracking_number?.toLowerCase().includes(filters.search.toLowerCase())) return false;
      return true;
    });
  }, [exceptions, filters]);

  // Sorted shipments
  const sortedShipments = useMemo(() => {
    return [...filteredShipments].sort((a, b) => {
      let comparison = 0;
      switch (sortField) {
        case 'priority':
          const priorityOrder = { high: 3, medium: 2, low: 1 };
          comparison = (priorityOrder[a.priority as keyof typeof priorityOrder] || 0) - 
                      (priorityOrder[b.priority as keyof typeof priorityOrder] || 0);
          break;
        case 'tracking_number':
          comparison = (a.tracking_number || '').localeCompare(b.tracking_number || '');
          break;
        case 'created_at':
          comparison = new Date(a.created_at || 0).getTime() - new Date(b.created_at || 0).getTime();
          break;
      }
      return sortDirection === 'asc' ? comparison : -comparison;
    });
  }, [filteredShipments, sortField, sortDirection]);

  // Paginated shipments
  const paginatedShipments = useMemo(() => {
    const startIndex = (currentPage - 1) * itemsPerPage;
    return sortedShipments.slice(startIndex, startIndex + itemsPerPage);
  }, [sortedShipments, currentPage, itemsPerPage]);

  const totalPages = Math.ceil(sortedShipments.length / itemsPerPage);

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
          <Button 
            variant="primary" 
            size="md" 
            onClick={() => setIsCreateModalOpen(true)}
            className="uppercase tracking-[0.25em]"
          >
            <i className="fas fa-plus mr-2" aria-hidden="true" />
            Create Task
          </Button>
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

      {/* Summary Cards */}
      <section className="grid gap-6 lg:grid-cols-3">
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Unassigned Shipments</p>
            <h2 className="text-3xl font-semibold text-mono-black">{filteredShipments.length}</h2>
            <p className="text-sm text-mono-gray-600">Awaiting dispatch allocation</p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Active Exceptions</p>
            <h2 className="text-3xl font-semibold text-mono-black">{filteredExceptions.length}</h2>
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

      {/* Filters and Search */}
      <Card className="border border-mono-gray-200">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label className="block text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500 mb-2">
              Search
            </label>
            <input
              type="text"
              placeholder="Search tracking number..."
              value={filters.search}
              onChange={(e) => setFilters({ ...filters, search: e.target.value })}
              className="w-full px-3 py-2 border border-mono-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-mono-black"
            />
          </div>
          <div>
            <label className="block text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500 mb-2">
              Priority
            </label>
            <select
              value={filters.priority}
              onChange={(e) => setFilters({ ...filters, priority: e.target.value })}
              className="w-full px-3 py-2 border border-mono-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-mono-black"
            >
              <option value="all">All Priorities</option>
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
            </select>
          </div>
          <div>
            <label className="block text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500 mb-2">
              Severity
            </label>
            <select
              value={filters.severity}
              onChange={(e) => setFilters({ ...filters, severity: e.target.value })}
              className="w-full px-3 py-2 border border-mono-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-mono-black"
            >
              <option value="all">All Severities</option>
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
            </select>
          </div>
          <div className="flex items-end">
            <Button
              variant="secondary"
              size="sm"
              onClick={() => setFilters({ priority: 'all', status: 'all', severity: 'all', search: '' })}
              className="w-full"
            >
              <i className="fas fa-times mr-2" aria-hidden="true" />
              Clear Filters
            </Button>
          </div>
        </div>
      </Card>

      {/* Unassigned Shipments Section with Pagination */}
      <section>
        <Card className="border border-mono-gray-200">
          <header className="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Dispatch Actions</p>
              <h2 className="text-xl font-semibold text-mono-black">Unassigned queue</h2>
            </div>
            <div className="flex items-center gap-3">
              <Badge variant="outline" size="sm">{sortedShipments.length} items</Badge>
              <div className="flex items-center gap-2">
                <span className="text-xs text-mono-gray-500">Sort by:</span>
                <button
                  onClick={() => handleSort('priority')}
                  className={`text-xs px-2 py-1 rounded ${sortField === 'priority' ? 'bg-mono-black text-mono-white' : 'bg-mono-gray-100 text-mono-gray-700'}`}
                >
                  Priority {sortField === 'priority' && (sortDirection === 'asc' ? '↑' : '↓')}
                </button>
                <button
                  onClick={() => handleSort('created_at')}
                  className={`text-xs px-2 py-1 rounded ${sortField === 'created_at' ? 'bg-mono-black text-mono-white' : 'bg-mono-gray-100 text-mono-gray-700'}`}
                >
                  Date {sortField === 'created_at' && (sortDirection === 'asc' ? '↑' : '↓')}
                </button>
              </div>
            </div>
          </header>
          <div className="divide-y divide-mono-gray-200">
            {paginatedShipments.length === 0 ? (
              <p className="py-6 text-sm text-mono-gray-600 text-center">No shipments match your filters.</p>
            ) : (
              paginatedShipments.map((shipment) => (
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

          {/* Pagination */}
          {totalPages > 1 && (
            <div className="flex items-center justify-between pt-4 mt-4 border-t border-mono-gray-200">
              <p className="text-sm text-mono-gray-600">
                Page {currentPage} of {totalPages} ({sortedShipments.length} total items)
              </p>
              <div className="flex gap-2">
                <Button
                  variant="secondary"
                  size="sm"
                  onClick={() => setCurrentPage(currentPage - 1)}
                  disabled={currentPage === 1}
                >
                  <i className="fas fa-chevron-left" aria-hidden="true" />
                </Button>
                {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
                  const pageNum = i + 1;
                  return (
                    <button
                      key={pageNum}
                      onClick={() => setCurrentPage(pageNum)}
                      className={`px-3 py-1 text-sm rounded ${
                        currentPage === pageNum
                          ? 'bg-mono-black text-mono-white'
                          : 'bg-mono-gray-100 text-mono-gray-700 hover:bg-mono-gray-200'
                      }`}
                    >
                      {pageNum}
                    </button>
                  );
                })}
                <Button
                  variant="secondary"
                  size="sm"
                  onClick={() => setCurrentPage(currentPage + 1)}
                  disabled={currentPage === totalPages}
                >
                  <i className="fas fa-chevron-right" aria-hidden="true" />
                </Button>
              </div>
            </div>
          )}
        </Card>
      </section>

      {/* Exceptions Section */}
      <section>
        <Card className="border border-mono-gray-200">
          <header className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Exception Review</p>
              <h2 className="text-xl font-semibold text-mono-black">Priority cases</h2>
            </div>
            <Badge variant="outline" size="sm">{filteredExceptions.length} open</Badge>
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
                {filteredExceptions.length === 0 ? (
                  <tr>
                    <td colSpan={5} className="px-4 py-6 text-center text-sm text-mono-gray-500">
                      No exceptions match your filters.
                    </td>
                  </tr>
                ) : (
                  filteredExceptions.map((exception) => (
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

      {/* Notifications Section */}
      <section className="grid gap-6 lg:grid-cols-2">
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

      {/* Create Workflow Modal */}
      <CreateWorkflowModal
        isOpen={isCreateModalOpen}
        onClose={() => setIsCreateModalOpen(false)}
        onSubmit={handleCreateTask}
        isLoading={createMutation.isPending}
      />
    </div>
  );
};

export default TodoEnhanced;
