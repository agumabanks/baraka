import React, { useState } from 'react';
import Button from '../ui/Button';
import Badge from '../ui/Badge';

interface CreateWorkflowModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (data: WorkflowFormData) => void;
  isLoading?: boolean;
}

export interface WorkflowFormData {
  title: string;
  description: string;
  priority: 'high' | 'medium' | 'low';
  status: 'pending' | 'in_progress' | 'completed' | 'delayed';
  assignedTo?: string;
  dueDate?: string;
  trackingNumber?: string;
  tags?: string[];
}

const CreateWorkflowModal: React.FC<CreateWorkflowModalProps> = ({
  isOpen,
  onClose,
  onSubmit,
  isLoading = false,
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

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit(formData);
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

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
      <div className="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div className="sticky top-0 bg-white border-b border-mono-gray-200 p-6 flex items-center justify-between">
          <div>
            <h2 className="text-2xl font-semibold text-mono-black">Create Workflow Item</h2>
            <p className="text-sm text-mono-gray-600 mt-1">Add a new task to the workflow queue</p>
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
            <label htmlFor="title" className="block text-sm font-medium text-mono-black mb-2">
              Task Title <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              id="title"
              required
              value={formData.title}
              onChange={(e) => setFormData({ ...formData, title: e.target.value })}
              className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-transparent"
              placeholder="Enter task title..."
            />
          </div>

          {/* Description */}
          <div>
            <label htmlFor="description" className="block text-sm font-medium text-mono-black mb-2">
              Description <span className="text-red-500">*</span>
            </label>
            <textarea
              id="description"
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
              <label htmlFor="priority" className="block text-sm font-medium text-mono-black mb-2">
                Priority <span className="text-red-500">*</span>
              </label>
              <select
                id="priority"
                value={formData.priority}
                onChange={(e) => setFormData({ ...formData, priority: e.target.value as any })}
                className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-transparent"
              >
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
              </select>
            </div>

            <div>
              <label htmlFor="status" className="block text-sm font-medium text-mono-black mb-2">
                Initial Status
              </label>
              <select
                id="status"
                value={formData.status}
                onChange={(e) => setFormData({ ...formData, status: e.target.value as any })}
                className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-transparent"
              >
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="delayed">Delayed</option>
              </select>
            </div>
          </div>

          {/* Tracking Number and Due Date Row */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label htmlFor="trackingNumber" className="block text-sm font-medium text-mono-black mb-2">
                Tracking Number
              </label>
              <input
                type="text"
                id="trackingNumber"
                value={formData.trackingNumber}
                onChange={(e) => setFormData({ ...formData, trackingNumber: e.target.value })}
                className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-transparent"
                placeholder="Optional tracking number..."
              />
            </div>

            <div>
              <label htmlFor="dueDate" className="block text-sm font-medium text-mono-black mb-2">
                Due Date
              </label>
              <input
                type="datetime-local"
                id="dueDate"
                value={formData.dueDate}
                onChange={(e) => setFormData({ ...formData, dueDate: e.target.value })}
                className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-transparent"
              />
            </div>
          </div>

          {/* Assigned To */}
          <div>
            <label htmlFor="assignedTo" className="block text-sm font-medium text-mono-black mb-2">
              Assign To
            </label>
            <input
              type="text"
              id="assignedTo"
              value={formData.assignedTo}
              onChange={(e) => setFormData({ ...formData, assignedTo: e.target.value })}
              className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-transparent"
              placeholder="User ID or name..."
            />
          </div>

          {/* Tags */}
          <div>
            <label htmlFor="tags" className="block text-sm font-medium text-mono-black mb-2">
              Tags
            </label>
            <div className="flex gap-2 mb-2">
              <input
                type="text"
                id="tags"
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
                  Creating...
                </>
              ) : (
                <>
                  <i className="fas fa-plus mr-2" aria-hidden="true" />
                  Create Task
                </>
              )}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default CreateWorkflowModal;
