import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import Button from '../../components/ui/Button';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { branchWorkersApi } from '../../services/api';
import type { BranchWorkerFormData } from '../../types/branchWorkers';

const BranchWorkerEdit: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  
  const [formData, setFormData] = useState<BranchWorkerFormData>({
    branch_id: '',
    user_id: '',
    role: '',
    status: 'active',
  });

  const { data: workerData, isLoading } = useQuery({
    queryKey: ['branch-worker', id],
    queryFn: () => branchWorkersApi.getWorker(id!),
    enabled: !!id,
  });

  useEffect(() => {
    if (workerData?.data?.worker) {
      const worker = workerData.data.worker;
      setFormData({
        branch_id: worker.branch_id,
        user_id: worker.user_id,
        role: worker.role,
        status: worker.status,
      });
    }
  }, [workerData]);

  const updateMutation = useMutation({
    mutationFn: (data: BranchWorkerFormData) => branchWorkersApi.updateWorker(id!, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['branch-worker', id] });
      navigate(`/dashboard/branch-workers/${id}`);
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateMutation.mutate(formData);
  };

  if (isLoading) return <LoadingSpinner message="Loading" />;

  return (
    <div className="space-y-6">
      <section className="rounded-3xl border border-mono-gray-200 bg-mono-white shadow-xl">
        <header className="border-b border-mono-gray-200 px-8 py-10">
          <h1 className="text-3xl font-semibold text-mono-black">Edit Branch Worker</h1>
        </header>
        <form onSubmit={handleSubmit} className="px-8 py-8">
          <div className="max-w-2xl space-y-6">
            <div>
              <label className="block text-sm font-medium mb-2">Role *</label>
              <input
                type="text"
                name="role"
                value={formData.role}
                onChange={(e) => setFormData({...formData, role: e.target.value})}
                required
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2"
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
              <Button type="submit" variant="primary" disabled={updateMutation.isPending}>
                {updateMutation.isPending ? 'Updating...' : 'Update Worker'}
              </Button>
              <Button type="button" variant="secondary" onClick={() => navigate(`/dashboard/branch-workers/${id}`)}>
                Cancel
              </Button>
            </div>
          </div>
        </form>
      </section>
    </div>
  );
};

export default BranchWorkerEdit;
