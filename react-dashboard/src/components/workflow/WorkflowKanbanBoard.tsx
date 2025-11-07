/* eslint-disable react-refresh/only-export-components */
import React, { useState, useMemo, useEffect, useRef, useCallback } from 'react';
import Card from '../ui/Card';
import Badge from '../ui/Badge';
import Button from '../ui/Button';
import Avatar from '../ui/Avatar';
import type { WorkflowBoardShipment } from '../../types/workflow';
import type { WorkflowAttachment, WorkflowDependency, WorkflowStatus } from '../../types/dashboard';

export type KanbanColumnId = 'new' | 'in_progress' | 'testing' | 'awaiting_feedback' | 'completed';

type ExtendedWorkflowBoardShipment = WorkflowBoardShipment & {
  timeTracking?: {
    totalSeconds?: number;
    running?: boolean;
  };
};

export interface AssignableUser {
  id: string;
  name: string;
  avatar?: string | null;
  initials?: string;
}

interface WorkflowKanbanBoardProps {
  tasks: WorkflowBoardShipment[];
  onEdit: (task: WorkflowBoardShipment) => void;
  onStatusChange: (taskId: string, status: WorkflowStatus) => Promise<void>;
  onAssign: (taskId: string, userId: string) => Promise<void>;
  onDelete: (taskId: string) => Promise<void>;
  assignableUsers: AssignableUser[];
  onCreate?: () => void;
  isUpdating?: boolean;
  canUpdateStatus?: (task: WorkflowBoardShipment, from: WorkflowStatus, to: WorkflowStatus) => boolean;
  onStatusDenied?: (task: WorkflowBoardShipment, from: WorkflowStatus, to: WorkflowStatus) => void;
  selectedTaskIds?: Set<string>;
  onToggleTaskSelection?: (task: WorkflowBoardShipment) => void;
  renderTaskActions?: (task: WorkflowBoardShipment, context: { from: WorkflowStatus }) => React.ReactNode;
  showOfflineOverlay?: boolean;
}

interface BoardState {
  new: WorkflowBoardShipment[];
  in_progress: WorkflowBoardShipment[];
  testing: WorkflowBoardShipment[];
  awaiting_feedback: WorkflowBoardShipment[];
  completed: WorkflowBoardShipment[];
}

export const KANBAN_COLUMNS: Array<{
  id: KanbanColumnId;
  title: string;
  subtitle: string;
  indicatorClass: string;
  accentBorderClass: string;
  status: WorkflowStatus;
  emptyHint: string;
}> = [
  {
    id: 'new',
    title: 'New',
    subtitle: 'Fresh intake',
    indicatorClass: 'bg-sky-400',
    accentBorderClass: 'border-sky-200',
    status: 'pending',
    emptyHint: 'Incoming tasks will land here.',
  },
  {
    id: 'in_progress',
    title: 'In Progress',
    subtitle: 'Actively moving',
    indicatorClass: 'bg-indigo-500',
    accentBorderClass: 'border-indigo-200',
    status: 'in_progress',
    emptyHint: 'Drag a task here when work begins.',
  },
  {
    id: 'testing',
    title: 'Testing',
    subtitle: 'Validation underway',
    indicatorClass: 'bg-amber-400',
    accentBorderClass: 'border-amber-200',
    status: 'testing',
    emptyHint: 'Quality checks appear in this stage.',
  },
  {
    id: 'awaiting_feedback',
    title: 'Awaiting Feedback',
    subtitle: 'Stakeholder review',
    indicatorClass: 'bg-orange-400',
    accentBorderClass: 'border-orange-200',
    status: 'awaiting_feedback',
    emptyHint: 'Park work here while you wait on feedback.',
  },
  {
    id: 'completed',
    title: 'Completed',
    subtitle: 'Signed-off work',
    indicatorClass: 'bg-emerald-400',
    accentBorderClass: 'border-emerald-200',
    status: 'completed',
    emptyHint: 'Done items archive automatically after 7 days.',
  },
];

const columnDefaults = (): BoardState => ({
  new: [],
  in_progress: [],
  testing: [],
  awaiting_feedback: [],
  completed: [],
});

