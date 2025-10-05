import React, { useState } from 'react';
import Button from '../ui/Button';
import Card from '../ui/Card';

interface Comment {
  id: string;
  text: string;
  author: string;
  created_at: string;
  updated_at?: string;
}

interface CommentsPanelProps {
  workflowItemId: string;
  comments: Comment[];
  onAddComment: (text: string) => void;
  onEditComment: (id: string, text: string) => void;
  onDeleteComment: (id: string) => void;
  isLoading?: boolean;
}

const CommentsPanel: React.FC<CommentsPanelProps> = ({
  comments,
  onAddComment,
  onEditComment,
  onDeleteComment,
  isLoading = false,
}) => {
  const [newComment, setNewComment] = useState('');
  const [editingId, setEditingId] = useState<string | null>(null);
  const [editText, setEditText] = useState('');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (newComment.trim()) {
      onAddComment(newComment.trim());
      setNewComment('');
    }
  };

  const handleEdit = (comment: Comment) => {
    setEditingId(comment.id);
    setEditText(comment.text);
  };

  const handleSaveEdit = (id: string) => {
    if (editText.trim()) {
      onEditComment(id, editText.trim());
      setEditingId(null);
      setEditText('');
    }
  };

  const handleCancelEdit = () => {
    setEditingId(null);
    setEditText('');
  };

  const formatDateTime = (date: string) => {
    return new Date(date).toLocaleString();
  };

  return (
    <Card className="border border-mono-gray-200">
      <header className="flex items-center justify-between mb-4">
        <div>
          <h3 className="text-lg font-semibold text-mono-black">Comments & Notes</h3>
          <p className="text-xs text-mono-gray-500">{comments.length} comment{comments.length !== 1 ? 's' : ''}</p>
        </div>
      </header>

      {/* Add Comment Form */}
      <form onSubmit={handleSubmit} className="mb-6">
        <div className="flex gap-2">
          <textarea
            value={newComment}
            onChange={(e) => setNewComment(e.target.value)}
            placeholder="Add a comment or note..."
            rows={3}
            className="flex-1 px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-transparent resize-none"
            disabled={isLoading}
          />
        </div>
        <div className="flex justify-end mt-2">
          <Button
            type="submit"
            variant="primary"
            size="sm"
            disabled={isLoading || !newComment.trim()}
          >
            <i className="fas fa-comment mr-2" aria-hidden="true" />
            Add Comment
          </Button>
        </div>
      </form>

      {/* Comments List */}
      <div className="space-y-4">
        {comments.length === 0 ? (
          <div className="text-center py-8">
            <i className="fas fa-comments text-4xl text-mono-gray-300 mb-3" aria-hidden="true" />
            <p className="text-sm text-mono-gray-500">No comments yet. Be the first to add one!</p>
          </div>
        ) : (
          comments.map((comment) => (
            <div
              key={comment.id}
              className="border border-mono-gray-200 rounded-lg p-4 hover:border-mono-gray-400 transition-colors"
            >
              {editingId === comment.id ? (
                <div className="space-y-2">
                  <textarea
                    value={editText}
                    onChange={(e) => setEditText(e.target.value)}
                    rows={3}
                    className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-transparent resize-none"
                  />
                  <div className="flex gap-2">
                    <Button
                      variant="primary"
                      size="sm"
                      onClick={() => handleSaveEdit(comment.id)}
                    >
                      <i className="fas fa-save mr-2" aria-hidden="true" />
                      Save
                    </Button>
                    <Button
                      variant="secondary"
                      size="sm"
                      onClick={handleCancelEdit}
                    >
                      Cancel
                    </Button>
                  </div>
                </div>
              ) : (
                <>
                  <div className="flex items-start justify-between mb-2">
                    <div className="flex items-center gap-2">
                      <div className="w-8 h-8 rounded-full bg-mono-gray-200 flex items-center justify-center">
                        <i className="fas fa-user text-mono-gray-600 text-sm" aria-hidden="true" />
                      </div>
                      <div>
                        <p className="text-sm font-semibold text-mono-black">{comment.author}</p>
                        <p className="text-xs text-mono-gray-500">{formatDateTime(comment.created_at)}</p>
                      </div>
                    </div>
                    <div className="flex gap-2">
                      <button
                        type="button"
                        onClick={() => handleEdit(comment)}
                        className="text-mono-gray-500 hover:text-mono-black transition-colors"
                        aria-label="Edit comment"
                      >
                        <i className="fas fa-edit text-sm" aria-hidden="true" />
                      </button>
                      <button
                        type="button"
                        onClick={() => onDeleteComment(comment.id)}
                        className="text-mono-gray-500 hover:text-red-600 transition-colors"
                        aria-label="Delete comment"
                      >
                        <i className="fas fa-trash text-sm" aria-hidden="true" />
                      </button>
                    </div>
                  </div>
                  <p className="text-sm text-mono-gray-700 whitespace-pre-wrap">{comment.text}</p>
                  {comment.updated_at && comment.updated_at !== comment.created_at && (
                    <p className="text-xs text-mono-gray-400 mt-2 italic">
                      Edited {formatDateTime(comment.updated_at)}
                    </p>
                  )}
                </>
              )}
            </div>
          ))
        )}
      </div>
    </Card>
  );
};

export default CommentsPanel;
