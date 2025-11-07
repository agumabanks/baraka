import React, { useState, useEffect } from 'react';
import Button from '../ui/Button';
import Badge from '../ui/Badge';
import UserSelect, { type UserOption } from '../ui/UserSelect';
import type { WorkflowItem, WorkflowStatus } from '../../types/dashboard';

interface EditWorkflowModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (id: string, data: WorkflowFormData) => void;
  item: WorkflowItem | null;
  isLoading?: boolean;
  assignableUsers?: UserOption[];
}

export interface WorkflowFormData {
  title: string;
  description: string;
  priority: 'high' | 'medium' | 'low';
  status: WorkflowStatus;
  assignedTo?: string;
  dueDate?: string;
  trackingNumber?: string;
  tags?: string[];
}

const EditWorkflowModal: React.FC<EditWorkflowModalProps> = ({
  isOpen,
  onClose,
  onSubmit,
  item,
  isLoading = false,
  assignableUsers = [],
}) => {
  const [formData, setFormData] = useState<WorkflowFormData>({
    title: '',
    description: '',
    priority: 'medium',
    status: 'pending',
    assignedTo: '',
    dueDate: '',
    trackingNumber: '',
    tags: [],
  });

  const [tagInput, setTagInput] = useState('');

  useEffect(() => {
    if (item) {
      setFormData({
        title: item.title || '',
        description: item.description || '',
        priority:
          (typeof item.priority === 'string' ? item.priority : 'medium') as WorkflowFormData['priority'],
        status: item.status || 'pending',
        assignedTo: item.assignedTo ?? '',
        dueDate: item.dueDate ?? '',
        trackingNumber: item.trackingNumber ?? '',
        tags: item.tags ?? [],
      });
    }
  }, [item]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (item?.id) {
      onSubmit(item.id, formData);
    }
  };

  const handleAddTag = () => {
    if (tagInput.trim() && !formData.tags?.includes(tagInput.trim())) {
      setFormData({
        ...formData,
        tags: [...(formData.tags || []), tagInput.trim()],
      });
      setTagInput('');
    }
  };

  const handleRemoveTag = (tag: string) => {
    setFormData({
      ...formData,
      tags: formData.tags?.filter((t) => t !== tag),
    });
  };

  if (!isOpen || !item) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
      <div className="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div className="sticky top-0 bg-white border-b border-mono-gray-200 p-6 flex items-center justify-between">
          <div>
            <h2 className="text-2xl font-semibold text-mono-black">Edit Workflow Item</h2>
            <p className="text-sm text-mono-gray-600 mt-1">Update task details and status</p>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="text-mono-gray-500 hover:text-mono-black transition-colors"
            aria-label="Close modal"
          >
            <i className="fas fa-times text-xl" aria-hidden="true" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-6">
          {/* Title */}
          <div>
            <label htmlFor="edit-title" className="block text-sm font-medium text-mono-black mb-2">
              Task Title <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              id="edit-title"
              required
              value={formData.title}
              onChange={(e) => setFormData({ ...formData, title: e.target.value })}
              className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-transparent"
              placeholder="Enter task title..."
            />
          </div>

          {/* Description */}
          <div>
            <label htmlFor="edit-description" className="block text-sm font-medium text-mono-black mb-2">
              Description <span className="text-red-500">*</span>
            </label>
            <textarea
              id="edit-description"
              required
              rows={4}
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-transparent resize-none"
              placeholder="Describe the task in detail..."
            />
          </div>

          {/* Priority and Status Row */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label htmlFor="edit-priority" className="block text-sm font-medium text-mono-black mb-2">
                Priority <span className="text-red-500">*</span>
              </label>
              <select
                id="edit-priority"
                value={formData.priority}
                onChange={(e) =>
                  setFormData({
                    ...formData,
                    priority: e.target.value as WorkflowFormData['priority'],
                  })}
                className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-transparent"
              >
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
              </select>
            </div>

            <div>
              <label htmlFor="edit-status" className="block text-sm font-medium text-mono-black mb-2">
                Status <span className="text-red-500">*</span>
              </label>
              <select
                id="edit-status"
                value={formData.status}
                onChange={(e) => setFormData({ ...formData, status: e.target.value as WorkflowStatus })}
                className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-transparent"
              >
                <option value="pending">New</option>
                <option value="in_progress">In Progress</option>
                <option value="testing">Testing</option>
                <option value="awaiting_feedback">Awaiting Feedback</option>
                <option value="completed">Completed</option>
                <option value="delayed">Delayed</option>
              </select>
            </div>
          </div>

          {/* Tracking Number and Due Date Row */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label htmlFor="edit-trackingNumber" className="block text-sm font-medium text-mono-black mb-2">
                Tracking Number
              </label>
              <input
                type="text"
                id="edit-trackingNumber"
                value={formData.trackingNumber}
                onChange={(e) => setFormData({ ...formData, trackingNumber: e.target.value })}
                className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-transparent"
                placeholder="Optional tracking number..."
              />
            </div>

            <div>
              <label htmlFor="edit-dueDate" className="block text-sm font-medium text-mono-black mb-2">
                Due Date
              </label>
              <input
                type="datetime-local"
                id="edit-dueDate"
                value={formData.dueDate}
                onChange={(e) => setFormData({ ...formData, dueDate: e.target.value })}
                className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-transparent"
              />
            </div>
          </div>

          {/* Assigned To */}
          <div>
            <UserSelect
              label="Assign To"
              value={formData.assignedTo ?? ''}
              onChange={(userId) => setFormData({ ...formData, assignedTo: userId })}
              options={assignableUsers}
              placeholder="Select a team member..."
            />
          </div>

          {/* Tags */}
          <div>
            <label htmlFor="edit-tags" className="block text-sm font-medium text-mono-black mb-2">
              Tags
            </label>
            <div className="flex gap-2 mb-2">
              <input
                type="text"
                id="edit-tags"
                value={tagInput}
                onChange={(e) => setTagInput(e.target.value)}
                onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), handleAddTag())}
                className="flex-1 px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-transparent"
                placeholder="Add tags..."
              />
              <Button type="button" variant="secondary" size="sm" onClick={handleAddTag}>
                <i className="fas fa-plus" aria-hidden="true" />
              </Button>
            </div>
            {formData.tags && formData.tags.length > 0 && (
              <div className="flex flex-wrap gap-2">
                {formData.tags.map((tag) => (
                  <Badge key={tag} variant="outline" size="sm">
                    {tag}
                    <button
                      type="button"
                      onClick={() => handleRemoveTag(tag)}
                      className="ml-2 text-mono-gray-500 hover:text-mono-black"
                      aria-label={`Remove ${tag} tag`}
                    >
                      <i className="fas fa-times text-xs" aria-hidden="true" />
                    </button>
                  </Badge>
                ))}
              </div>
            )}
          </div>

          {/* Form Actions */}
          <div className="flex items-center justify-end gap-3 pt-4 border-t border-mono-gray-200">
            <Button
              type="button"
              variant="secondary"
              size="md"
              onClick={onClose}
              disabled={isLoading}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              variant="primary"
              size="md"
              disabled={isLoading}
            >
              {isLoading ? (
                <>
                  <i className="fas fa-spinner fa-spin mr-2" aria-hidden="true" />
                  Updating...
                </>
              ) : (
                <>
                  <i className="fas fa-save mr-2" aria-hidden="true" />
                  Save Changes
                </>
              )}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default EditWorkflowModal;