const statusToColumn: Record<string, KanbanColumnId> = {
  pending: 'new',
  new: 'new',
  in_progress: 'in_progress',
  testing: 'testing',
  qa: 'testing',
  awaiting_feedback: 'awaiting_feedback',
  review: 'awaiting_feedback',
  completed: 'completed',
  done: 'completed',
  delayed: 'awaiting_feedback',
};

export const columnIdToWorkflowStatus: Record<KanbanColumnId, WorkflowStatus> = {
  new: 'pending',
  in_progress: 'in_progress',
  testing: 'testing',
  awaiting_feedback: 'awaiting_feedback',
  completed: 'completed',
};

export const mapWorkflowStatusToColumn = (status?: string | null): KanbanColumnId => {
  if (!status) return 'new';
  const key = status.toLowerCase();
  return statusToColumn[key] ?? 'new';
};

const buildBoardState = (items: WorkflowBoardShipment[]): BoardState => {
  const next = columnDefaults();
  items.forEach((item) => {
    const column = mapWorkflowStatusToColumn(item.status);
    next[column].push(item);
  });
  return next;
};

const getTaskId = (task: WorkflowBoardShipment): string => {
  const identifier = task.id ?? task.tracking_number ?? task.title ?? '';
  return String(identifier);
};

const getInitials = (name?: string | null): string => {
  if (!name) return '?';
  const parts = name.trim().split(/\s+/);
  if (parts.length === 1) {
    return parts[0].slice(0, 2).toUpperCase();
  }
  return `${parts[0][0] ?? ''}${parts[parts.length - 1][0] ?? ''}`.toUpperCase();
};

const formatDate = (value?: string | null): string => {
  if (!value) return '—';
  try {
    return new Intl.DateTimeFormat(undefined, {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    }).format(new Date(value));
  } catch (error) {
    console.error('Failed to format date', error);
    return value;
  }
};

