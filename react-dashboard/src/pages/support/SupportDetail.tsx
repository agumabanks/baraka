import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { supportApi } from '../../services/api';
import type {
  SupportDetail as SupportDetailResponse,
  SupportPriority,
  SupportReplyData,
  SupportStatus,
} from '../../types/support';

const statusBadgeMap: Record<SupportStatus, { label: string; className: string }> = {
  pending: {
    label: 'Pending',
    className: 'bg-yellow-100 text-yellow-800 border border-yellow-300',
  },
  processing: {
    label: 'Processing',
    className: 'bg-blue-100 text-blue-800 border border-blue-300',
  },
  resolved: {
    label: 'Resolved',
    className: 'bg-green-100 text-green-800 border border-green-300',
  },
  closed: {
    label: 'Closed',
    className: 'bg-mono-gray-200 text-mono-gray-700',
  },
};

const priorityBadgeMap: Record<SupportPriority, { label: string; className: string }> = {
  low: {
    label: 'Low',
    className: 'bg-mono-gray-100 text-mono-gray-700',
  },
  medium: {
    label: 'Medium',
    className: 'bg-blue-100 text-blue-700',
  },
  high: {
    label: 'High',
    className: 'bg-orange-100 text-orange-700 border border-orange-300',
  },
  urgent: {
    label: 'Urgent',
    className: 'bg-red-100 text-red-700 border border-red-300',
  },
};

const SupportDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [replyMessage, setReplyMessage] = useState('');
  const [replyFile, setReplyFile] = useState<File | null>(null);

  const {
    data,
    isLoading,
    isError,
    error,
  } = useQuery<SupportDetailResponse, Error>({
    queryKey: ['support', 'ticket', id],
    queryFn: async () => {
      if (!id) throw new Error('Support ID is required');
      const response = await supportApi.getSupportDetail(Number(id));
      return response.data;
    },
    enabled: !!id,
  });

  const replyMutation = useMutation({
    mutationFn: async (payload: SupportReplyData) => {
      const response = await supportApi.replyToSupport(payload);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['support', 'ticket', id] });
      setReplyMessage('');
      setReplyFile(null);
      window.alert('Reply sent successfully.');
    },
    onError: (error: Error) => {
      console.error('Failed to send reply', error);
      window.alert('Failed to send reply. Please try again.');
    },
  });

  const handleReplySubmit = (event: React.FormEvent) => {
    event.preventDefault();
    if (!id || !replyMessage.trim()) {
      window.alert('Please enter a message.');
      return;
    }

    replyMutation.mutate({
      support_id: Number(id),
      message: replyMessage.trim(),
      attached_file: replyFile,
    });
  };

  const handleFileChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0] || null;
    setReplyFile(file);
  };

  if (isLoading) {
    return (
      <div className="flex h-full items-center justify-center">
        <LoadingSpinner message="Loading support ticket" />
      </div>
    );
  }

  if (isError || !data) {
    return (
      <div className="space-y-4">
        <Card>
          <div className="space-y-2 text-center">
            <h1 className="text-xl font-semibold text-mono-black">Unable to load support ticket</h1>
            <p className="text-sm text-mono-gray-700">{error?.message ?? 'Something went wrong while fetching support data.'}</p>
            <div className="flex justify-center gap-2">
              <Button onClick={() => navigate('/dashboard/support')} variant="secondary">
                Back to List
              </Button>
              <Button onClick={() => window.location.reload()} variant="primary">
                Retry
              </Button>
            </div>
          </div>
        </Card>
      </div>
    );
  }

  const { support, chats } = data;
  const statusBadge = statusBadgeMap[support.status ?? 'pending'];
  const priorityBadge = priorityBadgeMap[support.priority];

  return (
    <div className="space-y-6">
      <header className="space-y-3">
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => navigate('/dashboard/support')}
            className="uppercase tracking-[0.2em]"
          >
            ← Back
          </Button>
        </div>
        <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
          Support Ticket #{support.id}
        </p>
        <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
          <div className="space-y-3">
            <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
              {support.subject}
            </h1>
            <div className="flex items-center gap-3">
              <span className={`rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] ${statusBadge.className}`}>
                {statusBadge.label}
              </span>
              <span className={`rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] ${priorityBadge.className}`}>
                {priorityBadge.label}
              </span>
            </div>
          </div>
          <div className="flex items-center gap-3">
            <Button
              variant="secondary"
              className="uppercase tracking-[0.25em]"
              onClick={() => navigate(`/dashboard/support/${id}/edit`)}
            >
              Edit Ticket
            </Button>
          </div>
        </div>
      </header>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Ticket Information */}
        <div className="lg:col-span-1">
          <Card className="border border-mono-gray-200">
            <div className="space-y-4">
              <h2 className="text-lg font-semibold text-mono-black">Ticket Information</h2>
              
              <div className="space-y-3">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">User</p>
                  <p className="text-sm font-medium text-mono-black">{support.userName}</p>
                  <p className="text-xs text-mono-gray-600">{support.userEmail}</p>
                  {support.userMobile && (
                    <p className="text-xs text-mono-gray-600">{support.userMobile}</p>
                  )}
                </div>

                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Department</p>
                  <p className="text-sm text-mono-gray-700">{support.department}</p>
                </div>

                {support.service && (
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Service</p>
                    <p className="text-sm text-mono-gray-700">{support.service}</p>
                  </div>
                )}

                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Date</p>
                  <p className="text-sm text-mono-gray-700">{support.date}</p>
                </div>

                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Description</p>
                  <p className="text-sm text-mono-gray-700 whitespace-pre-wrap">{support.description}</p>
                </div>
              </div>
            </div>
          </Card>
        </div>

        {/* Conversation */}
        <div className="lg:col-span-2">
          <Card className="border border-mono-gray-200">
            <div className="space-y-6">
              <h2 className="text-lg font-semibold text-mono-black">Conversation</h2>

              {/* Chat Messages */}
              <div className="space-y-4 max-h-96 overflow-y-auto">
                {chats.length === 0 ? (
                  <p className="text-sm text-mono-gray-600 text-center py-8">
                    No messages yet. Start the conversation by replying below.
                  </p>
                ) : (
                  chats.map((chat) => (
                    <div key={chat.id} className="border-l-4 border-blue-500 bg-mono-gray-50 p-4 rounded-r-lg">
                      <div className="flex items-start justify-between mb-2">
                        <div>
                          <p className="text-sm font-semibold text-mono-black">
                            {chat.user_name || 'User'}
                          </p>
                          <p className="text-xs text-mono-gray-500">
                            {new Date(chat.created_at).toLocaleString()}
                          </p>
                        </div>
                      </div>
                      <p className="text-sm text-mono-gray-700 whitespace-pre-wrap">{chat.message}</p>
                    </div>
                  ))
                )}
              </div>

              {/* Reply Form */}
              <div className="border-t border-mono-gray-200 pt-6">
                <h3 className="text-md font-semibold text-mono-black mb-4">Send Reply</h3>
                <form onSubmit={handleReplySubmit} className="space-y-4">
                  <div>
                    <label htmlFor="reply-message" className="block text-sm font-medium text-mono-gray-700 mb-2">
                      Message
                    </label>
                    <textarea
                      id="reply-message"
                      rows={4}
                      className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 text-sm focus:border-mono-black focus:outline-none focus:ring-1 focus:ring-mono-black"
                      placeholder="Type your reply here..."
                      value={replyMessage}
                      onChange={(e) => setReplyMessage(e.target.value)}
                      required
                    />
                  </div>

                  <div>
                    <label htmlFor="reply-file" className="block text-sm font-medium text-mono-gray-700 mb-2">
                      Attachment (Optional)
                    </label>
                    <input
                      id="reply-file"
                      type="file"
                      className="w-full text-sm text-mono-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-mono-gray-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-mono-gray-700 hover:file:bg-mono-gray-200"
                      onChange={handleFileChange}
                    />
                    {replyFile && (
                      <p className="mt-2 text-xs text-mono-gray-600">
                        Selected: {replyFile.name}
                      </p>
                    )}
                  </div>

                  <div className="flex justify-end gap-2">
                    <Button
                      type="button"
                      variant="ghost"
                      onClick={() => {
                        setReplyMessage('');
                        setReplyFile(null);
                      }}
                      disabled={replyMutation.isPending}
                    >
                      Clear
                    </Button>
                    <Button
                      type="submit"
                      variant="primary"
                      disabled={replyMutation.isPending || !replyMessage.trim()}
                      className="uppercase tracking-[0.25em]"
                    >
                      {replyMutation.isPending ? 'Sending...' : 'Send Reply'}
                    </Button>
                  </div>
                </form>
              </div>
            </div>
          </Card>
        </div>
      </div>
    </div>
  );
};

export default SupportDetail;
