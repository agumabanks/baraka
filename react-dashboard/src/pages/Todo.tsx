import React, { useMemo, useState, useEffect, useCallback, useRef } from 'react';
import { useQuery } from '@tanstack/react-query';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import LoadingSpinner from '../components/ui/LoadingSpinner';
import Input from '../components/ui/Input';
import Select from '../components/ui/Select';
import BulkActionsBar from '../components/workflow/BulkActionsBar';
import WorkflowKanbanBoard, { type AssignableUser } from '../components/workflow/WorkflowKanbanBoard';
import CreateWorkflowModal, { type WorkflowFormData } from '../components/workflow/CreateWorkflowModal';
import EditWorkflowModal from '../components/workflow/EditWorkflowModal';
import { useAuth } from '../contexts/AuthContext';
import {
  useWorkflowQueue,
  useCreateWorkflowItem,
  useUpdateWorkflowItem,
  useUpdateWorkflowStatus,
  useDeleteWorkflowItem,
  useBulkUpdateWorkflowItems,
  useBulkDeleteWorkflowItems,
  useAssignWorkflowItem,
} from '../hooks/useWorkflowQueue';
import { adminUsersApi } from '../services/api';
import type { WorkflowItem, WorkflowStatus } from '../types/dashboard';
import type { WorkflowBoardShipment } from '../types/workflow';
import type { AdminUser, AdminUserCollection } from '../types/settings';
import useWorkflowStore, { type WorkflowState } from '../stores/workflowStore';

type PriorityTone = 'high' | 'medium' | 'low';
type StatusFilter = 'all' | WorkflowStatus;
type PriorityFilter = 'all' | PriorityTone;

type FilterPreset = {
  id: string;
  name: string;
  status: StatusFilter;
  priority: PriorityFilter;
  search: string;
};

