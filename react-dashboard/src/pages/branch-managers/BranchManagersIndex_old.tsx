import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Badge from '../../components/ui/Badge';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { branchManagersApi } from '../../services/api';
import type { BranchManager, BranchManagerListParams } from '../../types/branchManagers';

const BranchManagersIndex: React.FC = () => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [params, setParams] = useState<BranchManagerListParams>({
    page: 1,
    per_page: 15,
  });

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['branch-managers', params],
    queryFn: () => branchManagersApi.getManagers(params),
  });

  const deleteManagerMutation = useMutation({
    mutationFn: (managerId: number) => branchManagersApi.deleteManager(managerId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['branch-managers'] });
    },
  });

  const managers = data?.data?.managers || [];
  const meta = data?.data?.meta;

  const handleDelete = async (managerId: number, managerName: string) => {
    if (confirm(`Are you sure you want to delete manager "${managerName}"?`)) {
      try {
        await deleteManagerMutation.mutateAsync(managerId);
      } catch (error) {
        console.error('Failed to delete manager:', error);
        alert('Failed to delete manager. Please try again.');
      }
    }
  };

  const handleSearch = (searchTerm: string) => {
    setParams({ ...params, search: searchTerm, page: 1 });
  };

  const handlePageChange = (newPage: number) => {
    setParams({ ...params, page: newPage });
  };

  if (isLoading && !data) {
    return <LoadingSpinner message="Loading branch managers" />;
  }

  if (isError) {
    const message = error instanceof Error ? error.message : 'Unable to load branch managers';
    return (
      <div className="flex min-h-[400px] flex-col items-center justify-center">
        <Card className="max-w-md text-center">
          <div className="space-y-4">
            <div className="inline-flex h-16 w-16 items-center justify-center rounded-full bg-mono-black text-mono-white">
              <i className="fas fa-exclamation-triangle text-2xl" aria-hidden="true" />
            </div>
            <div>
              <h2 className="text-2xl font-semibold text-mono-black">Unable to load managers</h2>
              <p className="text-sm text-mono-gray-600">{message}</p>
            </div>
          </div>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <section className="rounded-3xl border border-mono-gray-200 bg-mono-white shadow-xl">
        <header className="flex flex-col gap-6 border-b border-mono-gray-200 px-8 py-10 xl:flex-row xl:items-center xl:justify-between">
          <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Branch Management
            </p>
            <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
              Branch Managers
            </h1>
            <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
              Manage branch managers, monitor performance, and track settlements across your network.
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <Button 
              variant="primary" 
              size="sm" 
              className="uppercase tracking-[0.25em]"
              onClick={() => navigate('/dashboard/branch-managers/create')}
            >
              <i className="fas fa-plus mr-2" aria-hidden="true" />
              Add Manager
            </Button>
          </div>
        </header>

        {/* Search and Filters */}
        <div className="border-b border-mono-gray-200 px-8 py-6">
          <div className="flex flex-wrap gap-4">
            <div className="flex-1 min-w-[250px]">
              <input
                type="text"
                placeholder="Search managers..."
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 text-sm focus:border-mono-black focus:outline-none"
                onChange={(e) => handleSearch(e.target.value)}
              />
            </div>
          </div>
        </div>

        {/* Managers Table */}
        <div className="px-8 py-8">
          {managers.length === 0 ? (
            <Card className="text-center">
              <div className="space-y-3">
                <h2 className="text-xl font-semibold text-mono-black">No managers found</h2>
                <p className="text-sm text-mono-gray-600">
                  Start by adding a branch manager to your network.
                </p>
                <Button 
                  variant="primary" 
                  size="sm"
                  onClick={() => navigate('/dashboard/branch-managers/create')}
                >
                  <i className="fas fa-plus mr-2" aria-hidden="true" />
                  Add First Manager
                </Button>
              </div>
            </Card>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-mono-gray-200 text-left text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                    <th className="pb-4">Manager</th>
                    <th className="pb-4">Business Name</th>
                    <th className="pb-4">Branch</th>
                    <th className="pb-4">Balance</th>
                    <th className="pb-4">Status</th>
                    <th className="pb-4 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {managers.map((manager: BranchManager) => (
                    <tr 
                      key={manager.id} 
                      className="border-b border-mono-gray-100 hover:bg-mono-gray-50 transition-colors"
                    >
                      <td className="py-4">
                        <div>
                          <p className="font-semibold text-mono-black">
                            {manager.user?.name || 'N/A'}
                          </p>
                          <p className="text-sm text-mono-gray-600">
                            {manager.user?.email || 'N/A'}
                          </p>
                        </div>
                      </td>
                      <td className="py-4">
                        <p className="text-mono-black">{manager.business_name}</p>
                      </td>
                      <td className="py-4">
                        <div>
                          <p className="font-medium text-mono-black">
                            {manager.branch?.name || 'N/A'}
                          </p>
                          <p className="text-sm text-mono-gray-600">
                            {manager.branch?.code || ''}
                          </p>
                        </div>
                      </td>
                      <td className="py-4">
                        <p className="font-mono text-mono-black">
                          ${manager.current_balance.toFixed(2)}
                        </p>
                      </td>
                      <td className="py-4">
                        {manager.status === 'active' ? (
                          <Badge variant="solid" size="sm" className="bg-green-600 text-white">
                            Active
                          </Badge>
                        ) : manager.status === 'inactive' ? (
                          <Badge variant="outline" size="sm">
                            Inactive
                          </Badge>
                        ) : (
                          <Badge variant="ghost" size="sm" className="text-red-600">
                            Suspended
                          </Badge>
                        )}
                      </td>
                      <td className="py-4 text-right">
                        <div className="flex justify-end gap-2">
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => navigate(`/dashboard/branch-managers/${manager.id}`)}
                          >
                            <i className="fas fa-eye" aria-hidden="true" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => navigate(`/dashboard/branch-managers/${manager.id}/edit`)}
                          >
                            <i className="fas fa-edit" aria-hidden="true" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleDelete(manager.id, manager.user?.name || manager.business_name)}
                          >
                            <i className="fas fa-trash text-red-600" aria-hidden="true" />
                          </Button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {/* Pagination */}
          {meta && meta.last_page > 1 && (
            <div className="mt-6 flex items-center justify-between">
              <p className="text-sm text-mono-gray-600">
                Page {meta.current_page} of {meta.last_page} ({meta.total} total)
              </p>
              <div className="flex gap-2">
                <Button
                  variant="secondary"
                  size="sm"
                  disabled={meta.current_page === 1}
                  onClick={() => handlePageChange(meta.current_page - 1)}
                >
                  Previous
                </Button>
                <Button
                  variant="secondary"
                  size="sm"
                  disabled={meta.current_page === meta.last_page}
                  onClick={() => handlePageChange(meta.current_page + 1)}
                >
                  Next
                </Button>
              </div>
            </div>
          )}
        </div>
      </section>
    </div>
  );
};

export default BranchManagersIndex;
