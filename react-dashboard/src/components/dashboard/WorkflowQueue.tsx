import React from 'react';
import Card from '../ui/Card';
import Badge from '../ui/Badge';
import type { WorkflowItem } from '../../types/dashboard';

/**
 * Workflow Queue Placeholder Component
 * Displays workflow items with status indicators
 */
interface WorkflowQueueProps {
  /** Workflow items to display */
  items: WorkflowItem[];
  /** Loading state */
  loading?: boolean;
  /** Click handler for workflow items */
  onItemClick?: (item: WorkflowItem) => void;
  /** Maximum items to display */
  maxItems?: number;
}

const WorkflowQueue: React.FC<WorkflowQueueProps> = ({
  items,
  loading = false,
  onItemClick,
  maxItems = 5,
}) => {
  // Status badge styling
  const getStatusBadge = (status: WorkflowItem['status']) => {
    const statusConfig = {
      pending: {
        label: 'Pending',
        variant: 'outline' as const,
        icon: 'fas fa-clock',
      },
      in_progress: {
        label: 'In Progress',
        variant: 'solid' as const,
        icon: 'fas fa-spinner',
      },
      completed: {
        label: 'Completed',
        variant: 'solid' as const,
        icon: 'fas fa-check-circle',
      },
      delayed: {
        label: 'Delayed',
        variant: 'solid' as const,
        icon: 'fas fa-exclamation-triangle',
      },
    };

    const config = statusConfig[status];
    return (
      <Badge variant={config.variant} size="sm">
        <i className={`${config.icon} mr-1`} aria-hidden="true" />
        {config.label}
      </Badge>
    );
  };

  // Priority indicator
  const getPriorityIndicator = (priority?: number) => {
    if (!priority || priority <= 2) return null;
    
    return (
      <span
        className="flex items-center text-xs text-mono-gray-600"
        title={`Priority: ${priority}/5`}
      >
        <i className="fas fa-flag text-mono-black mr-1" aria-hidden="true" />
        High
      </span>
    );
  };

  const displayItems = items.slice(0, maxItems);

  return (
    <Card
      header={
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-semibold text-mono-black">
            Today's Queue
          </h3>
          <Badge variant="solid" size="sm">
            {items.length} items
          </Badge>
        </div>
      }
    >
      {loading ? (
        // Loading skeleton
        <div className="space-y-4" aria-live="polite" aria-busy="true">
          {[1, 2, 3].map((i) => (
            <div key={i} className="animate-pulse flex items-start gap-4">
              <div className="w-10 h-10 bg-mono-gray-200 rounded-full" />
              <div className="flex-1 space-y-2">
                <div className="h-4 bg-mono-gray-200 rounded w-3/4" />
                <div className="h-3 bg-mono-gray-200 rounded w-1/2" />
              </div>
            </div>
          ))}
          <span className="sr-only">Loading workflow queue</span>
        </div>
      ) : displayItems.length === 0 ? (
        // Empty state
        <div
          className="flex flex-col items-center justify-center py-12 text-center"
          role="status"
        >
          <i
            className="fas fa-check-circle text-5xl text-mono-gray-300 mb-4"
            aria-hidden="true"
          />
          <p className="text-mono-gray-500 font-medium mb-1">
            All caught up!
          </p>
          <p className="text-sm text-mono-gray-400">
            No pending workflow items
          </p>
        </div>
      ) : (
        // Workflow items list
        <div className="space-y-3" role="list" aria-label="Workflow queue items">
          {displayItems.map((item) => (
            <div
              key={item.id}
              className={`
                flex items-start gap-4 p-4 rounded-lg border border-mono-gray-200
                bg-mono-white hover:bg-mono-gray-50 transition-colors
                ${onItemClick || item.actionUrl ? 'cursor-pointer' : ''}
              `}
              onClick={() => {
                if (onItemClick) {
                  onItemClick(item);
                } else if (item.actionUrl) {
                  window.location.href = item.actionUrl;
                }
              }}
              role="listitem"
              tabIndex={onItemClick || item.actionUrl ? 0 : undefined}
              onKeyDown={(e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                  e.preventDefault();
                  if (onItemClick) {
                    onItemClick(item);
                  } else if (item.actionUrl) {
                    window.location.href = item.actionUrl;
                  }
                }
              }}
            >
              {/* Status Icon */}
              <div
                className="flex-shrink-0 w-10 h-10 rounded-full bg-mono-gray-100 flex items-center justify-center"
                aria-hidden="true"
              >
                {item.status === 'completed' ? (
                  <i className="fas fa-check text-mono-black" />
                ) : item.status === 'in_progress' ? (
                  <i className="fas fa-spinner text-mono-gray-700" />
                ) : item.status === 'delayed' ? (
                  <i className="fas fa-exclamation text-mono-gray-700" />
                ) : (
                  <i className="fas fa-clock text-mono-gray-600" />
                )}
              </div>

              {/* Content */}
              <div className="flex-1 min-w-0">
                <div className="flex items-start justify-between gap-2 mb-1">
                  <h4 className="text-sm font-medium text-mono-black truncate">
                    {item.title}
                  </h4>
                  {getPriorityIndicator(item.priority)}
                </div>
                
                {item.description && (
                  <p className="text-xs text-mono-gray-600 mb-2 line-clamp-2">
                    {item.description}
                  </p>
                )}

                <div className="flex items-center gap-3 text-xs text-mono-gray-500">
                  {getStatusBadge(item.status)}
                  
                  {item.assignedTo && (
                    <span className="flex items-center">
                      <i className="fas fa-user mr-1" aria-hidden="true" />
                      {item.assignedTo}
                    </span>
                  )}
                  
                  {item.dueDate && (
                    <span className="flex items-center">
                      <i className="fas fa-calendar mr-1" aria-hidden="true" />
                      {item.dueDate}
                    </span>
                  )}
                </div>
              </div>

              {/* Action indicator */}
              {(onItemClick || item.actionUrl) && (
                <div className="flex-shrink-0 text-mono-gray-400" aria-hidden="true">
                  <i className="fas fa-chevron-right text-sm" />
                </div>
              )}
            </div>
          ))}

          {/* View more link */}
          {items.length > maxItems && (
            <button
              className="w-full text-center py-2 text-sm font-medium text-mono-gray-700 hover:text-mono-black transition-colors"
              onClick={(e) => {
                e.stopPropagation();
                console.log('View all workflow items');
              }}
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