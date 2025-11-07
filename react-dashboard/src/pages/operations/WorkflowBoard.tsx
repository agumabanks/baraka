import React, { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import Button from '../../components/ui/Button';
import AdvancedFiltersBar, { type AdvancedFilters } from '../../components/workflow/AdvancedFiltersBar';
import WorkflowKanbanBoard, {
  type AssignableUser,
  mapWorkflowStatusToColumn,
} from '../../components/workflow/WorkflowKanbanBoard';
import CreateWorkflowModal, { type WorkflowFormData } from '../../components/workflow/CreateWorkflowModal';
import EditWorkflowModal from '../../components/workflow/EditWorkflowModal';
import { useWorkflowQueue } from '../../hooks/useWorkflowQueue';
import {
  useCreateWorkflowItem,
  useUpdateWorkflowItem,
  useUpdateWorkflowStatus,
  useAssignWorkflowItem,
  useDeleteWorkflowItem,
} from '../../hooks/useWorkflowQueue';
import { exportToCSV, exportToExcel, prepareWorkflowDataForExport } from '../../utils/export';
import { adminUsersApi } from '../../services/api';
import useWorkflowStore from '../../stores/workflowStore';
import type { WorkflowBoardShipment } from '../../types/workflow';
import type { WorkflowStatus, WorkflowItem } from '../../types/dashboard';
import type { AdminUser } from '../../types/settings';

const initialFilters: AdvancedFilters = {
  priority: 'all',
  status: 'all',
  severity: 'all',
  search: '',
  dateFrom: '',
  dateTo: '',
  assignedTo: '',
  tags: [],
};

const getInitials = (name?: string | null) => {
  if (!name) return '?';
  const parts = name.trim().split(/\s+/);
  if (parts.length === 1) {
    return parts[0].slice(0, 2).toUpperCase();
  }
  return `${parts[0][0] ?? ''}${parts[parts.length - 1][0] ?? ''}`.toUpperCase();
};

const convertToBoardShipment = (item: WorkflowItem): WorkflowBoardShipment => {
  const priorityValue = (() => {
    if (typeof item.priority === 'string') {
      return item.priority;
    }
    if (typeof item.priority === 'number') {
      if (item.priority >= 4) return 'high';
      if (item.priority >= 2) return 'medium';
      return 'low';
    }
    return 'medium';
  })();

  return {
    id: item.id,
    tracking_number: item.trackingNumber ?? item.tracking_number ?? null,
    service_level: item.serviceLevel ?? null,
    status: item.status ?? null,
    status_label: item.statusLabel ?? item.status_label ?? null,
    title: item.title ?? item.trackingNumber ?? null,
    description: item.description ?? null,
    project: item.project ?? null,
    client: item.client ?? null,
    stage: item.stage ?? null,
    origin_branch: item.originBranch ?? null,
    destination_branch: item.destinationBranch ?? null,
    promised_at: item.promisedAt ?? item.promised_at ?? null,
    created_at: item.createdAt ?? item.created_at ?? null,
    priority: priorityValue,
    due_at: item.dueDate ?? item.due_at ?? null,
    tags: item.tags ?? [],
    assigned_user_id: item.assignedUserId ?? item.assigned_user_id ?? null,
    assigned_user_name: item.assignedTo ?? item.assigned_user_name ?? null,
    assigned_user_avatar: item.assignedUserAvatar ?? item.assigned_user_avatar ?? null,
    assigned_user_initials: item.assigned_user_initials ?? getInitials(item.assignedTo ?? item.assigned_user_name ?? undefined),
    dependencies: item.dependencies ?? null,
    attachments: item.attachments ?? null,
    time_tracking: (item.timeTracking ?? item.time_tracking ?? null) as WorkflowBoardShipment['time_tracking'],
    watchers: item.watchers ?? null,
    attachments_count: item.attachmentsCount ?? item.attachments_count ?? null,
    comments_count: item.commentsCount ?? item.comments_count ?? null,
    activity_count: item.activityCount ?? item.activity_count ?? null,
    allowed_transitions: item.allowedTransitions ?? item.allowed_transitions ?? undefined,
    restricted_roles: item.restrictedRoles ?? item.restricted_roles ?? undefined,
    project_id: item.projectId ?? item.project_id ?? null,
    metadata: item.metadata ?? null,
  };
};

const normalisePriority = (value: unknown): 'high' | 'medium' | 'low' => {
  if (typeof value === 'string') {
    const normalised = value.toLowerCase();
    if (normalised === 'high' || normalised === 'medium' || normalised === 'low') {
      return normalised;
    }
  }
  return 'medium';
};

const WorkflowBoardPage: React.FC = () => {
  const navigate = useNavigate();
  const queueState = useWorkflowStore((state) => state.queue);
  const {
    data: workflowData,
    isLoading,
    isError,
    error,
    refetch,
    isFetching,
  } = useWorkflowQueue();
  const createMutation = useCreateWorkflowItem();
  const updateMutation = useUpdateWorkflowItem();
  const statusMutation = useUpdateWorkflowStatus();
  const assignMutation = useAssignWorkflowItem();
  const deleteMutation = useDeleteWorkflowItem();

  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [editingTask, setEditingTask] = useState<WorkflowBoardShipment | null>(null);
  const [filters, setFilters] = useState<AdvancedFilters>(initialFilters);

  const { data: assignableUsersResponse } = useQuery({
    queryKey: ['workflow-board', 'assignable-users'],
    queryFn: async () => {
      const response = await adminUsersApi.getUsers({ per_page: 50, status: 1 });
      return response.data;
    },
    staleTime: 5 * 60 * 1000,
  });

  const assignableUsers = useMemo<AssignableUser[]>(() => {
    const records = (assignableUsersResponse as AdminUser[] | undefined) ?? [];
    return records.map((user) => ({
      id: String(user.id),
      name: user.name ?? 'Unnamed teammate',
      avatar: user.avatar ?? null,
      initials: getInitials(user.name),
    }));
  }, [assignableUsersResponse]);

  const tasks = useMemo<WorkflowBoardShipment[]>(() => {
    const source = queueState.length > 0 ? queueState : (workflowData?.tasks ?? []);
    return source.map((item) => convertToBoardShipment(item));
  }, [queueState, workflowData?.tasks]);

  const filteredTasks = useMemo(() => {
    return tasks.filter((task) => {
      if (filters.priority !== 'all') {
        const taskPriority = (task.priority ?? '').toString().toLowerCase();
        if (taskPriority !== filters.priority) {
          return false;
        }
      }

      if (filters.status !== 'all') {
        const filterColumn = mapWorkflowStatusToColumn(filters.status);
        if (mapWorkflowStatusToColumn(task.status) !== filterColumn) {
          return false;
        }
      }

      if (filters.search) {
        const haystack = [
          task.tracking_number,
          task.title,
          task.description,
          task.client,
          task.project,
          task.origin_branch,
          task.destination_branch,
        ]
          .filter(Boolean)
          .join(' ')
          .toLowerCase();

        if (!haystack.includes(filters.search.toLowerCase())) {
          return false;
        }
      }

      if (filters.assignedTo) {
        const assignedName = (task.assigned_user_name ?? '').toLowerCase();
        if (!assignedName.includes(filters.assignedTo.toLowerCase())) {
          return false;
        }
      }

      if (filters.dateFrom && task.created_at) {
        if (new Date(task.created_at) < new Date(filters.dateFrom)) {
          return false;
        }
      }

      if (filters.dateTo && task.created_at) {
        if (new Date(task.created_at) > new Date(filters.dateTo)) {
          return false;
        }
      }

      return true;
    });
  }, [tasks, filters]);

  const handleCreateTask = async (formData: WorkflowFormData) => {
    try {
      await createMutation.mutateAsync(formData);
      setIsCreateModalOpen(false);
    } catch (creationError) {
      console.error('Failed to create task:', creationError);
    }
  };

  const handleEditTask = (task: WorkflowBoardShipment) => {
    setEditingTask(task);
    setIsEditModalOpen(true);
  };

  const handleUpdateTask = async (id: string, formData: WorkflowFormData) => {
    try {
      await updateMutation.mutateAsync({ id, data: formData });
      setIsEditModalOpen(false);
      setEditingTask(null);
    } catch (updateError) {
      console.error('Failed to update task:', updateError);
    }
  };

  const handleStatusChange = async (taskId: string, status: WorkflowStatus) => {
    await statusMutation.mutateAsync({ id: taskId, status });
  };

  const handleAssign = async (taskId: string, userId: string) => {
    await assignMutation.mutateAsync({ id: taskId, assignedTo: userId });
  };

  const handleDeleteTask = async (taskId: string) => {
    await deleteMutation.mutateAsync(taskId);
  };

  const handleFilterChange = (nextFilters: AdvancedFilters) => {
    setFilters(nextFilters);
  };

  const handleClearFilters = () => {
    setFilters(initialFilters);
  };

  const handleExport = (format: 'csv' | 'excel') => {
    const exportRows = prepareWorkflowDataForExport(filteredTasks);
    if (exportRows.length === 0) {
      console.warn('Nothing to export — try broadening your filters.');
      return;
    }
    const dateStamp = new Date().toISOString().split('T')[0];
    const filename = `workflow-tasks-${dateStamp}.${format === 'csv' ? 'csv' : 'xlsx'}`;

    if (format === 'csv') {
      exportToCSV(exportRows, filename);
    } else {
      exportToExcel(exportRows, filename);
    }
  };

  const editingWorkflowItem = useMemo<WorkflowItem | null>(() => {
    if (!editingTask) return null;
    const column = mapWorkflowStatusToColumn(editingTask.status);
    const safeStatus = ((): WorkflowStatus => {
      switch (column) {
        case 'in_progress':
          return 'in_progress';
        case 'testing':
          return 'testing';
        case 'awaiting_feedback':
          return 'awaiting_feedback';
        case 'completed':
          return 'completed';
        default:
          return 'pending';
      }
    })();

    return {
      id: String(editingTask.id ?? editingTask.tracking_number ?? ''),
      title: editingTask.title ?? editingTask.tracking_number ?? 'Untitled task',
      description: editingTask.description ?? '',
      priority: normalisePriority(editingTask.priority),
      status: safeStatus,
      assignedTo: editingTask.assigned_user_id ? String(editingTask.assigned_user_id) : undefined,
      dueDate: editingTask.due_at ?? editingTask.promised_at ?? undefined,
      trackingNumber: editingTask.tracking_number ?? undefined,
      tags: editingTask.tags ?? [],
    };
  }, [editingTask]);

  if (isLoading && !workflowData) {
    return <LoadingSpinner message="Loading workflow board" />;
  }

  if (isError || !workflowData) {
    const message = error instanceof Error ? error.message : 'Unable to load workflow board.';
    return (
      <div className="flex min-h-[400px] flex-col items-center justify-center">
        <div className="max-w-lg rounded-3xl border border-mono-gray-200 bg-white p-10 text-center shadow-sm">
          <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-mono-black text-white">
            <i className="fas fa-exclamation-triangle text-2xl" aria-hidden="true" />
          </div>
          <h2 className="mt-4 text-2xl font-semibold text-mono-black">Workflow board unavailable</h2>
          <p className="mt-2 text-sm text-mono-gray-600">{message}</p>
          <Button variant="primary" size="sm" className="mt-6" onClick={() => refetch()}>
            <i className="fas fa-redo mr-2" aria-hidden="true" />
            Try Again
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-8">
      <header className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="space-y-2">
          <div className="flex items-center gap-3 text-sm text-mono-gray-500">
            <Button
              variant="ghost"
              size="sm"
              onClick={() => navigate('/dashboard')}
              className="text-mono-gray-600 hover:text-mono-black"
            >
              <i className="fas fa-arrow-left mr-2" aria-hidden="true" />
              Dashboard
            </Button>
            <span className="text-mono-gray-300">|</span>
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Control Tower
            </p>
          </div>
          <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">Workflow Board</h1>
          <p className="text-sm text-mono-gray-600">
            Drag tasks between swimlanes, assign teammates, and keep delivery execution visible in one monochrome view.
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
          <Button
            variant="primary"
            size="md"
            className="uppercase tracking-[0.25em]"
            onClick={() => setIsCreateModalOpen(true)}
          >
            <i className="fas fa-plus mr-2" aria-hidden="true" />
            Create Task
          </Button>
        </div>
      </header>

      <AdvancedFiltersBar
        filters={filters}
        onFilterChange={handleFilterChange}
        onClear={handleClearFilters}
        onExport={handleExport}
      />

      <WorkflowKanbanBoard
        tasks={filteredTasks}
        onEdit={handleEditTask}
        onStatusChange={handleStatusChange}
        onAssign={handleAssign}
        onDelete={handleDeleteTask}
        assignableUsers={assignableUsers}
        onCreate={() => setIsCreateModalOpen(true)}
        isUpdating={
          isFetching ||
          statusMutation.isPending ||
          assignMutation.isPending ||
          deleteMutation.isPending ||
          updateMutation.isPending
        }
      />

      <CreateWorkflowModal
        isOpen={isCreateModalOpen}
        onClose={() => setIsCreateModalOpen(false)}
        onSubmit={handleCreateTask}
        isLoading={createMutation.isPending}
        assignableUsers={assignableUsers}
      />

      <EditWorkflowModal
        isOpen={isEditModalOpen}
        onClose={() => {
          setIsEditModalOpen(false);
          setEditingTask(null);
        }}
        onSubmit={handleUpdateTask}
        item={editingWorkflowItem}
        isLoading={updateMutation.isPending}
        assignableUsers={assignableUsers}
      />
    </div>
  );
};

export default WorkflowBoardPage;
