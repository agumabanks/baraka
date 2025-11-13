import React, { useState } from 'react';
import { Card } from '../../ui/Card';
import { Button } from '../../ui/Button';
import { Badge } from '../../ui/Badge';
import { Input } from '../../ui/Input';
import { Spinner } from '../../ui/Spinner';
import { WebhookEndpoint, WebhookEndpointFilters } from '../../../types/webhook';

interface WebhookListProps {
  endpoints: WebhookEndpoint[];
  loading: boolean;
  onTest: (endpoint: WebhookEndpoint) => void;
  onDelete: (endpoint: WebhookEndpoint) => void;
  onSelect: (endpoint: WebhookEndpoint) => void;
  onRefresh: () => void;
}

export const WebhookList: React.FC<WebhookListProps> = ({
  endpoints,
  loading,
  onTest,
  onDelete,
  onSelect,
  onRefresh,
}) => {
  const [filters, setFilters] = useState<WebhookEndpointFilters>({
    status: 'all',
    search: '',
  });

  const filteredEndpoints = endpoints.filter((endpoint) => {
    const matchesStatus = filters.status === 'all' || 
                         (filters.status === 'active' && endpoint.is_active) ||
                         (filters.status === 'inactive' && !endpoint.is_active);
    const matchesSearch = !filters.search || 
                         endpoint.name.toLowerCase().includes(filters.search.toLowerCase()) ||
                         endpoint.url.toLowerCase().includes(filters.search.toLowerCase());
    return matchesStatus && matchesSearch;
  });

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const getStatusBadge = (isActive: boolean) => {
    return isActive ? 
      <Badge variant="success">Active</Badge> : 
      <Badge variant="default">Inactive</Badge>;
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
              placeholder="Search endpoints..."
              value={filters.search}
              onChange={(e) => setFilters(prev => ({ ...prev, search: e.target.value }))}
            />
          </div>
          <div className="flex gap-2">
            <select
              value={filters.status}
              onChange={(e) => setFilters(prev => ({ ...prev, status: e.target.value as any }))}
              className="px-3 py-2 border border-gray-300 rounded-md text-sm"
            >
              <option value="all">All Statuses</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
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

      {/* Endpoints List */}
      <div className="space-y-4">
        {filteredEndpoints.length === 0 ? (
          <Card className="p-8 text-center">
            <p className="text-mono-gray-600">No webhook endpoints found.</p>
          </Card>
        ) : (
          filteredEndpoints.map((endpoint) => (
            <Card key={endpoint.id} className="p-6 hover:shadow-md transition-shadow">
              <div className="flex items-start justify-between">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-3 mb-2">
                    <h3 className="text-lg font-semibold text-mono-gray-900">
                      {endpoint.name}
                    </h3>
                    {getStatusBadge(endpoint.is_active)}
                    {endpoint.last_test_at && (
                      <Badge variant="outline">
                        Last Test: {formatDate(endpoint.last_test_at)}
                      </Badge>
                    )}
                  </div>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                    <div>
                      <p className="text-mono-gray-600">URL</p>
                      <p className="font-mono text-xs text-mono-gray-900 truncate">
                        {endpoint.url}
                      </p>
                    </div>
                    <div>
                      <p className="text-mono-gray-600">Events</p>
                      <p className="text-mono-gray-900">
                        {endpoint.events ? endpoint.events.join(', ') : 'Any'}
                      </p>
                    </div>
                    <div>
                      <p className="text-mono-gray-600">Success Rate</p>
                      <p className="text-mono-gray-900">
                        {endpoint.success_rate ? `${endpoint.success_rate}%` : 'N/A'}
                      </p>
                    </div>
                    <div>
                      <p className="text-mono-gray-600">Created</p>
                      <p className="text-mono-gray-900">{formatDate(endpoint.created_at)}</p>
                    </div>
                  </div>

                  {endpoint.description && (
                    <div className="mt-3">
                      <p className="text-mono-gray-600 text-sm">{endpoint.description}</p>
                    </div>
                  )}

                  {endpoint.last_delivery_error && (
                    <div className="mt-3 p-3 bg-red-50 border border-red-200 rounded">
                      <p className="text-sm font-medium text-red-800">Last Error</p>
                      <p className="text-sm text-red-700 mt-1">{endpoint.last_delivery_error}</p>
                    </div>
                  )}
                </div>

                <div className="flex flex-col gap-2 ml-4">
                  <Button
                    onClick={() => onSelect(endpoint)}
                    variant="outline"
                    size="sm"
                  >
                    View
                  </Button>
                  <Button
                    onClick={() => onTest(endpoint)}
                    variant="outline"
                    size="sm"
                  >
                    Test
                  </Button>
                  <Button
                    onClick={() => onDelete(endpoint)}
                    variant="outline"
                    size="sm"
                    className="text-red-600 hover:text-red-700 hover:bg-red-50"
                  >
                    Delete
                  </Button>
                </div>
              </div>
            </Card>
          ))
        )}
      </div>
    </div>
  );
};