const PRESET_STORAGE_KEY = 'todo_filter_presets';

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
  testing: {
    label: 'Testing',
    description: 'Validation in progress ahead of handover.',
    accent: 'border-blue-500',
  },
  awaiting_feedback: {
    label: 'Awaiting Feedback',
    description: 'Pending stakeholder review or input.',
    accent: 'border-purple-500',
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
    { label: 'Send to QA', target: 'testing', icon: 'fa-vial' },
    { label: 'Complete', target: 'completed', icon: 'fa-check' },
  ],
  in_progress: [
    { label: 'QA', target: 'testing', icon: 'fa-vial' },
    { label: 'Await Feedback', target: 'awaiting_feedback', icon: 'fa-comment-dots' },
    { label: 'Complete', target: 'completed', icon: 'fa-check' },
    { label: 'Delay', target: 'delayed', icon: 'fa-clock' },
  ],
  testing: [
    { label: 'Pass', target: 'completed', icon: 'fa-check' },
    { label: 'Needs Work', target: 'in_progress', icon: 'fa-rotate-left' },
    { label: 'Request Feedback', target: 'awaiting_feedback', icon: 'fa-comment-dots' },
  ],
  awaiting_feedback: [
    { label: 'Resume', target: 'in_progress', icon: 'fa-rotate-right' },
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

const getInitials = (name?: string | null): string => {
  if (!name) return '?';
  const parts = name.trim().split(/\s+/);
  if (parts.length === 1) {
    return parts[0].slice(0, 2).toUpperCase();
  }
  return `${parts[0][0] ?? ''}${parts[parts.length - 1][0] ?? ''}`.toUpperCase();
};

const getBoardTaskId = (task: WorkflowBoardShipment): string =>
  String(task.id ?? task.tracking_number ?? task.title ?? '');

const convertItemToBoardTask = (item: WorkflowItem): WorkflowBoardShipment => {
  const priority = typeof item.priority === 'string' ? item.priority : normalisePriority(item.priority);

  return {
    id: item.id,
    tracking_number: item.trackingNumber ?? item.tracking_number ?? null,
    service_level: item.serviceLevel ?? null,
    status: item.status,
    status_label: item.statusLabel ?? item.status_label ?? null,
    title: item.title,
    description: item.description,
    project: item.project ?? null,
    client: item.client ?? null,
    stage: item.stage ?? null,
    origin_branch: item.originBranch ?? item.origin_branch ?? null,
    destination_branch: item.destinationBranch ?? item.destination_branch ?? null,
    promised_at: item.promisedAt ?? item.promised_at ?? null,
    created_at: item.createdAt ?? item.created_at ?? null,
    priority,
    due_at: item.dueDate ?? item.due_at ?? null,
    tags: item.tags ?? [],
    assigned_user_id: item.assignedUserId ?? item.assigned_user_id ?? null,
    assigned_user_name: item.assignedTo ?? item.assigned_user_name ?? null,
    assigned_user_avatar: item.assignedUserAvatar ?? item.assigned_user_avatar ?? null,
    assigned_user_initials: item.assigned_user_initials ?? (item.assignedTo ? getInitials(item.assignedTo) : undefined),
    dependencies: item.dependencies ?? null,
    attachments: item.attachments ?? null,
    attachments_count: item.attachmentsCount ?? item.attachments_count ?? null,
    time_tracking: (item.timeTracking ?? item.time_tracking ?? null) as WorkflowBoardShipment['time_tracking'],
    watchers: item.watchers ?? null,
    comments_count: item.commentsCount ?? item.comments_count ?? null,
    activity_count: item.activityCount ?? item.activity_count ?? null,
    allowed_transitions: item.allowedTransitions ?? item.allowed_transitions ?? undefined,
    restricted_roles: item.restrictedRoles ?? item.restricted_roles ?? undefined,
    project_id: item.projectId ?? item.project_id ?? null,
    metadata: item.metadata ?? null,
  };
};

const TodoPage: React.FC = () => {
  const { user } = useAuth();
  const workflowQueueQuery = useWorkflowQueue();
  const workflowQueueData = workflowQueueQuery.data;
  const isLoading = workflowQueueQuery.isLoading;
  const isError = workflowQueueQuery.isError;
  const error = workflowQueueQuery.error;
  const refetch = workflowQueueQuery.refetch;
  const isFetching = workflowQueueQuery.isFetching;

  const createMutation = useCreateWorkflowItem();
  const updateMutation = useUpdateWorkflowItem();
  const updateStatusMutation = useUpdateWorkflowStatus();
  const deleteMutation = useDeleteWorkflowItem();
  const bulkUpdateMutation = useBulkUpdateWorkflowItems();
  const bulkDeleteMutation = useBulkDeleteWorkflowItems();
  const assignMutation = useAssignWorkflowItem();

  const { data: assignableUsersResponse } = useQuery<AdminUserCollection & { success?: boolean; message?: string }>({
    queryKey: ['workflow-board', 'assignable-users'],
    queryFn: async () => {
      return adminUsersApi.getUsers({ per_page: 50, status: 1 });
    },
    staleTime: 5 * 60 * 1000,
  });

  const assignableUsers = useMemo<AssignableUser[]>(() => {
    const records = assignableUsersResponse?.data ?? [];
    return records.map((record: AdminUser) => ({
      id: String(record.id),
      name: record.name ?? 'Unnamed teammate',
      avatar: record.avatar ?? null,
      initials: getInitials(record.name ?? ''),
    }));
  }, [assignableUsersResponse]);

  const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');
  const [priorityFilter, setPriorityFilter] = useState<PriorityFilter>('all');
  const [searchTerm, setSearchTerm] = useState('');
  const [filterPresets, setFilterPresets] = useState<FilterPreset[]>(() => {
    if (typeof window === 'undefined') return [];
    try {
      const stored = window.localStorage.getItem(PRESET_STORAGE_KEY);
      if (!stored) return [];
      const parsed = JSON.parse(stored) as FilterPreset[];
      return Array.isArray(parsed) ? parsed : [];
    } catch {
      return [];
    }
  });
  const [presetNameDraft, setPresetNameDraft] = useState('My View');
  const [selectedPresetId, setSelectedPresetId] = useState<string | null>(null);

  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [activeItem, setActiveItem] = useState<WorkflowItem | null>(null);
  const [selectedTaskIds, setSelectedTaskIds] = useState<Set<string>>(new Set());
  const [deniedMessage, setDeniedMessage] = useState<string | null>(null);
  const [isOffline, setIsOffline] = useState<boolean>(() =>
    typeof navigator !== 'undefined' ? !navigator.onLine : false,
  );

  useEffect(() => {
    const handleOnline = () => setIsOffline(false);
    const handleOffline = () => setIsOffline(true);
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, []);

  useEffect(() => {
    if (typeof window === 'undefined') return;
    try {
      window.localStorage.setItem(PRESET_STORAGE_KEY, JSON.stringify(filterPresets));
    } catch {
      // ignore write errors (private browsing / quota)
    }
  }, [filterPresets]);

  useEffect(() => {
    if (!deniedMessage) return;
    const timer = window.setTimeout(() => setDeniedMessage(null), 2600);
    return () => window.clearTimeout(timer);
  }, [deniedMessage]);

  const broadcastRef = useRef<BroadcastChannel | null>(null);
  useEffect(() => {
    if (typeof BroadcastChannel === 'undefined') return;
    const channel = new BroadcastChannel('workflow-sync');
    broadcastRef.current = channel;
    channel.onmessage = (event) => {
      if (event?.data?.type === 'workflow:refetch' && event?.data?.source !== 'todo') {
        refetch();
      }
    };
    return () => channel.close();
  }, [refetch]);

  const emitSync = useCallback(() => {
    broadcastRef.current?.postMessage({ type: 'workflow:refetch', source: 'todo' });
  }, []);

  const filterPresetLookup = useMemo(() =>
    new Map(filterPresets.map((preset) => [preset.id, preset])),
  [filterPresets]);

  const handleSavePreset = useCallback(() => {
    const trimmed = presetNameDraft.trim();
    if (!trimmed) return;
    const id = `${trimmed.toLowerCase().replace(/[^a-z0-9]+/g, '-')}-${Date.now()}`;
    const preset: FilterPreset = {
      id,
      name: trimmed,
      status: statusFilter,
      priority: priorityFilter,
      search: searchTerm,
    };
    setFilterPresets((prev) => [...prev, preset]);
    setSelectedPresetId(id);
  }, [presetNameDraft, priorityFilter, searchTerm, statusFilter]);

  const handleApplyPreset = useCallback((presetId: string) => {
    const preset = filterPresetLookup.get(presetId);
    if (!preset) return;
    setStatusFilter(preset.status);
    setPriorityFilter(preset.priority);
    setSearchTerm(preset.search);
    setSelectedPresetId(preset.id);
  }, [filterPresetLookup]);

  const handleDeletePreset = useCallback((presetId: string) => {
    setFilterPresets((prev) => prev.filter((preset) => preset.id !== presetId));
    if (selectedPresetId === presetId) {
      setSelectedPresetId(null);
    }
  }, [selectedPresetId]);

  const queueFromStore = useWorkflowStore((state: WorkflowState) => state.queue);
  const summaryFromStore = useWorkflowStore((state: WorkflowState) => state.summary);
  const storeIsSyncing = useWorkflowStore((state: WorkflowState) => state.isSyncing);

  const activeItems: WorkflowItem[] = queueFromStore.length
    ? queueFromStore
    : workflowQueueData?.tasks ?? [];

  const filteredItems = useMemo(() => {
    return activeItems.filter((item) => {
      const priority = normalisePriority(item.priority);
      const matchesStatus = statusFilter === 'all' || item.status === statusFilter;
      const matchesPriority = priorityFilter === 'all' || priority === priorityFilter;
      const matchesSearch = !searchTerm
        || item.title.toLowerCase().includes(searchTerm.toLowerCase())
        || item.description?.toLowerCase().includes(searchTerm.toLowerCase())
        || item.trackingNumber?.toLowerCase().includes(searchTerm.toLowerCase());

      return matchesStatus && matchesPriority && matchesSearch;
    });
  }, [activeItems, statusFilter, priorityFilter, searchTerm]);

  const itemLookup = useMemo(() => {
    const map = new Map<string, WorkflowItem>();
    activeItems.forEach((item) => {
      map.set(String(item.id), item);
    });
    return map;
  }, [activeItems]);

  const boardTasks = useMemo<WorkflowBoardShipment[]>(() => {
    return filteredItems.map((item) => convertItemToBoardTask(item));
  }, [filteredItems]);

  const summaryCounts = queueFromStore.length
    ? summaryFromStore
    : workflowQueueData?.summary ?? {
        total: activeItems.length,
        pending: activeItems.filter((item) => item.status === 'pending').length,
        in_progress: activeItems.filter((item) => item.status === 'in_progress').length,
        testing: activeItems.filter((item) => item.status === 'testing').length,
        awaiting_feedback: activeItems.filter((item) => item.status === 'awaiting_feedback').length,
        delayed: activeItems.filter((item) => item.status === 'delayed').length,
        completed: activeItems.filter((item) => item.status === 'completed').length,
      };

  const emitDenied = useCallback((task: WorkflowBoardShipment, from: WorkflowStatus, to: WorkflowStatus) => {
    const fromLabel = STATUS_METADATA[from]?.label ?? from;
    const toLabel = STATUS_METADATA[to]?.label ?? to;
    setDeniedMessage(`You do not have permission to move “${task.title ?? task.tracking_number ?? 'Task'}” from ${fromLabel} to ${toLabel}.`);
  }, []);

  const primaryRoleKey = useMemo(() => {
    if (user?.role?.key) return user.role.key;
    if (user?.roles?.length) {
      return user.roles[0].key ?? '';
    }
    return '';
  }, [user]);

  const permissionSet = useMemo(() => {
    const raw = Array.isArray(user?.permissions) ? user?.permissions : [];
    return new Set(raw.map((item) => String(item).toLowerCase()));
  }, [user?.permissions]);

  const projectIds = useMemo(() => (user?.project_ids ?? []).map((id) => String(id)), [user?.project_ids]);
  const isPrivileged = useMemo(() => ['super_admin', 'admin'].includes(primaryRoleKey), [primaryRoleKey]);
  const canManageWorkflow = useMemo(() => {
    if (isPrivileged) {
      return true;
    }

    const managementPermissions = [
      'workflow.manage',
      'workflow.update',
      'workflow.board.manage',
      'workflow.board',
      'todo_update',
    ];

    return managementPermissions.some((permission) => permissionSet.has(permission));
  }, [isPrivileged, permissionSet]);

  const canUpdateStatus = useCallback((task: WorkflowBoardShipment, from: WorkflowStatus, to: WorkflowStatus) => {
    if (canManageWorkflow) return true;

    const allowedTransitions = task.allowed_transitions;
    if (allowedTransitions) {
      const whitelist = allowedTransitions[from] ?? allowedTransitions.any;
      if (whitelist && !whitelist.includes(to)) {
        return false;
      }
    }

    const restrictedRoles = task.restricted_roles;
    if (restrictedRoles && restrictedRoles.length > 0 && primaryRoleKey) {
      if (!restrictedRoles.includes(primaryRoleKey)) {
        return false;
      }
    }

    const assignedId = task.assigned_user_id ? String(task.assigned_user_id) : null;
    if (assignedId && user?.id && String(user.id) === assignedId) {
      return true;
    }

    const projectId = task.project_id ? String(task.project_id) : null;
    if (projectId && projectIds.includes(projectId)) {
      return true;
    }

    if (!allowedTransitions && !restrictedRoles && !assignedId && !projectId) {
      return true;
    }

    return false;
  }, [canManageWorkflow, primaryRoleKey, projectIds, user?.id]);

  const handleStatusDenied = useCallback((task: WorkflowBoardShipment, from: WorkflowStatus, to: WorkflowStatus) => {
    emitDenied(task, from, to);
  }, [emitDenied]);

  const isMutating =
    createMutation.isPending ||
    updateMutation.isPending ||
    updateStatusMutation.isPending ||
    deleteMutation.isPending ||
    bulkUpdateMutation.isPending ||
    bulkDeleteMutation.isPending ||
    assignMutation.isPending;

  const buildTaskPayload = useCallback((formData: WorkflowFormData): Record<string, unknown> => {
    const payload: Record<string, unknown> = {
      title: formData.title,
      description: formData.description,
      priority: formData.priority,
      status: formData.status,
      tags: formData.tags ?? [],
    };

    if (formData.trackingNumber?.trim()) {
      payload.tracking_number = formData.trackingNumber.trim();
    }

    if (formData.dueDate) {
      const due = new Date(formData.dueDate);
      if (!Number.isNaN(due.getTime())) {
        payload.due_at = due.toISOString();
      }
    }

    if (formData.assignedTo?.trim()) {
      const candidate = Number(formData.assignedTo.trim());
      if (!Number.isNaN(candidate)) {
        payload.assigned_to = candidate;
      }
    }

    return payload;
  }, []);

  const handleCreateTask = useCallback(async (formData: WorkflowFormData) => {
    if (isOffline) {
      setDeniedMessage('You are offline. Reconnect to create new tasks.');
      return;
    }
    try {
      await createMutation.mutateAsync(buildTaskPayload(formData));
      setIsCreateModalOpen(false);
      emitSync();
    } catch (mutationError) {
      console.error('Failed to create workflow item:', mutationError);
    }
  }, [buildTaskPayload, createMutation, emitSync, isOffline]);

  const handleEditTask = useCallback((task: WorkflowBoardShipment) => {
    const source = itemLookup.get(getBoardTaskId(task));
    if (!source) {
      console.warn('Unable to locate workflow item for editing', task);
      return;
    }
    setActiveItem(source);
    setIsEditModalOpen(true);
  }, [itemLookup]);

  const handleUpdateTask = useCallback(async (id: string, formData: WorkflowFormData) => {
    if (isOffline) {
      setDeniedMessage('You are offline. Reconnect to update tasks.');
      return;
    }
    try {
      await updateMutation.mutateAsync({ id, data: buildTaskPayload(formData) });
      setIsEditModalOpen(false);
      setActiveItem(null);
      emitSync();
    } catch (mutationError) {
      console.error('Failed to update workflow item:', mutationError);
    }
  }, [buildTaskPayload, emitSync, isOffline, updateMutation]);

  const handleStatusChangeById = useCallback(async (taskId: string, nextStatus: WorkflowStatus) => {
    if (isOffline) {
      setDeniedMessage('You are offline. Reconnect to update task statuses.');
      return;
    }
    try {
      await updateStatusMutation.mutateAsync({ id: taskId, status: nextStatus });
      emitSync();
    } catch (mutationError) {
      console.error('Failed to update status:', mutationError);
    }
  }, [emitSync, isOffline, updateStatusMutation]);

  const handleAssignTask = useCallback(async (taskId: string, userId: string) => {
    if (isOffline) {
      setDeniedMessage('You are offline. Reconnect to reassign tasks.');
      return;
    }
    try {
      await assignMutation.mutateAsync({ id: taskId, assignedTo: userId });
      emitSync();
    } catch (assignError) {
      console.error('Failed to assign task:', assignError);
    }
  }, [assignMutation, emitSync, isOffline]);

  const handleDeleteTask = useCallback(async (taskId: string) => {
    if (isOffline) {
      setDeniedMessage('You are offline. Reconnect to modify tasks.');
      return;
    }
    try {
      await deleteMutation.mutateAsync(taskId);
      emitSync();
      setSelectedTaskIds((prev) => {
        if (!prev.has(taskId)) return prev;
        const next = new Set(prev);
        next.delete(taskId);
        return next;
      });
    } catch (mutationError) {
      console.error('Failed to delete workflow item:', mutationError);
    }
  }, [deleteMutation, emitSync, isOffline]);

  const toggleTaskSelection = useCallback((task: WorkflowBoardShipment) => {
    const taskId = getBoardTaskId(task);
    setSelectedTaskIds((prev) => {
      const next = new Set(prev);
      if (next.has(taskId)) {
        next.delete(taskId);
      } else {
        next.add(taskId);
      }
      return next;
    });
  }, []);

  const clearSelection = useCallback(() => {
    setSelectedTaskIds(new Set());
  }, []);

  const handleBulkStatusChange = useCallback(async (status: WorkflowStatus) => {
    if (!selectedTaskIds.size) return;
    if (isOffline) {
      setDeniedMessage('You are offline. Reconnect to update task statuses.');
      return;
    }
    try {
      await bulkUpdateMutation.mutateAsync({ ids: Array.from(selectedTaskIds), data: { status } });
      emitSync();
      clearSelection();
    } catch (error) {
      console.error('Failed to bulk update status:', error);
    }
  }, [bulkUpdateMutation, clearSelection, emitSync, isOffline, selectedTaskIds]);

  const handleBulkDelete = useCallback(async () => {
    if (!selectedTaskIds.size) return;
    if (!window.confirm(`Delete ${selectedTaskIds.size} selected task(s)?`)) return;
    if (isOffline) {
      setDeniedMessage('You are offline. Reconnect to modify tasks.');
      return;
    }
    try {
      await bulkDeleteMutation.mutateAsync(Array.from(selectedTaskIds));
      emitSync();
      clearSelection();
    } catch (error) {
      console.error('Failed to bulk delete tasks:', error);
    }
  }, [bulkDeleteMutation, clearSelection, emitSync, isOffline, selectedTaskIds]);

  const handleQuickStatus = useCallback((task: WorkflowBoardShipment, nextStatus: WorkflowStatus) => {
    const currentStatus = (task.status as WorkflowStatus) ?? 'pending';
    if (!canUpdateStatus(task, currentStatus, nextStatus)) {
      emitDenied(task, currentStatus, nextStatus);
      return;
    }
    handleStatusChangeById(getBoardTaskId(task), nextStatus);
  }, [canUpdateStatus, emitDenied, handleStatusChangeById]);

  const renderTaskActions = useCallback((task: WorkflowBoardShipment) => {
    const actions = STATUS_ACTIONS[(task.status as WorkflowStatus) ?? 'pending'] ?? [];
    return actions.map((action) => (
      <Button
        key={action.label}
        variant="ghost"
        size="sm"
        className="px-2 py-1 text-xs"
        onClick={() => handleQuickStatus(task, action.target)}
        disabled={isOffline || updateStatusMutation.isPending}
      >
        <i className={`fas ${action.icon} mr-1`} aria-hidden="true" />
        {action.label}
      </Button>
    ));
  }, [handleQuickStatus, isOffline, updateStatusMutation.isPending]);

  if (isLoading && !activeItems.length) {
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
          <Button
            variant="primary"
            size="md"
            onClick={() => setIsCreateModalOpen(true)}
            disabled={isMutating || isOffline}
          >
            <i className="fas fa-plus mr-2" aria-hidden="true" />
            New Task
          </Button>
        </div>
      </header>

      {isOffline && (
        <Card className="border border-amber-400 bg-amber-50 p-4 text-sm text-amber-700">
          <div className="flex items-center gap-2">
            <i className="fas fa-wifi-slash" aria-hidden="true" />
            <span>Offline mode — viewing cached tasks. Reconnect to sync changes.</span>
          </div>
        </Card>
      )}

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
          onChange={(event) => {
            setSearchTerm(event.target.value);
            setSelectedPresetId(null);
          }}
          className="md:col-span-2 xl:col-span-2"
        />
        <Select
          value={statusFilter}
          onChange={(event) => {
            setStatusFilter(event.target.value as StatusFilter);
            setSelectedPresetId(null);
          }}
          options={[
            { value: 'all', label: 'All statuses' },
            { value: 'pending', label: 'Pending' },
            { value: 'in_progress', label: 'In Progress' },
            { value: 'testing', label: 'Testing' },
            { value: 'awaiting_feedback', label: 'Awaiting Feedback' },
            { value: 'delayed', label: 'Delayed' },
            { value: 'completed', label: 'Completed' },
          ]}
        />
        <Select
          value={priorityFilter}
          onChange={(event) => {
            setPriorityFilter(event.target.value as PriorityFilter);
            setSelectedPresetId(null);
          }}
          options={[
            { value: 'all', label: 'All priorities' },
            { value: 'high', label: 'High' },
            { value: 'medium', label: 'Medium' },
            { value: 'low', label: 'Low' },
          ]}
        />
      </section>

      <section className="flex flex-col gap-3 rounded-3xl border border-mono-gray-200 bg-mono-gray-50 p-4">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex flex-wrap items-center gap-2">
            <Input
              value={presetNameDraft}
              onChange={(event) => setPresetNameDraft(event.target.value)}
              placeholder="Preset name"
              className="w-56"
            />
            <Button variant="secondary" size="sm" onClick={handleSavePreset}>
              <i className="fas fa-bookmark mr-2" aria-hidden="true" />
              Save preset
            </Button>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <Select
              value={selectedPresetId ?? ''}
              onChange={(event) => {
                const presetId = event.target.value;
                if (!presetId) {
                  setSelectedPresetId(null);
                  return;
                }
                handleApplyPreset(presetId);
              }}
              options={[{ value: '', label: 'Apply preset…' }, ...filterPresets.map((preset) => ({
                value: preset.id,
                label: preset.name,
              }))]}
              className="w-56"
            />
            {selectedPresetId && (
              <Button
                variant="ghost"
                size="sm"
                onClick={() => handleDeletePreset(selectedPresetId)}
              >
                <i className="fas fa-trash mr-2" aria-hidden="true" />
                Delete preset
              </Button>
            )}
          </div>
        </div>
        {filterPresets.length > 0 && (
          <div className="flex flex-wrap gap-2 text-xs text-mono-gray-500">
            <span className="uppercase tracking-[0.3em]">Presets:</span>
            {filterPresets.map((preset) => (
              <Button
                key={preset.id}
                variant={selectedPresetId === preset.id ? 'primary' : 'ghost'}
                size="sm"
                className="px-3 py-1 text-xs"
                onClick={() => handleApplyPreset(preset.id)}
              >
                {preset.name}
              </Button>
            ))}
          </div>
        )}
      </section>

      {deniedMessage && (
        <Card className="border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
          <div className="flex items-center gap-2">
            <i className="fas fa-lock" aria-hidden="true" />
            <span>{deniedMessage}</span>
          </div>
        </Card>
      )}

      <WorkflowKanbanBoard
        tasks={boardTasks}
        onEdit={handleEditTask}
        onStatusChange={handleStatusChangeById}
        onAssign={handleAssignTask}
        onDelete={handleDeleteTask}
        assignableUsers={assignableUsers}
        onCreate={!isOffline ? () => setIsCreateModalOpen(true) : undefined}
        isUpdating={isFetching || isMutating || storeIsSyncing}
        canUpdateStatus={canUpdateStatus}
        onStatusDenied={handleStatusDenied}
        selectedTaskIds={selectedTaskIds}
        onToggleTaskSelection={toggleTaskSelection}
        renderTaskActions={renderTaskActions}
        showOfflineOverlay={isOffline}
      />

      <BulkActionsBar
        selectedCount={selectedTaskIds.size}
        onUpdateStatus={handleBulkStatusChange}
        onDelete={handleBulkDelete}
        onClearSelection={clearSelection}
        isLoading={bulkUpdateMutation.isPending || bulkDeleteMutation.isPending}
      />

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
