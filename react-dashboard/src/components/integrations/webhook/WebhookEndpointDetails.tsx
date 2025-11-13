import React from 'react';
import Card from '../../ui/Card';
import Button from '../../ui/Button';
import Badge from '../../ui/Badge';
import { WebhookEndpoint } from '@/types/webhook';

interface WebhookEndpointDetailsProps {
  endpoint: WebhookEndpoint;
  onClose: () => void;
}

export const WebhookEndpointDetails: React.FC<WebhookEndpointDetailsProps> = ({ endpoint, onClose }) => {
  return (
    <div className="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div className="w-full max-w-3xl">
        <Card className="p-0 overflow-hidden">
          <div className="flex items-center justify-between px-6 py-4 border-b border-mono-gray-200 bg-mono-gray-50">
            <div>
              <p className="text-sm text-mono-gray-500">Webhook Endpoint</p>
              <h2 className="text-2xl font-semibold text-mono-gray-900">{endpoint.name}</h2>
            </div>
            <div className="flex items-center gap-3">
              <Badge variant={endpoint.is_active || endpoint.active ? 'success' : 'error'}>
                {endpoint.is_active || endpoint.active ? 'Active' : 'Inactive'}
              </Badge>
              <Button variant="outline" onClick={onClose}>
                Close
              </Button>
            </div>
          </div>

          <div className="p-6 space-y-6">
            <section>
              <h3 className="text-sm font-semibold text-mono-gray-700 uppercase tracking-wide">Endpoint Details</h3>
              <div className="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                  <p className="text-mono-gray-500">URL</p>
                  <p className="font-medium break-all">{endpoint.url}</p>
                </div>
                <div>
                  <p className="text-mono-gray-500">Subscribed Events</p>
                  <div className="flex flex-wrap gap-2 mt-1">
                    {endpoint.events?.map(event => (
                      <Badge key={event} variant="outline" size="sm">
                        {event}
                      </Badge>
                    ))}
                  </div>
                </div>
                <div>
                  <p className="text-mono-gray-500">Secret Key</p>
                  <p className="font-mono text-sm bg-mono-gray-100 px-2 py-1 rounded">
                    {endpoint.secret_key?.slice(0, 6)}••••{endpoint.secret_key?.slice(-4)}
                  </p>
                </div>
                <div>
                  <p className="text-mono-gray-500">Created</p>
                  <p className="font-medium">{new Date(endpoint.created_at).toLocaleString()}</p>
                </div>
                <div>
                  <p className="text-mono-gray-500">Last Updated</p>
                  <p className="font-medium">{endpoint.updated_at ? new Date(endpoint.updated_at).toLocaleString() : '—'}</p>
                </div>
                <div>
                  <p className="text-mono-gray-500">Last Triggered</p>
                  <p className="font-medium">{endpoint.last_triggered_at ? new Date(endpoint.last_triggered_at).toLocaleString() : '—'}</p>
                </div>
              </div>
            </section>

            <section>
              <h3 className="text-sm font-semibold text-mono-gray-700 uppercase tracking-wide">Retry Policy</h3>
              <div className="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                <div>
                  <p className="text-mono-gray-500">Max Attempts</p>
                  <p className="font-medium">{endpoint.retry_policy?.max_attempts ?? 5}</p>
                </div>
                <div>
                  <p className="text-mono-gray-500">Initial Delay</p>
                  <p className="font-medium">{endpoint.retry_policy?.initial_delay ?? 60}s</p>
                </div>
                <div>
                  <p className="text-mono-gray-500">Backoff Multiplier</p>
                  <p className="font-medium">{endpoint.retry_policy?.backoff_multiplier ?? 2}x</p>
                </div>
                <div>
                  <p className="text-mono-gray-500">Max Delay</p>
                  <p className="font-medium">{endpoint.retry_policy?.max_delay ?? 3600}s</p>
                </div>
              </div>
            </section>

            {endpoint.last_delivery_at && (
              <section>
                <h3 className="text-sm font-semibold text-mono-gray-700 uppercase tracking-wide">Delivery Activity</h3>
                <div className="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                  <div>
                    <p className="text-mono-gray-500">Last Delivery</p>
                    <p className="font-medium">{new Date(endpoint.last_delivery_at).toLocaleString()}</p>
                  </div>
                  <div>
                    <p className="text-mono-gray-500">Failure Count</p>
                    <p className="font-medium">{endpoint.failure_count ?? 0}</p>
                  </div>
                  <div>
                    <p className="text-mono-gray-500">Status</p>
                    <Badge variant={(endpoint.failure_count ?? 0) > 0 ? 'warning' : 'success'}>
                      {(endpoint.failure_count ?? 0) > 0 ? 'Attention' : 'Healthy'}
                    </Badge>
                  </div>
                </div>
              </section>
            )}
          </div>
        </Card>
      </div>
    </div>
  );
};
