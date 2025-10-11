import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import Button from '../../components/ui/Button';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { branchManagersApi } from '../../services/api';
import type { BranchManagerFormData } from '../../types/branchManagers';

const BranchManagerEdit: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  
  const [formData, setFormData] = useState<BranchManagerFormData>({
    branch_id: '',
    user_id: '',
    business_name: '',
    status: 'active',
  });

  const { data: managerData, isLoading: loadingManager } = useQuery({
    queryKey: ['branch-manager', id],
    queryFn: () => branchManagersApi.getManager(id!),
    enabled: !!id,
  });

  const { data: availableBranchesData, isLoading: loadingBranches } = useQuery({
    queryKey: ['available-branches'],
    queryFn: () => branchManagersApi.getAvailableBranches(),
  });

  useEffect(() => {
    if (managerData?.data?.manager) {
      const manager = managerData.data.manager;
      setFormData({
        branch_id: manager.branch_id,
        user_id: manager.user_id,
        business_name: manager.business_name,
        status: manager.status,
      });
    }
  }, [managerData]);

  const updateMutation = useMutation({
    mutationFn: (data: BranchManagerFormData) => branchManagersApi.updateManager(id!, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['branch-manager', id] });
      queryClient.invalidateQueries({ queryKey: ['branch-managers'] });
      navigate(`/dashboard/branch-managers/${id}`);
    },
    onError: (error: any) => {
      console.error('Failed to update manager:', error);
      alert(error?.response?.data?.message || 'Failed to update manager. Please try again.');
    },
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!formData.branch_id || !formData.user_id || !formData.business_name) {
      alert('Please fill in all required fields');
      return;
    }

    updateMutation.mutate(formData);
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  if (loadingManager || loadingBranches) {
    return <LoadingSpinner message="Loading form data" />;
  }

  const availableBranches = availableBranchesData?.data?.branches || [];
  const manager = managerData?.data?.manager;

  return (
    <div className="space-y-6">
      <section className="rounded-3xl border border-mono-gray-200 bg-mono-white shadow-xl">
        <header className="flex flex-col gap-6 border-b border-mono-gray-200 px-8 py-10">
          <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Branch Management
            </p>
            <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
              Edit Branch Manager
            </h1>
            <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
              Update manager details for {manager?.user?.name || 'this manager'}.
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
                {/* Include current branch */}
                {manager?.branch && (
                  <option value={manager.branch_id}>
                    {manager.branch.name} ({manager.branch.code})
                  </option>
                )}
                {availableBranches.filter((b: any) => b.value !== manager?.branch_id).map((branch: any) => (
                  <option key={branch.value} value={branch.value}>
                    {branch.label} ({branch.code})
                  </option>
                ))}
              </select>
            </div>

            {/* User ID */}
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
                disabled={updateMutation.isPending}
              >
                {updateMutation.isPending ? (
                  <>
                    <i className="fas fa-spinner fa-spin mr-2" aria-hidden="true" />
                    Updating...
                  </>
                ) : (
                  <>
                    <i className="fas fa-check mr-2" aria-hidden="true" />
                    Update Manager
                  </>
                )}
              </Button>
              <Button
                type="button"
                variant="secondary"
                onClick={() => navigate(`/dashboard/branch-managers/${id}`)}
                disabled={updateMutation.isPending}
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

export default BranchManagerEdit;
