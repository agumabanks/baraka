import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import Button from '../../components/ui/Button';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { branchWorkersApi } from '../../services/api';
import type { BranchWorkerFormData } from '../../types/branchWorkers';

const BranchWorkerCreate: React.FC = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState<BranchWorkerFormData>({
    branch_id: '',
    user_id: '',
    role: '',
    status: 'active',
  });

  const { data: resourcesData, isLoading } = useQuery({
    queryKey: ['available-worker-resources'],
    queryFn: () => branchWorkersApi.getAvailableResources(),
  });

  const createMutation = useMutation({
    mutationFn: (data: BranchWorkerFormData) => branchWorkersApi.createWorker(data),
    onSuccess: () => navigate('/dashboard/branch-workers'),
    onError: (error: any) => alert(error?.response?.data?.message || 'Failed to create worker'),
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.branch_id || !formData.user_id || !formData.role) {
      alert('Please fill in all required fields');
      return;
    }
    createMutation.mutate(formData);
  };

  if (isLoading) return <LoadingSpinner message="Loading form data" />;

  const branches = resourcesData?.data?.branches || [];
  const users = resourcesData?.data?.users || [];

  return (
    <div className="space-y-6">
      <section className="rounded-3xl border border-mono-gray-200 bg-mono-white shadow-xl">
        <header className="border-b border-mono-gray-200 px-8 py-10">
          <h1 className="text-3xl font-semibold text-mono-black">Create Branch Worker</h1>
        </header>
        <form onSubmit={handleSubmit} className="px-8 py-8">
          <div className="max-w-2xl space-y-6">
            <div>
              <label className="block text-sm font-medium mb-2">Branch *</label>
              <select
                name="branch_id"
                value={formData.branch_id}
                onChange={(e) => setFormData({...formData, branch_id: e.target.value})}
                required
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2"
              >
                <option value="">Select a branch</option>
                {branches.map((branch: any) => (
                  <option key={branch.value} value={branch.value}>{branch.label}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium mb-2">User *</label>
              <select
                name="user_id"
                value={formData.user_id}
                onChange={(e) => setFormData({...formData, user_id: e.target.value})}
                required
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2"
              >
                <option value="">Select a user</option>
                {users.map((user: any) => (
                  <option key={user.value} value={user.value}>{user.label}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium mb-2">Role *</label>
              <input
                type="text"
                name="role"
                value={formData.role}
                onChange={(e) => setFormData({...formData, role: e.target.value})}
                required
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2"
                placeholder="e.g., delivery, sorting, customer_service"
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-2">Status *</label>
              <select
                name="status"
                value={formData.status}
                onChange={(e) => setFormData({...formData, status: e.target.value})}
                required
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div className="flex gap-3">
              <Button type="submit" variant="primary" disabled={createMutation.isPending}>
                {createMutation.isPending ? 'Creating...' : 'Create Worker'}
              </Button>
              <Button type="button" variant="secondary" onClick={() => navigate('/dashboard/branch-workers')}>
                Cancel
              </Button>
            </div>
          </div>
        </form>
      </section>
    </div>
  );
};

export default BranchWorkerCreate;
