import React, { useMemo, useState } from 'react';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Badge from '../components/ui/Badge';
import LoadingSpinner from '../components/ui/LoadingSpinner';
import Input from '../components/ui/Input';
import Select from '../components/ui/Select';
import CreateWorkflowModal, { type WorkflowFormData } from '../components/workflow/CreateWorkflowModal';
import EditWorkflowModal from '../components/workflow/EditWorkflowModal';
import {
  useWorkflowQueue,
  useCreateWorkflowItem,
  useUpdateWorkflowItem,
  useUpdateWorkflowStatus,
  useDeleteWorkflowItem,
} from '../hooks/useWorkflowQueue';
import type { WorkflowItem, WorkflowStatus } from '../types/dashboard';

type PriorityTone = 'high' | 'medium' | 'low';

type StatusFilter = 'all' | WorkflowStatus;
type PriorityFilter = 'all' | PriorityTone;

type StatusAction = {
  label: string;
  target: WorkflowStatus;
  icon: string;
};

const STATUS_METADATA: Record<WorkflowStatus, { label: string; description: string; accent: string }> = {
  pending: {
    label: 'Pending',
    description: 'Awaiting acknowledgement and assignment.',
    accent: 'border-mono-gray-200',
  },
  in_progress: {
    label: 'In Progress',
    description: 'Actively being handled by the operations team.',
    accent: 'border-mono-black',
  },
  delayed: {
    label: 'Delayed',
    description: 'Requires escalation to recover timeline commitments.',
    accent: 'border-amber-500',
  },
  completed: {
    label: 'Completed',
    description: 'Verified as resolved with full audit trail.',
    accent: 'border-emerald-500',
  },
};

const STATUS_ACTIONS: Record<WorkflowStatus, StatusAction[]> = {
  pending: [
    { label: 'Start', target: 'in_progress', icon: 'fa-play' },
    { label: 'Complete', target: 'completed', icon: 'fa-check' },
  ],
  in_progress: [
    { label: 'Complete', target: 'completed', icon: 'fa-check' },
    { label: 'Delay', target: 'delayed', icon: 'fa-clock' },
  ],
  delayed: [
    { label: 'Resume', target: 'in_progress', icon: 'fa-rotate-right' },
    { label: 'Complete', target: 'completed', icon: 'fa-check' },
  ],
  completed: [
    { label: 'Reopen', target: 'pending', icon: 'fa-rotate-left' },
  ],
};

const PRIORITY_TONE: Record<PriorityTone, string> = {
  high: 'bg-mono-black text-mono-white',
  medium: 'bg-mono-gray-800 text-mono-white',
  low: 'bg-mono-gray-500 text-mono-white',
};

const normalisePriority = (value: WorkflowItem['priority'] | undefined): PriorityTone => {
  if (value === 'high' || value === 'medium' || value === 'low') {
    return value;
  }
  if (typeof value === 'number') {
    if (value >= 4) return 'high';
    if (value >= 2) return 'medium';
    return 'low';
  }
  return 'medium';
};

const formatDateTime = (value?: string) => {
  if (!value) return 'No deadline';
  try {
    return new Date(value).toLocaleString();
  } catch (error) {
    return value;
  }
};

