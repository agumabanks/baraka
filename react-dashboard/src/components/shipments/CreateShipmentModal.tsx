import React, { useState, useEffect } from 'react';
import { useMutation, useQueryClient, useQuery } from '@tanstack/react-query';
import Button from '../ui/Button';
import Input from '../ui/Input';
import api, { salesApi } from '../../services/api';
import type { SalesCustomer } from '../../types/sales';

interface CreateShipmentModalProps {
  isOpen: boolean;
  onClose: () => void;
}

// Using Sales customers as selectable accounts for shipments
type Customer = Pick<SalesCustomer, 'id' | 'name'> & { hub?: { id: number; name: string } | null };

interface Branch {
  id: number;
  name: string;
  code: string;
}

interface ShipmentFormData {
  client_id?: number;
  customer_id?: number;
  sender_name: string;
  sender_phone: string;
  sender_address: string;
  recipient_name: string;
  recipient_phone: string;
  recipient_address: string;
  origin_branch_id?: number;
  dest_branch_id?: number;
  weight?: number;
  pieces?: number;
  description?: string;
  service_level?: string;
  payment_method?: string;
  declared_value?: number;
  price_amount?: number;
}

interface NewClientData {
  business_name: string;
  primary_branch_id?: number;
  contact_name: string;
  contact_phone: string;
  contact_email: string;
  address: string;
}

