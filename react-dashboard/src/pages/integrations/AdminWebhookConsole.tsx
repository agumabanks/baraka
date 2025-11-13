import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Badge from '../../components/ui/Badge';
import Spinner from '../../components/ui/Spinner';
import { webhookApi } from '../../services/api';
import { WebhookEndpoint, WebhookDelivery, WebhookMetrics, WebhookEndpointForm } from '../../types/webhook';
import { WebhookList } from '../../components/integrations/webhook/WebhookList';
import { WebhookCreateModal } from '../../components/integrations/webhook/WebhookCreateModal';
import { WebhookHealthDashboard } from '../../components/integrations/webhook/WebhookHealthDashboard';
import { WebhookDeliveryHistory } from '../../components/integrations/webhook/WebhookDeliveryHistory';
import { WebhookEndpointDetails } from '../../components/integrations/webhook/WebhookEndpointDetails';
import { toast } from '../../stores/toastStore';

type TabType = 'overview' | 'endpoints' | 'deliveries' | 'health' | 'settings';

export const AdminWebhookConsole: React.FC = () => {
  const [activeTab, setActiveTab] = useState<TabType>('overview');
  const [selectedEndpoint, setSelectedEndpoint] = useState<WebhookEndpoint | null>(null);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showEndpointDetails, setShowEndpointDetails] = useState(false);
  const queryClient = useQueryClient();

  // Queries
  const { data: endpointsResponse, isLoading: endpointsLoading } = useQuery({
    queryKey: ['webhooks', 'endpoints'],
    queryFn: webhookApi.getEndpoints,
  });

  const { data: deliveriesResponse, isLoading: deliveriesLoading } = useQuery({
    queryKey: ['webhooks', 'deliveries', 'all'],
    queryFn: () => webhookApi.getDeliveries(),
  });

  const { data: metricsResponse, isLoading: metricsLoading } = useQuery({
    queryKey: ['webhooks', 'metrics'],
    queryFn: webhookApi.getMetrics,
  });

  const { data: healthResponse, isLoading: healthLoading } = useQuery({
    queryKey: ['webhooks', 'health'],
    queryFn: webhookApi.getHealthStatus,
  });

  // Mutations
  const createEndpointMutation = useMutation({
    mutationFn: (data: WebhookEndpointForm) => webhookApi.createEndpoint(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['webhooks', 'endpoints'] });
      setShowCreateModal(false);
      toast.success({
        title: 'Webhook Endpoint Created',
        description: 'New webhook endpoint has been created successfully.',
      });
    },
    onError: (error: unknown) => {
      toast.error({
        title: 'Creation Failed',
        description: error instanceof Error ? error.message : 'Failed to create webhook endpoint.',
      });
    },
  });

  const deleteEndpointMutation = useMutation({
    mutationFn: (endpointId: string) => webhookApi.deleteEndpoint(endpointId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['webhooks', 'endpoints'] });
      toast.success({
        title: 'Webhook Endpoint Deleted',
        description: 'Webhook endpoint has been deleted successfully.',
      });
    },
    onError: (error: unknown) => {
      toast.error({
        title: 'Deletion Failed',
        description: error instanceof Error ? error.message : 'Failed to delete webhook endpoint.',
      });
    },
  });

  const testEndpointMutation = useMutation({
    mutationFn: (endpointId: string) => webhookApi.testEndpoint(endpointId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['webhooks', 'deliveries'] });
      toast.success({
        title: 'Test Sent',
        description: 'Test webhook has been sent to the endpoint.',
      });
    },
    onError: (error: unknown) => {
      toast.error({
        title: 'Test Failed',
        description: error instanceof Error ? error.message : 'Failed to send test webhook.',
      });
    },
  });

  const retryDeliveryMutation = useMutation({
    mutationFn: (deliveryId: string) => webhookApi.retryDelivery(deliveryId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['webhooks', 'deliveries'] });
      toast.success({
        title: 'Delivery Retried',
        description: 'Webhook delivery has been retried.',
      });
    },
    onError: (error: unknown) => {
      toast.error({
        title: 'Retry Failed',
        description: error instanceof Error ? error.message : 'Failed to retry webhook delivery.',
      });
    },
  });

  const handleCreateEndpoint = (data: WebhookEndpointForm) => {
    createEndpointMutation.mutate(data);
  };

  const handleDeleteEndpoint = (endpoint: WebhookEndpoint) => {
    if (confirm(`Are you sure you want to delete the webhook endpoint "${endpoint.name}"?`)) {
      deleteEndpointMutation.mutate(endpoint.id);
    }
  };

  const handleTestEndpoint = (endpoint: WebhookEndpoint) => {
    testEndpointMutation.mutate(endpoint.id);
  };

  const handleViewEndpointDetails = (endpoint: WebhookEndpoint) => {
    setSelectedEndpoint(endpoint);
    setShowEndpointDetails(true);
  };

  const handleRetryDelivery = (deliveryId: string) => {
    retryDeliveryMutation.mutate(deliveryId);
  };

  const handleRefresh = () => {
    queryClient.invalidateQueries({ queryKey: ['webhooks', 'endpoints'] });
    queryClient.invalidateQueries({ queryKey: ['webhooks', 'deliveries'] });
    queryClient.invalidateQueries({ queryKey: ['webhooks', 'metrics'] });
    queryClient.invalidateQueries({ queryKey: ['webhooks', 'health'] });
  };

  const endpoints = endpointsResponse?.data ?? [];
  const deliveries = deliveriesResponse?.data ?? [];
  const metrics = metricsResponse?.data;
  const health = healthResponse?.data;

  const tabs = [
    { id: 'overview', label: 'Overview', icon: 'LayoutDashboard' },
    { id: 'endpoints', label: 'Endpoints', icon: 'Globe' },
    { id: 'deliveries', label: 'Deliveries', icon: 'Send' },
    { id: 'health', label: 'Health', icon: 'Activity' },
    { id: 'settings', label: 'Settings', icon: 'Settings' },
  ] as const;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-mono-gray-900">Webhook Management Console</h1>
          <p className="text-mono-gray-600 mt-1">
            Manage webhook endpoints, monitor deliveries, and track system health
          </p>
        </div>
        <div className="flex gap-2">
          <Button
            onClick={() => setShowCreateModal(true)}
            className="bg-blue-600 hover:bg-blue-700 text-white"
          >
            Create Endpoint
          </Button>
          <Button
            onClick={handleRefresh}
            variant="outline"
          >
            Refresh
          </Button>
        </div>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-6 gap-4">
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-blue-600">{endpoints.length}</p>
            <p className="text-sm text-mono-gray-600">Total Endpoints</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-green-600">
              {endpoints.filter(e => e.is_active).length}
            </p>
            <p className="text-sm text-mono-gray-600">Active</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-yellow-600">
              {endpoints.filter(e => !e.is_active).length}
            </p>
            <p className="text-sm text-mono-gray-600">Inactive</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-purple-600">{deliveries.length}</p>
            <p className="text-sm text-mono-gray-600">Total Deliveries</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-green-600">
              {deliveries.filter(d => d.status === 'success').length}
            </p>
            <p className="text-sm text-mono-gray-600">Successful</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-red-600">
              {deliveries.filter(d => d.status === 'failed').length}
            </p>
            <p className="text-sm text-mono-gray-600">Failed</p>
          </div>
        </Card>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200">
        <nav className="-mb-px flex space-x-8">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === tab.id
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <div className="flex items-center gap-2">
                {tab.icon === 'LayoutDashboard' && (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                  </svg>
                )}
                {tab.icon === 'Globe' && (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9" />
                  </svg>
                )}
                {tab.icon === 'Send' && (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                  </svg>
                )}
                {tab.icon === 'Activity' && (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                  </svg>
                )}
                {tab.icon === 'Settings' && (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                )}
                {tab.label}
              </div>
            </button>
          ))}
        </nav>
      </div>

      {/* Tab Content */}
      <div className="min-h-[600px]">
        {activeTab === 'overview' && (
          <div className="space-y-6">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <WebhookHealthDashboard 
                data={health} 
                loading={healthLoading} 
                detailed={true}
              />
              <div className="space-y-4">
                <h3 className="text-lg font-semibold text-mono-gray-900">Recent Deliveries</h3>
                <Card className="p-6">
                  {deliveriesLoading ? (
                    <div className="flex justify-center py-8">
                      <Spinner size="md" />
                    </div>
                  ) : (
                    <div className="space-y-3">
                      {deliveries.slice(0, 5).map((delivery: WebhookDelivery) => (
                        <div key={delivery.id} className="flex items-center justify-between p-3 bg-gray-50 rounded">
                          <div className="flex items-center gap-3">
                            <div className={`w-2 h-2 rounded-full ${
                              delivery.status === 'success' ? 'bg-green-500' : 
                              delivery.status === 'failed' ? 'bg-red-500' : 'bg-yellow-500'
                            }`} />
                            <div>
                              <p className="text-sm font-medium text-mono-gray-900">{delivery.event_type}</p>
                              <p className="text-xs text-mono-gray-600">{delivery.created_at}</p>
                            </div>
                          </div>
                          <Badge 
                            variant={delivery.status === 'success' ? 'success' : 
                                   delivery.status === 'failed' ? 'error' : 'default'}
                          >
                            {delivery.status}
                          </Badge>
                        </div>
                      ))}
                    </div>
                  )}
                </Card>
              </div>
            </div>
          </div>
        )}

        {activeTab === 'endpoints' && (
          <WebhookList
            endpoints={endpoints}
            loading={endpointsLoading}
            onTest={handleTestEndpoint}
            onDelete={handleDeleteEndpoint}
            onSelect={handleViewEndpointDetails}
            onRefresh={handleRefresh}
          />
        )}

        {activeTab === 'deliveries' && (
          <WebhookDeliveryHistory
            deliveries={deliveries}
            loading={deliveriesLoading}
            onRetry={handleRetryDelivery}
            onRefresh={handleRefresh}
          />
        )}

        {activeTab === 'health' && (
          <WebhookHealthDashboard 
            data={health} 
            loading={healthLoading}
            detailed={true}
          />
        )}

        {activeTab === 'settings' && (
          <Card className="p-6">
            <h3 className="text-lg font-semibold text-mono-gray-900 mb-4">Webhook Settings</h3>
            <div className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="text-sm font-medium text-mono-gray-700">Default Timeout (seconds)</label>
                  <p className="text-sm text-mono-gray-900">30</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-mono-gray-700">Max Retries</label>
                  <p className="text-sm text-mono-gray-900">3</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-mono-gray-700">Retry Delay (seconds)</label>
                  <p className="text-sm text-mono-gray-900">60</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-mono-gray-700">Rate Limiting</label>
                  <p className="text-sm text-mono-gray-900">100 requests/hour</p>
                </div>
              </div>
            </div>
          </Card>
        )}
      </div>

      {/* Modals */}
      {showCreateModal && (
        <WebhookCreateModal
          onClose={() => setShowCreateModal(false)}
          onSubmit={handleCreateEndpoint}
          loading={createEndpointMutation.isPending}
        />
      )}

      {showEndpointDetails && selectedEndpoint && (
        <WebhookEndpointDetails
          endpoint={selectedEndpoint}
          onClose={() => {
            setShowEndpointDetails(false);
            setSelectedEndpoint(null);
          }}
        />
      )}
    </div>
  );
};
