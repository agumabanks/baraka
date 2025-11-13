import React, { useState } from 'react';
import { Card } from '../../ui/Card';
import { Button } from '../../ui/Button';
import { Input } from '../../ui/Input';
import { WebhookEndpointForm } from '../../../types/webhook';

interface WebhookCreateModalProps {
  onClose: () => void;
  onSubmit: (data: WebhookEndpointForm) => void;
  loading?: boolean;
}

const AVAILABLE_EVENTS = [
  { value: 'shipment.created', label: 'Shipment Created' },
  { value: 'shipment.updated', label: 'Shipment Updated' },
  { value: 'shipment.delivered', label: 'Shipment Delivered' },
  { value: 'shipment.failed', label: 'Shipment Failed' },
  { value: 'branch.created', label: 'Branch Created' },
  { value: 'branch.updated', label: 'Branch Updated' },
  { value: 'user.created', label: 'User Created' },
  { value: 'system.alert', label: 'System Alert' },
  { value: 'pricing.updated', label: 'Pricing Updated' },
  { value: 'contract.signed', label: 'Contract Signed' },
];

export const WebhookCreateModal: React.FC<WebhookCreateModalProps> = ({
  onClose,
  onSubmit,
  loading = false,
}) => {
  const [formData, setFormData] = useState<WebhookEndpointForm>({
    name: '',
    url: '',
    events: [],
    is_active: true,
  });
  const [errors, setErrors] = useState<Record<string, string>>({});

  const handleInputChange = (field: keyof WebhookEndpointForm, value: any) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: '' }));
    }
  };

  const handleEventToggle = (eventValue: string) => {
    setFormData(prev => ({
      ...prev,
      events: prev.events.includes(eventValue)
        ? prev.events.filter(e => e !== eventValue)
        : [...prev.events, eventValue]
    }));
  };

  const validateForm = (): boolean => {
    const newErrors: Record<string, string> = {};

    if (!formData.name.trim()) {
      newErrors.name = 'Name is required';
    }

    if (!formData.url.trim()) {
      newErrors.url = 'URL is required';
    } else if (!/^https?:\/\/.+/.test(formData.url)) {
      newErrors.url = 'URL must start with http:// or https://';
    }

    if (formData.events.length === 0) {
      newErrors.events = 'At least one event must be selected';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (validateForm()) {
      onSubmit(formData);
    }
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div className="p-6">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-2xl font-bold text-mono-gray-900">Create Webhook Endpoint</h2>
            <Button
              onClick={onClose}
              variant="ghost"
              size="sm"
            >
              ×
            </Button>
          </div>

          <form onSubmit={handleSubmit} className="space-y-6">
            {/* Name */}
            <div>
              <label className="block text-sm font-medium text-mono-gray-700 mb-2">
                Endpoint Name *
              </label>
              <Input
                value={formData.name}
                onChange={(e) => handleInputChange('name', e.target.value)}
                placeholder="e.g., Order Processing Webhook"
                error={errors.name}
              />
            </div>

            {/* URL */}
            <div>
              <label className="block text-sm font-medium text-mono-gray-700 mb-2">
                Target URL *
              </label>
              <Input
                value={formData.url}
                onChange={(e) => handleInputChange('url', e.target.value)}
                placeholder="https://your-app.com/webhooks/baraka"
                error={errors.url}
              />
              <p className="text-xs text-mono-gray-500 mt-1">
                This is where webhook payloads will be sent
              </p>
            </div>

            {/* Events */}
            <div>
              <label className="block text-sm font-medium text-mono-gray-700 mb-2">
                Events to Subscribe *
              </label>
              {errors.events && (
                <p className="text-sm text-red-600 mb-2">{errors.events}</p>
              )}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-48 overflow-y-auto">
                {AVAILABLE_EVENTS.map((event) => (
                  <label
                    key={event.value}
                    className="flex items-center space-x-2 p-2 hover:bg-gray-50 rounded cursor-pointer"
                  >
                    <input
                      type="checkbox"
                      checked={formData.events.includes(event.value)}
                      onChange={() => handleEventToggle(event.value)}
                      className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                    <span className="text-sm text-mono-gray-700">{event.label}</span>
                    <span className="text-xs text-mono-gray-500 bg-gray-100 px-2 py-1 rounded">
                      {event.value}
                    </span>
                  </label>
                ))}
              </div>
            </div>

            {/* Active Status */}
            <div>
              <label className="flex items-center space-x-2">
                <input
                  type="checkbox"
                  checked={formData.is_active}
                  onChange={(e) => handleInputChange('is_active', e.target.checked)}
                  className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span className="text-sm font-medium text-mono-gray-700">
                  Active (endpoint will receive webhooks immediately)
                </span>
              </label>
            </div>

            {/* Information */}
            <Card className="p-4 bg-blue-50 border-blue-200">
              <h3 className="text-sm font-medium text-blue-900 mb-2">Webhook Security</h3>
              <ul className="text-xs text-blue-800 space-y-1">
                <li>• All webhooks include a signature header for verification</li>
                <li>• Use the secret key to validate webhook authenticity</li>
                <li>• Failed webhooks are automatically retried with exponential backoff</li>
                <li>• Monitor delivery success in the webhook dashboard</li>
              </ul>
            </Card>

            {/* Actions */}
            <div className="flex justify-end space-x-3">
              <Button
                type="button"
                onClick={onClose}
                variant="outline"
                disabled={loading}
              >
                Cancel
              </Button>
              <Button
                type="submit"
                disabled={loading}
                className="bg-blue-600 hover:bg-blue-700 text-white"
              >
                {loading ? 'Creating...' : 'Create Endpoint'}
              </Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};