const CreateShipmentModal: React.FC<CreateShipmentModalProps> = ({ isOpen, onClose }) => {
  const queryClient = useQueryClient();
  const [showNewClientForm, setShowNewClientForm] = useState(false);
  const [selectedCustomerId, setSelectedCustomerId] = useState<number | null>(null);
  const [formData, setFormData] = useState<ShipmentFormData>({
    sender_name: '',
    sender_phone: '',
    sender_address: '',
    recipient_name: '',
    recipient_phone: '',
    recipient_address: '',
    service_level: 'standard',
    payment_method: 'cash',
  });
  const [newClientData, setNewClientData] = useState<NewClientData>({
    business_name: '',
    contact_name: '',
    contact_phone: '',
    contact_email: '',
    address: '',
  });

  // Fetch customers (these are the selectable accounts for shipments)
  const { data: customersResp } = useQuery({
    queryKey: ['sales', 'customers', 'for-shipment'],
    queryFn: async () => {
      return await salesApi.getCustomers({ per_page: 100 });
    },
    enabled: isOpen,
  });

  // Fetch branches
  const { data: branchesData } = useQuery({
    queryKey: ['branches-list'],
    queryFn: async () => {
      const response = await api.get('/v10/branches');
      return response.data;
    },
    enabled: isOpen,
  });

  const customers: Customer[] = customersResp?.data?.items || [];
  const branches: Branch[] = branchesData?.data?.items || [];

  const createClientMutation = useMutation({
    mutationFn: async (data: NewClientData) => {
      const response = await api.post('/v10/shipments/clients', data);
      return response.data;
    },
    onSuccess: (response) => {
      queryClient.invalidateQueries({ queryKey: ['clients'] });
      setSelectedCustomerId(response.data.id);
      setShowNewClientForm(false);
      alert('Client created successfully!');
    },
    onError: (error: any) => {
      alert('Failed to create client: ' + (error.response?.data?.message || error.message));
    },
  });

  const createShipmentMutation = useMutation({
    mutationFn: async (data: ShipmentFormData) => {
      const response = await api.post('/v10/shipments', data);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['workflow-board'] });
      queryClient.invalidateQueries({ queryKey: ['operations-insights'] });
      queryClient.invalidateQueries({ queryKey: ['shipments'] });
      onClose();
      resetForm();
      alert('Shipment created successfully!');
    },
    onError: (error: any) => {
      alert('Failed to create shipment: ' + (error.response?.data?.message || error.message));
    },
  });

  useEffect(() => {
    if (selectedCustomerId) {
      setFormData(prev => ({ ...prev, customer_id: selectedCustomerId }));
    }
  }, [selectedCustomerId]);

  const handleChange = (field: keyof ShipmentFormData, value: any) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const handleClientChange = (field: keyof NewClientData, value: any) => {
    setNewClientData(prev => ({ ...prev, [field]: value }));
  };

  const handleShipmentSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.customer_id && !formData.client_id) {
      alert('Please select a client');
      return;
    }
    createShipmentMutation.mutate(formData);
  };

  const handleClientSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    createClientMutation.mutate(newClientData);
  };

  const resetForm = () => {
    setFormData({
      sender_name: '',
      sender_phone: '',
      sender_address: '',
      recipient_name: '',
      recipient_phone: '',
      recipient_address: '',
      service_level: 'standard',
      payment_method: 'cash',
    });
    setSelectedCustomerId(null);
    setShowNewClientForm(false);
    setNewClientData({
      business_name: '',
      contact_name: '',
      contact_phone: '',
      contact_email: '',
      address: '',
    });
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div className="bg-white rounded-3xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-y-auto m-4">
        <div className="sticky top-0 bg-white border-b border-mono-gray-200 px-8 py-6 rounded-t-3xl z-10">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-2xl font-semibold text-mono-black">
                {showNewClientForm ? 'Create New Client' : 'Create New Shipment'}
              </h2>
              <p className="text-sm text-mono-gray-600 mt-1">
                {showNewClientForm 
                  ? 'Enter client details to create a new customer'
                  : 'Select client and enter shipment details'}
              </p>
            </div>
            <button
              onClick={() => {
                onClose();
                resetForm();
              }}
              className="text-mono-gray-500 hover:text-mono-black transition-colors"
            >
              <i className="fas fa-times text-xl" aria-hidden="true" />
            </button>
          </div>
        </div>

        {showNewClientForm ? (
          // NEW CLIENT FORM
          <form onSubmit={handleClientSubmit} className="px-8 py-6 space-y-6">
            <div className="grid gap-4 md:grid-cols-2">
              <div className="md:col-span-2">
                <Input
                  label="Business Name *"
                  required
                  value={newClientData.business_name}
                  onChange={(e) => handleClientChange('business_name', e.target.value)}
                  placeholder="Enter business name"
                />
              </div>
              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-mono-black mb-2">
                  Primary Branch *
                </label>
                <select
                  required
                  className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black"
                  value={newClientData.primary_branch_id || ''}
                  onChange={(e) => handleClientChange('primary_branch_id', parseInt(e.target.value))}
                >
                  <option value="">Select primary branch</option>
                  {branches.map(branch => (
                    <option key={branch.id} value={branch.id}>
                      {branch.name} ({branch.code})
                    </option>
                  ))}
                </select>
              </div>
              <Input
                label="Contact Name *"
                required
                value={newClientData.contact_name}
                onChange={(e) => handleClientChange('contact_name', e.target.value)}
                placeholder="Enter contact person name"
              />
              <Input
                label="Contact Phone *"
                required
                value={newClientData.contact_phone}
                onChange={(e) => handleClientChange('contact_phone', e.target.value)}
                placeholder="Enter contact phone"
              />
              <Input
                label="Contact Email"
                type="email"
                value={newClientData.contact_email}
                onChange={(e) => handleClientChange('contact_email', e.target.value)}
                placeholder="Enter contact email"
              />
              <div className="md:col-span-2">
                <Input
                  label="Address *"
                  required
                  value={newClientData.address}
                  onChange={(e) => handleClientChange('address', e.target.value)}
                  placeholder="Enter business address"
                />
              </div>
            </div>

            <div className="flex items-center justify-between pt-6 border-t border-mono-gray-200">
              <Button
                type="button"
                variant="secondary"
                onClick={() => setShowNewClientForm(false)}
                disabled={createClientMutation.isPending}
              >
                <i className="fas fa-arrow-left mr-2" aria-hidden="true" />
                Back to Shipment
              </Button>
              <Button
                type="submit"
                variant="primary"
                disabled={createClientMutation.isPending}
              >
                {createClientMutation.isPending ? (
                  <>
                    <i className="fas fa-spinner fa-spin mr-2" aria-hidden="true" />
                    Creating...
                  </>
                ) : (
                  <>
                    <i className="fas fa-check mr-2" aria-hidden="true" />
                    Create Client
                  </>
                )}
              </Button>
            </div>
          </form>
        ) : (
          // SHIPMENT FORM
          <form onSubmit={handleShipmentSubmit} className="px-8 py-6 space-y-8">
            {/* Client Selection */}
            <div>
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-semibold text-mono-black">Client Selection</h3>
                <Button
                  type="button"
                  variant="secondary"
                  size="sm"
                  onClick={() => setShowNewClientForm(true)}
                >
                  <i className="fas fa-plus mr-2" aria-hidden="true" />
                  New Client
                </Button>
              </div>
              <select
                required
                className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black"
                value={selectedCustomerId || ''}
                onChange={(e) => setSelectedCustomerId(parseInt(e.target.value))}
              >
                <option value="">Select a client *</option>
                {customers.map(customer => (
                  <option key={customer.id} value={customer.id}>
                    {customer.name}
                    {customer.hub && ` - ${customer.hub.name}`}
                  </option>
                ))}
              </select>
            </div>

            {/* Branch Selection */}
            <div>
              <h3 className="text-lg font-semibold text-mono-black mb-4">Route Information</h3>
              <div className="grid gap-4 md:grid-cols-2">
                <div>
                  <label className="block text-sm font-medium text-mono-black mb-2">
                    Origin Branch *
                  </label>
                  <select
                    required
                    className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black"
                    value={formData.origin_branch_id || ''}
                    onChange={(e) => handleChange('origin_branch_id', parseInt(e.target.value))}
                  >
                    <option value="">Select origin branch</option>
                    {branches.map(branch => (
                      <option key={branch.id} value={branch.id}>
                        {branch.name} ({branch.code})
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-mono-black mb-2">
                    Destination Branch *
                  </label>
                  <select
                    required
                    className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black"
                    value={formData.dest_branch_id || ''}
                    onChange={(e) => handleChange('dest_branch_id', parseInt(e.target.value))}
                  >
                    <option value="">Select destination branch</option>
                    {branches.map(branch => (
                      <option key={branch.id} value={branch.id}>
                        {branch.name} ({branch.code})
                      </option>
                    ))}
                  </select>
                </div>
              </div>
            </div>

            {/* Sender Information */}
            <div>
              <h3 className="text-lg font-semibold text-mono-black mb-4">Sender Information</h3>
              <div className="grid gap-4 md:grid-cols-2">
                <Input
                  label="Sender Name *"
                  required
                  value={formData.sender_name}
                  onChange={(e) => handleChange('sender_name', e.target.value)}
                  placeholder="Enter sender name"
                />
                <Input
                  label="Sender Phone *"
                  required
                  value={formData.sender_phone}
                  onChange={(e) => handleChange('sender_phone', e.target.value)}
                  placeholder="Enter phone number"
                />
                <div className="md:col-span-2">
                  <Input
                    label="Sender Address *"
                    required
                    value={formData.sender_address}
                    onChange={(e) => handleChange('sender_address', e.target.value)}
                    placeholder="Enter full address"
                  />
                </div>
              </div>
            </div>

            {/* Recipient Information */}
            <div>
              <h3 className="text-lg font-semibold text-mono-black mb-4">Recipient Information</h3>
              <div className="grid gap-4 md:grid-cols-2">
                <Input
                  label="Recipient Name *"
                  required
                  value={formData.recipient_name}
                  onChange={(e) => handleChange('recipient_name', e.target.value)}
                  placeholder="Enter recipient name"
                />
                <Input
                  label="Recipient Phone *"
                  required
                  value={formData.recipient_phone}
                  onChange={(e) => handleChange('recipient_phone', e.target.value)}
                  placeholder="Enter phone number"
                />
                <div className="md:col-span-2">
                  <Input
                    label="Recipient Address *"
                    required
                    value={formData.recipient_address}
                    onChange={(e) => handleChange('recipient_address', e.target.value)}
                    placeholder="Enter full address"
                  />
                </div>
              </div>
            </div>

            {/* Shipment Details */}
            <div>
              <h3 className="text-lg font-semibold text-mono-black mb-4">Shipment Details</h3>
              <div className="grid gap-4 md:grid-cols-2">
                <Input
                  label="Weight (kg)"
                  type="number"
                  step="0.01"
                  value={formData.weight || ''}
                  onChange={(e) => handleChange('weight', parseFloat(e.target.value) || undefined)}
                  placeholder="Enter weight"
                />
                <Input
                  label="Number of Pieces"
                  type="number"
                  value={formData.pieces || ''}
                  onChange={(e) => handleChange('pieces', parseInt(e.target.value) || undefined)}
                  placeholder="Enter number of pieces"
                />
                <div className="md:col-span-2">
                  <Input
                    label="Description"
                    value={formData.description || ''}
                    onChange={(e) => handleChange('description', e.target.value)}
                    placeholder="Brief description of contents"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-mono-black mb-2">
                    Service Level *
                  </label>
                  <select
                    className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black"
                    value={formData.service_level}
                    onChange={(e) => handleChange('service_level', e.target.value)}
                  >
                    <option value="standard">Standard Delivery</option>
                    <option value="express">Express Delivery</option>
                    <option value="same_day">Same Day Delivery</option>
                    <option value="overnight">Overnight Delivery</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-mono-black mb-2">
                    Payment Method *
                  </label>
                  <select
                    className="w-full px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black"
                    value={formData.payment_method}
                    onChange={(e) => handleChange('payment_method', e.target.value)}
                  >
                    <option value="cash">Cash on Delivery</option>
                    <option value="prepaid">Prepaid</option>
                    <option value="credit">Credit Account</option>
                  </select>
                </div>
                <Input
                  label="Declared Value (UGX)"
                  type="number"
                  step="0.01"
                  value={formData.declared_value || ''}
                  onChange={(e) => handleChange('declared_value', parseFloat(e.target.value) || undefined)}
                  placeholder="Enter declared value"
                />
                <Input
                  label="Price Amount (UGX)"
                  type="number"
                  step="0.01"
                  value={formData.price_amount || ''}
                  onChange={(e) => handleChange('price_amount', parseFloat(e.target.value) || undefined)}
                  placeholder="Enter price amount"
                />
              </div>
            </div>

            {/* Actions */}
            <div className="flex items-center justify-end gap-4 pt-6 border-t border-mono-gray-200">
              <Button
                type="button"
                variant="secondary"
                onClick={() => {
                  onClose();
                  resetForm();
                }}
                disabled={createShipmentMutation.isPending}
              >
                Cancel
              </Button>
              <Button
                type="submit"
                variant="primary"
                disabled={createShipmentMutation.isPending || !selectedCustomerId}
              >
                {createShipmentMutation.isPending ? (
                  <>
                    <i className="fas fa-spinner fa-spin mr-2" aria-hidden="true" />
                    Creating...
                  </>
                ) : (
                  <>
                    <i className="fas fa-plus mr-2" aria-hidden="true" />
                    Create Shipment
                  </>
                )}
              </Button>
            </div>
          </form>
        )}
      </div>
    </div>
  );
};

export default CreateShipmentModal;