const TodoPage: React.FC = () => {
  const {
    data: items = [],
    isLoading,
    isError,
    error,
    refetch,
    isFetching,
  } = useWorkflowQueue();

  const createMutation = useCreateWorkflowItem();
  const updateMutation = useUpdateWorkflowItem();
  const updateStatusMutation = useUpdateWorkflowStatus();
  const deleteMutation = useDeleteWorkflowItem();

  const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');
  const [priorityFilter, setPriorityFilter] = useState<PriorityFilter>('all');
  const [searchTerm, setSearchTerm] = useState('');
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [activeItem, setActiveItem] = useState<WorkflowItem | null>(null);

  const filteredItems = useMemo(() => {
    return items.filter((item) => {
      const priority = normalisePriority(item.priority);
      const matchesStatus = statusFilter === 'all' || item.status === statusFilter;
      const matchesPriority = priorityFilter === 'all' || priority === priorityFilter;
      const matchesSearch = !searchTerm
        || item.title.toLowerCase().includes(searchTerm.toLowerCase())
        || item.description?.toLowerCase().includes(searchTerm.toLowerCase())
        || (item as any).trackingNumber?.toLowerCase().includes(searchTerm.toLowerCase());

      return matchesStatus && matchesPriority && matchesSearch;
    });
  }, [items, statusFilter, priorityFilter, searchTerm]);

  const groupedItems = useMemo(() => {
    return filteredItems.reduce<Record<WorkflowStatus, WorkflowItem[]>>(
      (acc, item) => {
        acc[item.status] = acc[item.status] ? [...acc[item.status], item] : [item];
        return acc;
      },
      { pending: [], in_progress: [], delayed: [], completed: [] },
    );
  }, [filteredItems]);

  const summaryCounts = useMemo(() => ({
    total: items.length,
    pending: items.filter((item) => item.status === 'pending').length,
    in_progress: items.filter((item) => item.status === 'in_progress').length,
    delayed: items.filter((item) => item.status === 'delayed').length,
    completed: items.filter((item) => item.status === 'completed').length,
  }), [items]);

  const handleCreateTask = async (formData: WorkflowFormData) => {
    try {
      await createMutation.mutateAsync(formData);
      setIsCreateModalOpen(false);
    } catch (mutationError) {
      console.error('Failed to create workflow item:', mutationError);
    }
  };

  const handleEdit = (item: WorkflowItem) => {
    setActiveItem(item);
    setIsEditModalOpen(true);
  };

  const handleUpdateTask = async (id: string, formData: WorkflowFormData) => {
    try {
      await updateMutation.mutateAsync({ id, data: formData });
      setIsEditModalOpen(false);
      setActiveItem(null);
    } catch (mutationError) {
      console.error('Failed to update workflow item:', mutationError);
    }
  };

  const handleStatusChange = async (item: WorkflowItem, nextStatus: WorkflowStatus) => {
    try {
      await updateStatusMutation.mutateAsync({ id: item.id, status: nextStatus });
    } catch (mutationError) {
      console.error('Failed to update status:', mutationError);
    }
  };

  const handleDelete = async (item: WorkflowItem) => {
    if (!confirm(`Delete task "${item.title}"?`)) {
      return;
    }

    try {
      await deleteMutation.mutateAsync(item.id);
      if (activeItem?.id === item.id) {
        setActiveItem(null);
        setIsEditModalOpen(false);
      }
    } catch (mutationError) {
      console.error('Failed to delete workflow item:', mutationError);
    }
  };

  const isMutating =
    createMutation.isPending ||
    updateMutation.isPending ||
    updateStatusMutation.isPending ||
    deleteMutation.isPending;

  if (isLoading && !items.length) {
    return <LoadingSpinner message="Loading task board" />;
  }

  if (isError) {
    const message = error instanceof Error ? error.message : 'Unable to load tasks';
    return (
      <div className="flex min-h-[320px] items-center justify-center">
        <Card className="max-w-md text-center">
          <div className="space-y-4">
            <div className="inline-flex h-16 w-16 items-center justify-center rounded-full bg-mono-black text-mono-white">
              <i className="fas fa-exclamation-triangle text-2xl" aria-hidden="true" />
            </div>
            <div>
              <h2 className="text-2xl font-semibold text-mono-black">Task board unavailable</h2>
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

  return (
    <div className="space-y-10">
      <header className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="space-y-2">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Operations Control</p>
          <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">Task Board</h1>
          <p className="text-sm text-mono-gray-600 max-w-2xl">
            Triage high-priority actions and keep operations flowing. Use filters to focus on what matters right now.
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-3">
          {isFetching && (
            <span className="text-xs uppercase tracking-[0.3em] text-mono-gray-500" aria-live="polite">
              Refreshing…
            </span>
          )}
          <Button variant="secondary" size="sm" onClick={() => refetch()} disabled={isFetching}>
            <i className="fas fa-sync-alt mr-2" aria-hidden="true" />
            Refresh
          </Button>
          <Button variant="primary" size="md" onClick={() => setIsCreateModalOpen(true)} disabled={isMutating}>
            <i className="fas fa-plus mr-2" aria-hidden="true" />
            New Task
          </Button>
        </div>
      </header>

      <section className="grid gap-4 md:grid-cols-4">
        <Card className="border border-mono-gray-200">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Open Tasks</p>
          <h2 className="text-3xl font-semibold text-mono-black mt-2">{summaryCounts.total}</h2>
        </Card>
        <Card className="border border-mono-gray-200">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Pending</p>
          <h2 className="text-2xl font-semibold text-mono-black mt-2">{summaryCounts.pending}</h2>
        </Card>
        <Card className="border border-mono-gray-200">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">In Progress</p>
          <h2 className="text-2xl font-semibold text-mono-black mt-2">{summaryCounts.in_progress}</h2>
        </Card>
        <Card className="border border-mono-gray-200">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Delayed</p>
          <h2 className="text-2xl font-semibold text-mono-black mt-2">{summaryCounts.delayed}</h2>
        </Card>
      </section>

      <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Input
          type="search"
          placeholder="Search tasks, notes, or tracking numbers..."
          value={searchTerm}
          onChange={(event) => setSearchTerm(event.target.value)}
          className="md:col-span-2 xl:col-span-2"
        />
        <Select
          value={statusFilter}
          onChange={(event) => setStatusFilter(event.target.value as StatusFilter)}
          options={[
            { value: 'all', label: 'All statuses' },
            { value: 'pending', label: 'Pending' },
            { value: 'in_progress', label: 'In Progress' },
            { value: 'delayed', label: 'Delayed' },
            { value: 'completed', label: 'Completed' },
          ]}
        />
        <Select
          value={priorityFilter}
          onChange={(event) => setPriorityFilter(event.target.value as PriorityFilter)}
          options={[
            { value: 'all', label: 'All priorities' },
            { value: 'high', label: 'High' },
            { value: 'medium', label: 'Medium' },
            { value: 'low', label: 'Low' },
          ]}
        />
      </section>

      <section className="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        {(Object.keys(STATUS_METADATA) as WorkflowStatus[]).map((status) => {
          const itemsForStatus = groupedItems[status];
          const metadata = STATUS_METADATA[status];

          return (
            <Card key={status} className={`border-2 rounded-3xl ${metadata.accent}`}>
              <header className="space-y-1">
                <div className="flex items-center justify-between">
                  <h2 className="text-xl font-semibold text-mono-black">{metadata.label}</h2>
                  <Badge variant="outline">{itemsForStatus.length}</Badge>
                </div>
                <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">{metadata.description}</p>
              </header>

              <div className="mt-6 space-y-4">
                {itemsForStatus.length === 0 && (
                  <div className="rounded-2xl border border-dashed border-mono-gray-200 bg-mono-gray-50 p-6 text-center">
                    <p className="text-sm text-mono-gray-500">No tasks in this column.</p>
                  </div>
                )}

                {itemsForStatus.map((item) => {
                  const priority = normalisePriority(item.priority);
                  return (
                    <div
                      key={item.id}
                      className="rounded-2xl border border-mono-gray-200 bg-mono-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-xl"
                    >
                      <div className="flex items-start justify-between gap-3">
                        <div className="space-y-1">
                          <h3 className="text-lg font-semibold text-mono-black">{item.title}</h3>
                          {item.description && (
                            <p className="text-sm text-mono-gray-600 leading-relaxed">
                              {item.description}
                            </p>
                          )}
                        </div>
                        <Badge variant="ghost" className={PRIORITY_TONE[priority]}>{priority.toUpperCase()}</Badge>
                      </div>

                      <div className="mt-4 space-y-2 text-sm text-mono-gray-600">
                        <div className="flex items-center gap-2">
                          <i className="fas fa-calendar-day text-xs" aria-hidden="true" />
                          <span>{formatDateTime((item as any).dueDate)}</span>
                        </div>
                        {item.assignedTo && (
                          <div className="flex items-center gap-2">
                            <i className="fas fa-user text-xs" aria-hidden="true" />
                            <span>Assigned to {item.assignedTo}</span>
                          </div>
                        )}
                        {(item as any).trackingNumber && (
                          <div className="flex items-center gap-2">
                            <i className="fas fa-barcode text-xs" aria-hidden="true" />
                            <span>Tracking {String((item as any).trackingNumber)}</span>
                          </div>
                        )}
                      </div>

                      <div className="mt-6 flex flex-wrap items-center gap-3">
                        {STATUS_ACTIONS[status].map((action) => (
                          <Button
                            key={action.target}
                            variant="ghost"
                            size="sm"
                            onClick={() => handleStatusChange(item, action.target)}
                            disabled={isMutating}
                            className="uppercase tracking-[0.2em]"
                          >
                            <i className={`fas ${action.icon} mr-2`} aria-hidden="true" />
                            {action.label}
                          </Button>
                        ))}
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleEdit(item)}
                          disabled={isMutating}
                          className="uppercase tracking-[0.2em]"
                        >
                          <i className="fas fa-pen mr-2" aria-hidden="true" />
                          Edit
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleDelete(item)}
                          disabled={isMutating}
                          className="text-red-600 uppercase tracking-[0.2em] hover:text-red-700"
                        >
                          <i className="fas fa-trash mr-2" aria-hidden="true" />
                          Delete
                        </Button>
                      </div>
                    </div>
                  );
                })}
              </div>
            </Card>
          );
        })}
      </section>

      <CreateWorkflowModal
        isOpen={isCreateModalOpen}
        onClose={() => setIsCreateModalOpen(false)}
        onSubmit={handleCreateTask}
        isLoading={createMutation.isPending}
      />

      <EditWorkflowModal
        isOpen={isEditModalOpen}
        onClose={() => {
          setIsEditModalOpen(false);
          setActiveItem(null);
        }}
        onSubmit={handleUpdateTask}
        item={activeItem}
        isLoading={updateMutation.isPending}
      />
    </div>
  );
};

export default TodoPage;
