import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import Button from '../../components/ui/Button';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { branchManagersApi } from '../../services/api';
import type { BranchManagerFormData } from '../../types/branchManagers';

const BranchManagerCreate: React.FC = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState<BranchManagerFormData>({
    branch_id: '',
    user_id: '',
    business_name: '',
    status: 'active',
  });

  const { data: availableBranchesData, isLoading: loadingBranches } = useQuery({
    queryKey: ['available-branches'],
    queryFn: () => branchManagersApi.getAvailableBranches(),
  });

  const createMutation = useMutation({
    mutationFn: (data: BranchManagerFormData) => branchManagersApi.createManager(data),
    onSuccess: () => {
      navigate('/dashboard/branch-managers');
    },
    onError: (error: any) => {
      console.error('Failed to create manager:', error);
      alert(error?.response?.data?.message || 'Failed to create manager. Please try again.');
    },
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!formData.branch_id || !formData.user_id || !formData.business_name) {
      alert('Please fill in all required fields');
      return;
    }

    createMutation.mutate(formData);
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  if (loadingBranches) {
    return <LoadingSpinner message="Loading form data" />;
  }

  const availableBranches = availableBranchesData?.data?.branches || [];

  return (
    <div className="space-y-6">
      <section className="rounded-3xl border border-mono-gray-200 bg-mono-white shadow-xl">
        <header className="flex flex-col gap-6 border-b border-mono-gray-200 px-8 py-10">
          <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Branch Management
            </p>
            <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
              Create Branch Manager
            </h1>
            <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
              Assign a new manager to a branch. The manager will be responsible for overseeing operations at the branch.
            </p>
          </div>
        </header>

        <form onSubmit={handleSubmit} className="px-8 py-8">
          <div className="max-w-2xl space-y-6">
            {/* Branch Selection */}
            <div>
              <label className="block text-sm font-medium text-mono-black mb-2">
                Branch <span className="text-red-600">*</span>
              </label>
              <select
                name="branch_id"
                value={formData.branch_id}
                onChange={handleChange}
                required
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 text-sm focus:border-mono-black focus:outline-none"
              >
                <option value="">Select a branch</option>
                {availableBranches.map((branch: any) => (
                  <option key={branch.value} value={branch.value}>
                    {branch.label} ({branch.code})
                  </option>
                ))}
              </select>
              <p className="mt-1 text-xs text-mono-gray-600">
                Select the branch this manager will oversee
              </p>
            </div>

            {/* User ID (for now, input field - can be enhanced with dropdown) */}
            <div>
              <label className="block text-sm font-medium text-mono-black mb-2">
                User ID <span className="text-red-600">*</span>
              </label>
              <input
                type="number"
                name="user_id"
                value={formData.user_id}
                onChange={handleChange}
                required
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 text-sm focus:border-mono-black focus:outline-none"
                placeholder="Enter user ID"
              />
              <p className="mt-1 text-xs text-mono-gray-600">
                The user account ID for this manager
              </p>
            </div>

            {/* Business Name */}
            <div>
              <label className="block text-sm font-medium text-mono-black mb-2">
                Business Name <span className="text-red-600">*</span>
              </label>
              <input
                type="text"
                name="business_name"
                value={formData.business_name}
                onChange={handleChange}
                required
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 text-sm focus:border-mono-black focus:outline-none"
                placeholder="Enter business name"
              />
              <p className="mt-1 text-xs text-mono-gray-600">
                The name of the business entity
              </p>
            </div>

            {/* Status */}
            <div>
              <label className="block text-sm font-medium text-mono-black mb-2">
                Status <span className="text-red-600">*</span>
              </label>
              <select
                name="status"
                value={formData.status}
                onChange={handleChange}
                required
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 text-sm focus:border-mono-black focus:outline-none"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>

            {/* Form Actions */}
            <div className="flex gap-3 pt-4">
              <Button
                type="submit"
                variant="primary"
                disabled={createMutation.isPending}
              >
                {createMutation.isPending ? (
                  <>
                    <i className="fas fa-spinner fa-spin mr-2" aria-hidden="true" />
                    Creating...
                  </>
                ) : (
                  <>
                    <i className="fas fa-check mr-2" aria-hidden="true" />
                    Create Manager
                  </>
                )}
              </Button>
              <Button
                type="button"
                variant="secondary"
                onClick={() => navigate('/dashboard/branch-managers')}
                disabled={createMutation.isPending}
              >
                Cancel
              </Button>
            </div>
          </div>
        </form>
      </section>
    </div>
  );
};

export default BranchManagerCreate;
