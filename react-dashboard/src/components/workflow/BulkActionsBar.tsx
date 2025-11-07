import React from 'react';
import Badge from '../ui/Badge';
import type { WorkflowStatus } from '../../types/dashboard';

interface BulkActionsBarProps {
  selectedCount: number;
  onUpdateStatus: (status: WorkflowStatus) => void;
  onDelete: () => void;
  onClearSelection: () => void;
  isLoading?: boolean;
}

const BulkActionsBar: React.FC<BulkActionsBarProps> = ({
  selectedCount,
  onUpdateStatus,
  onDelete,
  onClearSelection,
  isLoading = false,
}) => {
  if (selectedCount === 0) return null;

  return (
    <div className="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50 animate-slideUp">
      <div className="bg-mono-black text-white rounded-full shadow-2xl px-6 py-4 flex items-center gap-4">
        <div className="flex items-center gap-2">
          <Badge variant="solid" size="sm" className="bg-white text-mono-black">
            {selectedCount}
          </Badge>
          <span className="text-sm font-medium">item{selectedCount !== 1 ? 's' : ''} selected</span>
        </div>

        <div className="h-6 w-px bg-mono-gray-600" />

        <div className="flex items-center gap-2">
          <select
            onChange={(e) => {
              const value = e.target.value as WorkflowStatus | '';
              if (value) {
                onUpdateStatus(value);
                e.target.value = '';
              }
            }}
            disabled={isLoading}
            className="px-3 py-1.5 text-sm bg-mono-gray-800 text-white border border-mono-gray-600 rounded focus:outline-none focus:ring-2 focus:ring-white"
          >
            <option value="">Change Status</option>
            <option value="pending">New</option>
            <option value="in_progress">In Progress</option>
            <option value="testing">Testing</option>
            <option value="awaiting_feedback">Awaiting Feedback</option>
            <option value="completed">Completed</option>
            <option value="delayed">Delayed</option>
          </select>

          <button
            onClick={onDelete}
            disabled={isLoading}
            className="px-3 py-1.5 text-sm bg-red-600 hover:bg-red-700 rounded transition-colors disabled:opacity-50"
            title="Delete selected items"
          >
            <i className="fas fa-trash mr-2" aria-hidden="true" />
            Delete
          </button>

          <button
            onClick={onClearSelection}
            disabled={isLoading}
            className="px-3 py-1.5 text-sm bg-mono-gray-700 hover:bg-mono-gray-600 rounded transition-colors disabled:opacity-50"
            title="Clear selection"
          >
            <i className="fas fa-times" aria-hidden="true" />
          </button>
        </div>

        {isLoading && (
          <>
            <div className="h-6 w-px bg-mono-gray-600" />
            <i className="fas fa-spinner fa-spin text-white" aria-hidden="true" />
          </>
        )}
      </div>
    </div>
  );
};

export default BulkActionsBar;
