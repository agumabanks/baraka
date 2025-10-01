import React, { useEffect, useMemo, useState } from 'react';
import Card from '../ui/Card';
import Badge from '../ui/Badge';
import { SkeletonWorkflowItem } from './SkeletonCard';
import type { WorkflowItem } from '../../types/dashboard';
import { getUserPermissions, hasPermission } from '../../lib/rbac';

type WorkflowActionType = 'assign' | 'reschedule' | 'contact';

interface WorkflowQueueProps {
  /** Workflow items to display */
  items: WorkflowItem[];
  /** Loading state */
  loading?: boolean;
  /** Click handler for workflow items */
  onItemClick?: (item: WorkflowItem) => void;
  /** Maximum items to display */
  maxItems?: number;
  /** Callback when a quick action button is pressed */
  onAction?: (payload: { type: WorkflowActionType; item: WorkflowItem }) => void;
}

const filterOptions: { key: 'all' | 'high' | 'medium' | 'low'; label: string }[] = [
  { key: 'all', label: 'All' },
  { key: 'high', label: 'High' },
  { key: 'medium', label: 'Medium' },
  { key: 'low', label: 'Low' },
];

const statusMetadata = {
  pending: { label: 'Pending', icon: 'fas fa-clock', variant: 'outline' as const },
  in_progress: { label: 'In Progress', icon: 'fas fa-spinner', variant: 'solid' as const },
  completed: { label: 'Completed', icon: 'fas fa-check-circle', variant: 'solid' as const },
  delayed: { label: 'Delayed', icon: 'fas fa-exclamation-triangle', variant: 'solid' as const },
};

const actionDefinitions: { type: WorkflowActionType; label: string; permission: string }[] = [
  { type: 'assign', label: 'Assign', permission: 'assign_tasks' },
  { type: 'reschedule', label: 'Reschedule', permission: 'reschedule_tasks' },
  { type: 'contact', label: 'Contact', permission: 'contact_customers' },
];

const processingLabel = 'Processing...';

const normalisePriority = (priority?: WorkflowItem['priority']): 'high' | 'medium' | 'low' => {
  if (typeof priority === 'string') {
    if (priority === 'high' || priority === 'medium' || priority === 'low') {
      return priority;
    }
  }

  if (typeof priority === 'number') {
    if (priority >= 4) return 'high';
    if (priority >= 2) return 'medium';
  }

  return 'low';
};

