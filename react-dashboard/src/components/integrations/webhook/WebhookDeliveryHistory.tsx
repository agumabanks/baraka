import React, { useState } from 'react';
import { Card } from '../../ui/Card';
import { Button } from '../../ui/Button';
import { Badge } from '../../ui/Badge';
import { Input } from '../../ui/Input';
import { Spinner } from '../../ui/Spinner';
import { WebhookDelivery, WebhookDeliveryFilters } from '../../../types/webhook';

interface WebhookDeliveryHistoryProps {
  deliveries: WebhookDelivery[];
  loading: boolean;
  onRetry: (deliveryId: string) => void;
  onRefresh: () => void;
}

export const WebhookDeliveryHistory: React.FC<WebhookDeliveryHistoryProps> = ({
  deliveries,
  loading,
  onRetry,
  onRefresh,
}) => {
  const [filters, setFilters] = useState<WebhookDeliveryFilters>({
    status: undefined,
    search: '',
  });
  const [selectedDelivery, setSelectedDelivery] = useState<WebhookDelivery | null>(null);
  const [showDetail, setShowDetail] = useState(false);

  const filteredDeliveries = deliveries.filter((delivery) => {
    const matchesStatus = !filters.status || delivery.status === filters.status;
    const matchesSearch = !filters.search || 
                         delivery.event_type.toLowerCase().includes(filters.search.toLowerCase()) ||
                         delivery.id.toLowerCase().includes(filters.search.toLowerCase());
    return matchesStatus && matchesSearch;
  });

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    });
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'success':
        return <Badge variant="success">Success</Badge>;
      case 'failed':
        return <Badge variant="destructive">Failed</Badge>;
      case 'retrying':
        return <Badge variant="warning">Retrying</Badge>;
      case 'pending':
        return <Badge variant="default">Pending</Badge>;
      default:
        return <Badge variant="default">{status}</Badge>;
    }
  };

  const handleViewDetails = (delivery: WebhookDelivery) => {
    setSelectedDelivery(delivery);
    setShowDetail(true);
  };

  const handleRetry = async (deliveryId: string) => {
    if (confirm('Are you sure you want to retry this delivery?')) {
      onRetry(deliveryId);
    }
  };

  if (loading) {
    return (
      <Card className="p-8">
        <div className="flex justify-center">
          <Spinner size="lg" />
        </div>
      </Card>
    );
  }

  return (
    <div className="space-y-6">
      {/* Filters */}
      <Card className="p-4">
        <div className="flex flex-col sm:flex-row gap-4">
          <div className="flex-1">
            <Input
              placeholder="Search deliveries..."
              value={filters.search}
              onChange={(e) => setFilters(prev => ({ ...prev, search: e.target.value }))}
            />
          </div>
          <div className="flex gap-2">
            <select
              value={filters.status || ''}
              onChange={(e) => setFilters(prev => ({ 
                ...prev, 
                status: e.target.value as any || undefined 
              }))}
              className="px-3 py-2 border border-gray-300 rounded-md text-sm"
            >
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="success">Success</option>
              <option value="failed">Failed</option>
              <option value="retrying">Retrying</option>
            </select>
            <Button
              onClick={onRefresh}
              variant="outline"
              size="sm"
            >
              Refresh
            </Button>
          </div>
        </div>
      </Card>

      {/* Deliveries List */}
      <div className="space-y-4">
        {filteredDeliveries.length === 0 ? (
          <Card className="p-8 text-center">
            <p className="text-mono-gray-600">No webhook deliveries found.</p>
          </Card>
        ) : (
          filteredDeliveries.map((delivery) => (
            <Card key={delivery.id} className="p-6 hover:shadow-md transition-shadow">
              <div className="flex items-start justify-between">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-3 mb-2">
                    <h3 className="text-lg font-semibold text-mono-gray-900">
                      {delivery.event_type}
                    </h3>
                    {getStatusBadge(delivery.status)}
                    {delivery.retry_count > 0 && (
                      <Badge variant="outline">
                        Retry #{delivery.retry_count}
                      </Badge>
                    )}
                  </div>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                    <div>
                      <p className="text-mono-gray-600">Delivery ID</p>
                      <p className="font-mono text-xs text-mono-gray-900 truncate">
                        {delivery.id}
                      </p>
                    </div>
                    <div>
                      <p className="text-mono-gray-600">Endpoint</p>
                      <p className="font-mono text-xs text-mono-gray-900 truncate">
                        {delivery.webhook_endpoint_id}
                      </p>
                    </div>
                    <div>
                      <p className="text-mono-gray-600">Created</p>
                      <p className="text-mono-gray-900">{formatDate(delivery.created_at)}</p>
                    </div>
                    {delivery.duration_ms && (
                      <div>
                        <p className="text-mono-gray-600">Duration</p>
                        <p className="text-mono-gray-900">{delivery.duration_ms}ms</p>
                      </div>
                    )}
                  </div>

                  {delivery.response_status && (
                    <div className="mt-3 p-3 bg-gray-50 rounded">
                      <div className="flex items-center justify-between">
                        <div>
                          <p className="text-sm font-medium text-mono-gray-700">Response</p>
                          <p className="text-sm text-mono-gray-600">
                            Status: {delivery.response_status}
                          </p>
                        </div>
                        {delivery.completed_at && (
                          <p className="text-sm text-mono-gray-600">
                            Completed: {formatDate(delivery.completed_at)}
                          </p>
                        )}
                      </div>
                    </div>
                  )}

                  {delivery.error_message && (
                    <div className="mt-3 p-3 bg-red-50 border border-red-200 rounded">
                      <p className="text-sm font-medium text-red-800">Error</p>
                      <p className="text-sm text-red-700 mt-1">{delivery.error_message}</p>
                    </div>
                  )}
                </div>

                <div className="flex flex-col gap-2 ml-4">
                  <Button
                    onClick={() => handleViewDetails(delivery)}
                    variant="outline"
                    size="sm"
                  >
                    Details
                  </Button>
                  {delivery.status === 'failed' && (
                    <Button
                      onClick={() => handleRetry(delivery.id)}
                      variant="outline"
                      size="sm"
                    >
                      Retry
                    </Button>
                  )}
                </div>
              </div>
            </Card>
          ))
        )}
      </div>

      {/* Detail Modal */}
      {showDetail && selectedDelivery && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6">
              <div className="flex justify-between items-center mb-6">
                <h2 className="text-2xl font-bold text-mono-gray-900">Delivery Details</h2>
                <Button
                  onClick={() => setShowDetail(false)}
                  variant="ghost"
                  size="sm"
                >
                  ×
                </Button>
              </div>

              <div className="space-y-6">
                {/* Basic Info */}
                <div>
                  <h3 className="text-lg font-semibold text-mono-gray-900 mb-3">Basic Information</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="text-sm font-medium text-mono-gray-600">Delivery ID</label>
                      <p className="text-sm text-mono-gray-900 font-mono">{selectedDelivery.id}</p>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-mono-gray-600">Event Type</label>
                      <p className="text-sm text-mono-gray-900">{selectedDelivery.event_type}</p>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-mono-gray-600">Status</label>
                      <div className="mt-1">{getStatusBadge(selectedDelivery.status)}</div>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-mono-gray-600">Retry Count</label>
                      <p className="text-sm text-mono-gray-900">{selectedDelivery.retry_count}</p>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-mono-gray-600">Created</label>
                      <p className="text-sm text-mono-gray-900">{formatDate(selectedDelivery.created_at)}</p>
                    </div>
                    {selectedDelivery.completed_at && (
                      <div>
                        <label className="text-sm font-medium text-mono-gray-600">Completed</label>
                        <p className="text-sm text-mono-gray-900">{formatDate(selectedDelivery.completed_at)}</p>
                      </div>
                    )}
                    {selectedDelivery.duration_ms && (
                      <div>
                        <label className="text-sm font-medium text-mono-gray-600">Duration</label>
                        <p className="text-sm text-mono-gray-900">{selectedDelivery.duration_ms}ms</p>
                      </div>
                    )}
                  </div>
                </div>

                {/* Payload */}
                <div>
                  <h3 className="text-lg font-semibold text-mono-gray-900 mb-3">Payload</h3>
                  <div className="bg-gray-50 p-4 rounded-lg">
                    <pre className="text-sm text-mono-gray-900 whitespace-pre-wrap overflow-x-auto">
                      {JSON.stringify(selectedDelivery.payload, null, 2)}
                    </pre>
                  </div>
                </div>

                {/* Response */}
                {selectedDelivery.response_body && (
                  <div>
                    <h3 className="text-lg font-semibold text-mono-gray-900 mb-3">Response</h3>
                    <div className="bg-gray-50 p-4 rounded-lg">
                      <pre className="text-sm text-mono-gray-900 whitespace-pre-wrap overflow-x-auto">
                        {selectedDelivery.response_body}
                      </pre>
                    </div>
                  </div>
                )}

                {/* Error */}
                {selectedDelivery.error_message && (
                  <div>
                    <h3 className="text-lg font-semibold text-mono-gray-900 mb-3">Error</h3>
                    <div className="bg-red-50 border border-red-200 p-4 rounded-lg">
                      <p className="text-sm text-red-800">{selectedDelivery.error_message}</p>
                    </div>
                  </div>
                )}
              </div>

              <div className="flex justify-end mt-6">
                <Button
                  onClick={() => setShowDetail(false)}
                  variant="outline"
                >
                  Close
                </Button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};