const formatTime = (value?: string | null): string => {
  if (!value) return '—';
  try {
    return new Intl.DateTimeFormat(undefined, {
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(value));
  } catch (error) {
    console.error('Failed to format time', error);
    return value;
  }
};

const formatDuration = (seconds?: number | null): string => {
  if (seconds == null) return '—';
  const total = Math.max(0, Math.floor(seconds));
  const hrs = Math.floor(total / 3600);
  const mins = Math.floor((total % 3600) / 60);
  if (hrs > 0) {
    return `${hrs}h ${mins.toString().padStart(2, '0')}m`;
  }
  return `${mins}m`;
};

const priorityStyle: Record<string, string> = {
  high: 'bg-rose-500/10 text-rose-600 border border-rose-200',
  medium: 'bg-amber-400/10 text-amber-600 border border-amber-200',
  low: 'bg-emerald-400/10 text-emerald-600 border border-emerald-200',
};

const dependencyPillStyle: Record<string, string> = {
  blocked: 'bg-rose-500/10 text-rose-600 border border-rose-200',
  at_risk: 'bg-amber-500/10 text-amber-600 border border-amber-200',
  complete: 'bg-emerald-500/10 text-emerald-600 border border-emerald-200',
};

type TimeTrackingRaw =
  | {
      total_seconds?: number;
      running?: boolean;
      started_at?: string | null;
      updated_at?: string | null;
    }
  | {
      totalSeconds?: number;
      running?: boolean;
      started_at?: string | null;
      updated_at?: string | null;
    };

const normaliseTimeTracking = (tracking: TimeTrackingRaw | null | undefined) => {
  if (!tracking) {
    return { totalSeconds: undefined as number | undefined, running: false };
  }

  if ('total_seconds' in tracking && typeof tracking.total_seconds === 'number') {
    return { totalSeconds: tracking.total_seconds, running: Boolean(tracking.running) };
  }

  if ('totalSeconds' in tracking && typeof tracking.totalSeconds === 'number') {
    return { totalSeconds: tracking.totalSeconds, running: Boolean(tracking.running) };
  }

  return { totalSeconds: undefined as number | undefined, running: Boolean((tracking as { running?: boolean }).running) };
};

const WorkflowKanbanBoard: React.FC<WorkflowKanbanBoardProps> = ({
  tasks,
  onEdit,
  onStatusChange,
  onAssign,
  onDelete,
  assignableUsers,
  onCreate,
  isUpdating = false,
  canUpdateStatus,
  onStatusDenied,
  selectedTaskIds,
  onToggleTaskSelection,
  renderTaskActions,
  showOfflineOverlay = false,
}) => {
  const [board, setBoard] = useState<BoardState>(() => buildBoardState(tasks));
  const [activeMenuTask, setActiveMenuTask] = useState<string | null>(null);
  const [hoverColumn, setHoverColumn] = useState<KanbanColumnId | null>(null);
  const [draggingTaskId, setDraggingTaskId] = useState<string | null>(null);
  const [pendingStatusIds, setPendingStatusIds] = useState<Set<string>>(new Set());
  const [pendingAssignIds, setPendingAssignIds] = useState<Set<string>>(new Set());
  const [deletingTaskId, setDeletingTaskId] = useState<string | null>(null);
  const tasksRef = useRef<WorkflowBoardShipment[]>(tasks);
  const boardSnapshotRef = useRef(board);
  const [statusDeniedTaskId, setStatusDeniedTaskId] = useState<string | null>(null);

  useEffect(() => {
    tasksRef.current = tasks;
    setBoard(buildBoardState(tasks));
  }, [tasks]);

  useEffect(() => {
    boardSnapshotRef.current = board;
  }, [board]);

  useEffect(() => {
    if (!statusDeniedTaskId) return;
    const timer = window.setTimeout(() => setStatusDeniedTaskId(null), 2400);
    return () => window.clearTimeout(timer);
  }, [statusDeniedTaskId]);

  const closeMenu = useCallback(() => setActiveMenuTask(null), []);

  const columnSummaries = useMemo(
    () =>
      KANBAN_COLUMNS.map(({ id, title, subtitle, indicatorClass }) => ({
        id,
        title,
        subtitle,
        indicatorClass,
        count: board[id].length,
      })),
    [board],
  );

  const handleDragStart = (task: WorkflowBoardShipment, columnId: KanbanColumnId) => (
    event: React.DragEvent<HTMLDivElement>,
  ) => {
    const taskId = getTaskId(task);
    if (!taskId) return;
    if (showOfflineOverlay) {
      event.preventDefault();
      return;
    }
    setDraggingTaskId(taskId);
    event.dataTransfer.setData(
      'application/json',
      JSON.stringify({ taskId, fromColumn: columnId }),
    );
    event.dataTransfer.effectAllowed = 'move';
  };

  const resetBoardFromSource = useCallback(() => {
    setBoard(buildBoardState(tasksRef.current));
  }, []);

  const updateBoardState = useCallback((updater: (draft: BoardState) => void) => {
    setBoard((prev) => {
      const draft: BoardState = {
        new: [...prev.new],
        in_progress: [...prev.in_progress],
        testing: [...prev.testing],
        awaiting_feedback: [...prev.awaiting_feedback],
        completed: [...prev.completed],
      };
      updater(draft);
      return draft;
    });
  }, []);

  const handleDrop = (targetColumn: KanbanColumnId) => (event: React.DragEvent<HTMLDivElement>) => {
    event.preventDefault();
    const dataRaw = event.dataTransfer.getData('application/json');
    setHoverColumn(null);
    setDraggingTaskId(null);

    if (!dataRaw) return;
    let payload: { taskId: string; fromColumn: KanbanColumnId };
    try {
      payload = JSON.parse(dataRaw);
    } catch {
      return;
    }
    if (!payload?.taskId || !payload?.fromColumn) return;
    if (payload.fromColumn === targetColumn) return;

    const { taskId, fromColumn } = payload;
    const fromStatus = columnIdToWorkflowStatus[fromColumn];
    const toStatus = columnIdToWorkflowStatus[targetColumn];
    const boardSnapshot = boardSnapshotRef.current;
    const sourceSnapshot = boardSnapshot[fromColumn] ?? [];
    const taskInColumn = sourceSnapshot.find((item) => getTaskId(item) === taskId) ?? null;

    if (!taskInColumn) {
      resetBoardFromSource();
      return;
    }

    if (showOfflineOverlay) {
      setStatusDeniedTaskId(taskId);
      onStatusDenied?.(taskInColumn, fromStatus, toStatus);
      return;
    }

    if (canUpdateStatus && !canUpdateStatus(taskInColumn, fromStatus, toStatus)) {
      setStatusDeniedTaskId(taskId);
      onStatusDenied?.(taskInColumn, fromStatus, toStatus);
      return;
    }

    setPendingStatusIds((prev) => new Set(prev).add(taskId));

    let movedTask: WorkflowBoardShipment | null = null;
    updateBoardState((draft) => {
      const sourceColumn = draft[fromColumn];
      const index = sourceColumn.findIndex((item) => getTaskId(item) === taskId);
      if (index === -1) {
        movedTask = null;
        return;
      }
      [movedTask] = sourceColumn.splice(index, 1);
      if (movedTask) {
        movedTask = { ...movedTask, status: toStatus };
        draft[targetColumn] = [movedTask, ...draft[targetColumn]];
      }
    });

    if (!movedTask) {
      setPendingStatusIds((prev) => {
        const next = new Set(prev);
        next.delete(taskId);
        return next;
      });
      return;
    }

    void onStatusChange(taskId, toStatus)
      .catch((error) => {
        console.error('Failed to update status', error);
        resetBoardFromSource();
      })
      .finally(() => {
        setPendingStatusIds((prev) => {
          const next = new Set(prev);
          next.delete(taskId);
          return next;
        });
      });
  };

  const handleAssign = (task: WorkflowBoardShipment, columnId: KanbanColumnId) => async (
    event: React.ChangeEvent<HTMLSelectElement>,
  ) => {
    const taskId = getTaskId(task);
    const userId = event.target.value;
    if (!taskId || !userId) return;

    const assignedUser = assignableUsers.find((user) => user.id === userId);
    setPendingAssignIds((prev) => new Set(prev).add(taskId));

    updateBoardState((draft) => {
      const column = draft[columnId];
      const index = column.findIndex((item) => getTaskId(item) === taskId);
      if (index !== -1) {
        column[index] = {
          ...column[index],
          assigned_user_id: assignedUser?.id ?? userId,
          assigned_user_name: assignedUser?.name ?? 'Assigned',
          assigned_user_avatar: assignedUser?.avatar ?? null,
          assigned_user_initials: assignedUser?.initials ?? getInitials(assignedUser?.name ?? userId),
        };
      }
    });

    try {
      await onAssign(taskId, userId);
    } catch (error) {
      console.error('Failed to assign task', error);
      resetBoardFromSource();
    } finally {
      setPendingAssignIds((prev) => {
        const next = new Set(prev);
        next.delete(taskId);
        return next;
      });
    }
  };

  const handleDelete = (task: WorkflowBoardShipment, columnId: KanbanColumnId) => {
    const taskId = getTaskId(task);
    if (!taskId) return;
    if (!window.confirm(`Delete “${task.title ?? task.tracking_number ?? 'this task'}”?`)) {
      return;
    }

    setDeletingTaskId(taskId);
    updateBoardState((draft) => {
      draft[columnId] = draft[columnId].filter((item) => getTaskId(item) !== taskId);
    });

    void onDelete(taskId)
      .catch((error) => {
        console.error('Failed to delete task', error);
        resetBoardFromSource();
      })
      .finally(() => setDeletingTaskId(null));
  };

  const isTaskBusy = (taskId: string) =>
    pendingStatusIds.has(taskId) || pendingAssignIds.has(taskId) || deletingTaskId === taskId;

  return (
    <div className="space-y-6">
      <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        {columnSummaries.map((column) => (
          <Card
            key={column.id}
            className="border border-mono-gray-200 p-5 shadow-sm transition-transform duration-200 hover:-translate-y-1"
          >
            <div className="flex items-start justify-between">
              <div>
                <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">{column.subtitle}</p>
                <h2 className="mt-2 text-3xl font-semibold text-mono-black">{column.count}</h2>
                <p className="text-sm text-mono-gray-500">Tasks</p>
              </div>
              <span className={`h-2 w-2 rounded-full ${column.indicatorClass}`} aria-hidden="true" />
            </div>
          </Card>
        ))}
      </section>

      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold text-mono-black">Kanban Board</h2>
        {onCreate && (
          <Button variant="primary" size="sm" onClick={onCreate}>
            <i className="fas fa-plus mr-2" aria-hidden="true" />
            New Task
          </Button>
        )}
      </div>

      <div className="relative">
        {isUpdating && (
          <div className="absolute inset-0 z-10 rounded-3xl border border-dashed border-mono-gray-300 bg-white/70 backdrop-blur-sm flex items-center justify-center text-sm font-medium text-mono-gray-600">
            Syncing latest updates…
          </div>
        )}
        {showOfflineOverlay && (
          <div className="absolute inset-0 z-20 rounded-3xl border border-dashed border-amber-400 bg-white/80 backdrop-blur-sm flex flex-col items-center justify-center gap-2 text-sm font-medium text-mono-gray-700">
            <i className="fas fa-wifi-slash text-amber-500" aria-hidden="true" />
            <span>You are offline — drag and assignment actions are disabled until reconnection.</span>
          </div>
        )}
        <div className="flex gap-6 overflow-x-auto pb-4">
          {KANBAN_COLUMNS.map((column) => {
            const tasksInColumn = board[column.id];
            const isHovering = hoverColumn === column.id;
            return (
              <div
                key={column.id}
                className={`relative flex w-[300px] flex-shrink-0 flex-col gap-4 rounded-3xl border bg-mono-white p-4 shadow-sm transition-all ${
                  column.accentBorderClass
                } ${isHovering ? 'ring-2 ring-offset-2 ring-offset-white ring-mono-black/40' : ''}`}
                onDragOver={(event) => {
                  event.preventDefault();
                  setHoverColumn(column.id);
                }}
                onDragLeave={() => setHoverColumn(null)}
                onDrop={handleDrop(column.id)}
                role="region"
                aria-label={`${column.title} column`}
              >
                <header className="flex items-center justify-between">
                  <div>
                    <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">{column.title}</p>
                    <p className="text-sm text-mono-gray-500">{column.subtitle}</p>
                  </div>
                  <Badge variant="outline" size="sm">
                    {tasksInColumn.length}
                  </Badge>
                </header>

                <div className="flex flex-1 flex-col gap-4">
                  {tasksInColumn.length === 0 ? (
                    <div className="flex flex-1 items-center justify-center rounded-2xl border border-dashed border-mono-gray-300 bg-mono-gray-50 p-6 text-center text-xs text-mono-gray-500">
                      {column.emptyHint}
                    </div>
                  ) : (
                    tasksInColumn.map((task) => {
                      const taskId = getTaskId(task);
                      const priority = (task.priority || '').toString().toLowerCase();
                      const assignedName = task.assigned_user_name ?? '';
                      const initials = task.assigned_user_initials ?? getInitials(assignedName);
                      const selectId = `assign-${column.id}-${taskId}`;
                      const isBusy = isTaskBusy(taskId);
                      const isSelected = selectedTaskIds?.has(taskId) ?? false;
                      const highlightClass =
                        statusDeniedTaskId === taskId
                          ? 'ring-2 ring-rose-400'
                          : isSelected
                            ? 'ring-2 ring-offset-2 ring-offset-white ring-mono-black/80'
                            : '';
                      const dependencies = (task.dependencies ?? []) as WorkflowDependency[];
                      const extendedTask = task as ExtendedWorkflowBoardShipment;
                      const timeTrackingRaw = extendedTask.time_tracking ?? extendedTask.timeTracking ?? null;
                      const { totalSeconds, running: timerRunning } = normaliseTimeTracking(timeTrackingRaw);
                      const attachments = (task.attachments ?? null) as WorkflowAttachment[] | null;
                      const attachmentsCount = attachments ? attachments.length : task.attachments_count ?? 0;
                      const activityCount = task.activity_count ?? task.comments_count ?? 0;

                      return (
                        <div
                          key={taskId}
                          className={`group relative ${showOfflineOverlay ? 'cursor-default' : 'cursor-grab'} rounded-2xl border border-mono-gray-200 bg-white p-4 shadow-sm transition-shadow duration-200 hover:shadow-lg ${
                            draggingTaskId === taskId ? 'opacity-50' : ''
                          } ${highlightClass}`}
                          draggable={!showOfflineOverlay}
                          onDragStart={handleDragStart(task, column.id)}
                          onDragEnd={() => {
                            setHoverColumn(null);
                            setDraggingTaskId(null);
                          }}
                        >
                          <div className="absolute inset-0 rounded-2xl bg-mono-black/5 opacity-0 transition-opacity duration-200 group-hover:opacity-100 pointer-events-none" />
                          <div className="relative z-10 flex items-start justify-between gap-3">
                            <div className="flex items-start gap-2">
                              {onToggleTaskSelection && (
                                <input
                                  type="checkbox"
                                  className="mt-1 h-4 w-4 cursor-pointer rounded border-mono-gray-300 text-mono-black focus:ring-mono-black"
                                  checked={isSelected}
                                  onChange={() => onToggleTaskSelection(task)}
                                  aria-label={`Select task ${task.title ?? task.tracking_number ?? taskId}`}
                                />
                              )}
                              <div>
                                <h3 className="text-sm font-semibold text-mono-black">
                                  {task.title ?? task.tracking_number ?? 'Untitled task'}
                                </h3>
                                <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">
                                  {task.project ?? task.service_level ?? 'Uncategorised'}
                                </p>
                              </div>
                            </div>
                            <div className="relative flex items-center gap-2">
                              {priority && (
                                <Badge
                                  variant="ghost"
                                  size="sm"
                                  className={priorityStyle[priority] ?? 'border border-mono-gray-200 text-mono-gray-600'}
                                >
                                  {priority.toUpperCase()}
                                </Badge>
                              )}
                              <button
                                type="button"
                                className="rounded-full p-2 text-mono-gray-400 hover:bg-mono-gray-100 hover:text-mono-black focus:outline-none focus:ring-2 focus:ring-mono-black"
                                onClick={(event) => {
                                  event.stopPropagation();
                                  setActiveMenuTask((prev) => (prev === taskId ? null : taskId));
                                }}
                                aria-haspopup="menu"
                                aria-expanded={activeMenuTask === taskId}
                              >
                                <i className="fas fa-ellipsis-vertical" aria-hidden="true" />
                              </button>
                              {activeMenuTask === taskId && (
                                <div className="absolute right-0 top-10 z-20 w-48 rounded-xl border border-mono-gray-200 bg-white py-1 shadow-xl">
                                  <button
                                    type="button"
                                    className="flex w-full items-center justify-between px-4 py-2 text-sm text-mono-gray-700 hover:bg-mono-gray-50"
                                    onClick={() => {
                                      closeMenu();
                                      onEdit(task);
                                    }}
                                  >
                                    Edit Task
                                    <i className="fas fa-pen" aria-hidden="true" />
                                  </button>
                                  <button
                                    type="button"
                                    className="flex w-full items-center justify-between px-4 py-2 text-sm text-mono-gray-700 hover:bg-mono-gray-50"
                                    onClick={() => {
                                      closeMenu();
                                      handleDelete(task, column.id);
                                    }}
                                  >
                                    Delete
                                    <i className="fas fa-trash" aria-hidden="true" />
                                  </button>
                                  <button
                                    type="button"
                                    className="flex w-full items-center justify-between px-4 py-2 text-sm text-mono-gray-400"
                                    disabled
                                  >
                                    Clone Task
                                  </button>
                                  <button
                                    type="button"
                                    className="flex w-full items-center justify-between px-4 py-2 text-sm text-mono-gray-400"
                                    disabled
                                  >
                                    Record Time
                                  </button>
                                  <button
                                    type="button"
                                    className="flex w-full items-center justify-between px-4 py-2 text-sm text-mono-gray-400"
                                    disabled
                                  >
                                    Stop Timers
                                  </button>
                                  <button
                                    type="button"
                                    className="flex w-full items-center justify-between px-4 py-2 text-sm text-mono-gray-400"
                                    disabled
                                  >
                                    Recurring Settings
                                  </button>
                                  <button
                                    type="button"
                                    className="flex w-full items-center justify-between px-4 py-2 text-sm text-mono-gray-400"
                                    disabled
                                  >
                                    Archive
                                  </button>
                                </div>
                              )}
                            </div>
                          </div>

                          {renderTaskActions && (
                            <div className="relative z-10 mt-3 flex flex-wrap gap-2">
                              {renderTaskActions(task, { from: column.status })}
                            </div>
                          )}

                          <div className="relative z-10 mt-3 flex flex-wrap gap-2">
                            {dependencies.slice(0, 3).map((dependency) => {
                              const tone = dependencyPillStyle[dependency.status ?? 'blocked'] ??
                                'border border-mono-gray-300 text-mono-gray-600';
                              return (
                                <Badge key={dependency.id} variant="ghost" size="sm" className={tone}>
                                  {dependency.title ?? 'Dependency'}
                                </Badge>
                              );
                            })}
                            {task.stage && (
                              <Badge variant="outline" size="sm" className="border-mono-gray-200 text-mono-gray-600">
                                {task.stage}
                              </Badge>
                            )}
                            {task.status_label && task.status_label !== task.stage && (
                              <Badge variant="outline" size="sm" className="border-mono-gray-200 text-mono-gray-600">
                                {task.status_label}
                              </Badge>
                            )}
                          </div>

                          <p className="relative z-10 mt-3 text-sm text-mono-gray-600">
                            {task.description ??
                              task.client ??
                              `${task.origin_branch ?? 'Origin TBD'} → ${task.destination_branch ?? 'Destination TBD'}`}
                          </p>

                          <div className="relative z-10 mt-4 flex flex-wrap items-center gap-3 text-xs text-mono-gray-500">
                            <span className="inline-flex items-center gap-1">
                              <i className="fas fa-calendar" aria-hidden="true" />
                              {formatDate(task.due_at ?? task.promised_at)}
                            </span>
                            <span className="inline-flex items-center gap-1">
                              <i className="fas fa-clock" aria-hidden="true" />
                              {formatTime(task.promised_at)}
                            </span>
                            {totalSeconds != null && (
                              <span className="inline-flex items-center gap-1 rounded-full bg-mono-gray-100 px-2 py-0.5">
                                <i className={`fas ${timerRunning ? 'fa-stopwatch' : 'fa-hourglass-half'}`} aria-hidden="true" />
                                {formatDuration(totalSeconds)}
                                {timerRunning && <span className="ml-1 inline-block h-2 w-2 animate-pulse rounded-full bg-emerald-500" />}
                              </span>
                            )}
                            {attachmentsCount > 0 && (
                              <span className="inline-flex items-center gap-1">
                                <i className="fas fa-paperclip" aria-hidden="true" />
                                {attachmentsCount}
                              </span>
                            )}
                            {activityCount > 0 && (
                              <span className="inline-flex items-center gap-1">
                                <i className="fas fa-list" aria-hidden="true" />
                                {activityCount}
                              </span>
                            )}
                            {task.tags?.slice(0, 2).map((tag) => (
                              <Badge key={tag} variant="ghost" size="sm" className="bg-mono-gray-100 text-mono-gray-600">
                                #{tag}
                              </Badge>
                            ))}
                          </div>

                          <div className="relative z-10 mt-4 flex items-center justify-between gap-3">
                            <div className="flex -space-x-2">
                              {task.watchers?.slice(0, 3).map((watcher) => (
                                <Avatar
                                  key={watcher.id}
                                  src={watcher.avatar ?? undefined}
                                  fallback={getInitials(watcher.name)}
                                  size="sm"
                                  className="border border-white shadow-sm"
                                />
                              ))}
                              {assignedName && (
                                <Avatar
                                  src={task.assigned_user_avatar ?? undefined}
                                  fallback={initials}
                                  size="sm"
                                  className="border border-white shadow-sm"
                                />
                              )}
                              {!assignedName && (
                                <div className="flex h-8 w-8 items-center justify-center rounded-full border border-dashed border-mono-gray-300 text-xs text-mono-gray-400">
                                  —
                                </div>
                              )}
                            </div>

                            <div className="flex items-center gap-2">
                              <select
                                id={selectId}
                                className="rounded-lg border border-mono-gray-200 px-2 py-1 text-xs text-mono-gray-600 focus:border-mono-black focus:outline-none focus:ring-2 focus:ring-mono-black"
                                onChange={handleAssign(task, column.id)}
                                value={task.assigned_user_id ? String(task.assigned_user_id) : ''}
                                disabled={isBusy || showOfflineOverlay}
                              >
                                <option value="">Assign…</option>
                                {assignableUsers.map((user) => (
                                  <option key={user.id} value={user.id}>
                                    {user.name}
                                  </option>
                                ))}
                              </select>
                              {isBusy && (
                                <i className="fas fa-spinner fa-spin text-mono-gray-400" aria-hidden="true" />
                              )}
                            </div>
                          </div>
                        </div>
                      );
                    })
                  )}
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};

export default WorkflowKanbanBoard;