const WorkflowQueue: React.FC<WorkflowQueueProps> = ({
  items,
  loading = false,
  onItemClick,
  maxItems = 5,
  onAction,
}) => {
  const [activeFilter, setActiveFilter] = useState<'all' | 'high' | 'medium' | 'low'>('all');
  const [pendingAction, setPendingAction] = useState<Record<string, boolean>>({});
  const [statusMessage, setStatusMessage] = useState('');
  const [showAll, setShowAll] = useState(false);
  const userPermissions = getUserPermissions();
  const allowAllActions = userPermissions.permissions.length === 0;

  const filteredItems = useMemo(() => {
    const filtered = activeFilter === 'all'
      ? items
      : items.filter((item) => normalisePriority(item.priority) === activeFilter);

    if (showAll) {
      return filtered;
    }

    return filtered.slice(0, maxItems);
  }, [items, activeFilter, maxItems, showAll]);

  useEffect(() => {
    const label = filterOptions.find((option) => option.key === activeFilter)?.label ?? 'All';
    setStatusMessage(`Showing ${filteredItems.length} ${label.toLowerCase()} priority items`);
  }, [activeFilter, filteredItems.length]);

  useEffect(() => {
    setShowAll(false);
  }, [activeFilter, items]);

  const handleAction = (type: WorkflowActionType, item: WorkflowItem) => {
    const actionKey = `${item.id}-${type}`;
    setPendingAction((prev) => ({ ...prev, [actionKey]: true }));
    setStatusMessage(`${processingLabel} ${item.title}`);

    setTimeout(() => {
      setPendingAction((prev) => ({ ...prev, [actionKey]: false }));
      setStatusMessage(`${type.charAt(0).toUpperCase() + type.slice(1)} completed for ${item.title}`);
      onAction?.({ type, item });
    }, 900);
  };

  return (
    <Card
      header={
        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h3 className="text-lg font-semibold text-mono-black">Today's Workflow</h3>
            <p className="text-xs text-mono-gray-500">Live operations queue, refreshed automatically</p>
          </div>
          <div className="flex flex-wrap items-center gap-2" role="group" aria-label="Filter workflow items by priority">
            {filterOptions.map((option) => {
              const isActive = activeFilter === option.key;
              return (
                <button
                  key={option.key}
                  type="button"
                  onClick={() => setActiveFilter(option.key)}
                  className={`px-3 py-1.5 rounded-full border text-sm font-medium transition-colors ${
                    isActive
                      ? 'bg-mono-black text-mono-white border-mono-black'
                      : 'bg-mono-white text-mono-gray-700 border-mono-gray-300 hover:border-mono-black'
                  }`}
                  aria-pressed={isActive}
                >
                  {option.label}
                </button>
              );
            })}
          </div>
        </div>
      }
    >
      <div className="sr-only" role="status" aria-live="polite">
        {statusMessage}
      </div>

      {loading ? (
        <div className="space-y-3" aria-live="polite" aria-busy="true">
          {[1, 2, 3].map((item) => (
            <SkeletonWorkflowItem key={item} />
          ))}
        </div>
      ) : filteredItems.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-12 text-center">
          <div className="w-16 h-16 rounded-full border border-dashed border-mono-gray-300 flex items-center justify-center mb-4">
            <span role="img" aria-label="Calendar" className="text-2xl">
              🗓️
            </span>
          </div>
          <p className="text-mono-gray-600 font-medium mb-1">No tasks match this filter</p>
          <p className="text-xs text-mono-gray-500">Try selecting a different priority to discover more work items.</p>
        </div>
      ) : (
        <div className="space-y-3" role="list" aria-label="Workflow queue items">
          {filteredItems.map((item) => {
            const priority = normalisePriority(item.priority);
            const status = statusMetadata[item.status ?? 'pending'];

            return (
              <div
                key={item.id}
                className="flex flex-col gap-4 border border-mono-gray-200 rounded-xl p-4 bg-mono-white transition-colors hover:border-mono-black"
                role="listitem"
              >
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div className="flex items-start gap-3">
                    <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-mono-gray-100 text-mono-gray-700">
                      {item.status === 'completed' ? (
                        <i className="fas fa-check" aria-hidden="true" />
                      ) : item.status === 'delayed' ? (
                        <i className="fas fa-exclamation" aria-hidden="true" />
                      ) : item.status === 'in_progress' ? (
                        <i className="fas fa-spinner" aria-hidden="true" />
                      ) : (
                        <i className="fas fa-clock" aria-hidden="true" />
                      )}
                    </span>
                    <div className="space-y-1">
                      <div className="flex items-start gap-2">
                        <h4 className="text-sm font-semibold text-mono-black">
                          {item.title}
                        </h4>
                        <span className="inline-flex items-center rounded-full border border-mono-gray-300 px-2 py-0.5 text-[11px] uppercase tracking-wide text-mono-gray-600">
                          {priority} priority
                        </span>
                      </div>
                      {item.description && (
                        <p className="text-xs text-mono-gray-600 leading-relaxed">
                          {item.description}
                        </p>
                      )}
                      <div className="flex flex-wrap items-center gap-3 text-xs text-mono-gray-500">
                        <Badge variant={status.variant} size="sm">
                          <i className={`${status.icon} mr-1`} aria-hidden="true" />
                          {status.label}
                        </Badge>
                        {item.assignedTo && (
                          <span className="flex items-center gap-1">
                            <i className="fas fa-user" aria-hidden="true" />
                            {item.assignedTo}
                          </span>
                        )}
                        {item.dueDate && (
                          <span className="flex items-center gap-1">
                            <i className="fas fa-calendar" aria-hidden="true" />
                            {item.dueDate}
                          </span>
                        )}
                      </div>
                    </div>
                  </div>

                  {(onItemClick || item.actionUrl) && (
                    <button
                      type="button"
                      onClick={() => {
                        if (onItemClick) {
                          onItemClick(item);
                        } else if (item.actionUrl) {
                          window.location.href = item.actionUrl;
                        }
                      }}
                      className="inline-flex items-center gap-1 text-xs font-medium text-mono-gray-600 hover:text-mono-black"
                    >
                      Open details
                      <i className="fas fa-chevron-right text-[10px]" aria-hidden="true" />
                    </button>
                  )}
                </div>

                <div className="flex flex-wrap items-center gap-2">
                  {actionDefinitions
                    .filter((action) => hasPermission(action.permission) || allowAllActions)
                    .map((action) => {
                      const actionKey = `${item.id}-${action.type}`;
                      const isBusy = pendingAction[actionKey];
                      return (
                        <button
                          key={action.type}
                          type="button"
                          onClick={() => handleAction(action.type, item)}
                          disabled={isBusy}
                          className="px-3 py-1.5 text-xs font-semibold border border-mono-gray-300 rounded-full text-mono-gray-700 hover:border-mono-black hover:text-mono-black disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          {isBusy ? processingLabel : action.label}
                        </button>
                      );
                    })}
                </div>
              </div>
            );
          })}

          {!showAll && items.length > filteredItems.length && (
            <button
              type="button"
              className="w-full text-center py-2 text-sm font-medium text-mono-gray-700 hover:text-mono-black"
              onClick={() => setShowAll(true)}
            >
              View all {items.length} items
              <i className="fas fa-arrow-right ml-2" aria-hidden="true" />
            </button>
          )}
        </div>
      )}
    </Card>
  );
};

export default WorkflowQueue;
