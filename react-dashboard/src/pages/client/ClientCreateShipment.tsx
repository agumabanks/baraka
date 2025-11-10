import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Button from '../../components/ui/Button';
import api from '../../services/api';

interface Branch {
  id: number;
  name: string;
  code: string;
  type: string;
  city?: string;
  country?: string;
}

export const ClientCreateShipment: React.FC = () => {
  const navigate = useNavigate();
  const [branches, setBranches] = useState<Branch[]>([]);
  const [formData, setFormData] = useState({
    origin_branch_id: '',
    dest_branch_id: '',
    service_level: 'standard',
    incoterm: 'DDU',
    sender_name: '',
    sender_phone: '',
    sender_address: '',
    receiver_name: '',
    receiver_phone: '',
    receiver_address: '',
    weight: '',
    length: '',
    width: '',
    height: '',
    description: '',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    const token = localStorage.getItem('client_token');
    if (!token) {
      navigate('/client/login');
      return;
    }
    fetchBranches();
  }, []);

  const fetchBranches = async () => {
    try {
      const token = localStorage.getItem('client_token');
      const response = await api.get('/v10/customer/branches', {
        headers: { Authorization: `Bearer ${token}` },
      });
      
      if (response.data.success) {
        setBranches(response.data.data?.branches || []);
      }
    } catch (err) {
      console.error('Failed to fetch branches', err);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const token = localStorage.getItem('client_token');
      const response = await api.post(
        '/v10/customer/shipments',
        {
          origin_branch_id: parseInt(formData.origin_branch_id),
          dest_branch_id: parseInt(formData.dest_branch_id),
          service_level: formData.service_level,
          incoterm: formData.incoterm,
          metadata: {
            sender: {
              name: formData.sender_name,
              phone: formData.sender_phone,
              address: formData.sender_address,
            },
            receiver: {
              name: formData.receiver_name,
              phone: formData.receiver_phone,
              address: formData.receiver_address,
            },
            package: {
              weight: parseFloat(formData.weight),
              dimensions: {
                length: parseFloat(formData.length),
                width: parseFloat(formData.width),
                height: parseFloat(formData.height),
              },
              description: formData.description,
            },
          },
        },
        {
          headers: { Authorization: `Bearer ${token}` },
        }
      );

      if (response.data.success) {
        navigate('/client/dashboard');
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to create shipment');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
          <h1 className="text-2xl font-bold text-gray-900">Create Shipment</h1>
          <Button variant="secondary" onClick={() => navigate('/client/dashboard')}>
            Back to Dashboard
          </Button>
        </div>
      </header>

      <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {error && (
          <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="bg-white shadow rounded-lg">
          {/* Branch Selection */}
          <div className="p-6 border-b border-gray-200">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Route Information</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Origin Branch *
                </label>
                <select
                  value={formData.origin_branch_id}
                  onChange={(e) =>
                    setFormData({ ...formData, origin_branch_id: e.target.value })
                  }
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  required
                >
                  <option value="">Select origin branch</option>
                  {branches.map((branch) => (
                    <option key={branch.id} value={branch.id}>
                      {branch.name} ({branch.city}, {branch.country})
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Destination Branch *
                </label>
                <select
                  value={formData.dest_branch_id}
                  onChange={(e) =>
                    setFormData({ ...formData, dest_branch_id: e.target.value })
                  }
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  required
                >
                  <option value="">Select destination branch</option>
                  {branches.map((branch) => (
                    <option key={branch.id} value={branch.id}>
                      {branch.name} ({branch.city}, {branch.country})
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Service Level *
                </label>
                <select
                  value={formData.service_level}
                  onChange={(e) => setFormData({ ...formData, service_level: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  required
                >
                  <option value="standard">Standard</option>
                  <option value="express">Express</option>
                  <option value="overnight">Overnight</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Incoterm *
                </label>
                <select
                  value={formData.incoterm}
                  onChange={(e) => setFormData({ ...formData, incoterm: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  required
                >
                  <option value="DDU">DDU - Delivered Duty Unpaid</option>
                  <option value="DDP">DDP - Delivered Duty Paid</option>
                  <option value="FOB">FOB - Free On Board</option>
                </select>
              </div>
            </div>
          </div>

          {/* Sender Information */}
          <div className="p-6 border-b border-gray-200">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Sender Information</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Sender Name *
                </label>
                <input
                  type="text"
                  value={formData.sender_name}
                  onChange={(e) => setFormData({ ...formData, sender_name: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Sender Phone *
                </label>
                <input
                  type="tel"
                  value={formData.sender_phone}
                  onChange={(e) => setFormData({ ...formData, sender_phone: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  required
                />
              </div>

              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Sender Address *
                </label>
                <textarea
                  value={formData.sender_address}
                  onChange={(e) => setFormData({ ...formData, sender_address: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  rows={2}
                  required
                />
              </div>
            </div>
          </div>

          {/* Receiver Information */}
          <div className="p-6 border-b border-gray-200">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Receiver Information</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Receiver Name *
                </label>
                <input
                  type="text"
                  value={formData.receiver_name}
                  onChange={(e) => setFormData({ ...formData, receiver_name: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Receiver Phone *
                </label>
                <input
                  type="tel"
                  value={formData.receiver_phone}
                  onChange={(e) => setFormData({ ...formData, receiver_phone: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  required
                />
              </div>

              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Receiver Address *
                </label>
                <textarea
                  value={formData.receiver_address}
                  onChange={(e) =>
                    setFormData({ ...formData, receiver_address: e.target.value })
                  }
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  rows={2}
                  required
                />
              </div>
            </div>
          </div>

          {/* Package Information */}
          <div className="p-6 border-b border-gray-200">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Package Information</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Weight (kg) *
                </label>
                <input
                  type="number"
                  step="0.01"
                  value={formData.weight}
                  onChange={(e) => setFormData({ ...formData, weight: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Length (cm) *
                </label>
                <input
                  type="number"
                  step="0.01"
                  value={formData.length}
                  onChange={(e) => setFormData({ ...formData, length: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Width (cm) *
                </label>
                <input
                  type="number"
                  step="0.01"
                  value={formData.width}
                  onChange={(e) => setFormData({ ...formData, width: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Height (cm) *
                </label>
                <input
                  type="number"
                  step="0.01"
                  value={formData.height}
                  onChange={(e) => setFormData({ ...formData, height: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  required
                />
              </div>

              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Package Description *
                </label>
                <textarea
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  rows={3}
                  placeholder="Describe the contents of your package"
                  required
                />
              </div>
            </div>
          </div>

          {/* Submit */}
          <div className="p-6 flex justify-end gap-4">
            <Button
              type="button"
              variant="secondary"
              onClick={() => navigate('/client/dashboard')}
            >
              Cancel
            </Button>
            <Button type="submit" disabled={loading}>
              {loading ? 'Creating...' : 'Create Shipment'}
            </Button>
          </div>
        </form>
      </main>
    </div>
  );
};

export default ClientCreateShipment;
