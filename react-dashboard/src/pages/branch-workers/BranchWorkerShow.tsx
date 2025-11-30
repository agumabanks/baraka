import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Badge from '../../components/ui/Badge';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { branchWorkersApi } from '../../services/api';

const BranchWorkerShow: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();

  const { data, isLoading, isError } = useQuery({
    queryKey: ['branch-worker', id],
    queryFn: () => branchWorkersApi.getWorker(id!),
    enabled: !!id,
  });

  if (isLoading) return <LoadingSpinner message="Loading worker details" />;
  if (isError) return <div>Error loading worker</div>;

  const worker = data?.data?.worker;
  if (!worker) return null;

  return (
    <div className="space-y-6">
      <section className="rounded-3xl border border-mono-gray-200 bg-mono-white shadow-xl">
        <header className="flex flex-col gap-6 border-b border-mono-gray-200 px-8 py-10 xl:flex-row xl:items-center xl:justify-between">
          <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Branch Worker</p>
            <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">{worker.user?.name || 'Worker Details'}</h1>
          </div>
          <div className="flex gap-3">
            <Button variant="secondary" size="sm" onClick={() => navigate(`/admin/dashboard/branch-workers/${id}/edit`)}>
              <i className="fas fa-edit mr-2" />Edit
            </Button>
            <Button variant="primary" size="sm" onClick={() => navigate('/admin/dashboard/branch-workers')}>
              <i className="fas fa-arrow-left mr-2" />Back
            </Button>
          </div>
        </header>
        <div className="px-8 py-8">
          <Card className="border border-mono-gray-200">
            <div className="space-y-4">
              <div>
                <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Name</p>
                <p className="text-mono-black">{worker.user?.name || 'N/A'}</p>
              </div>
              <div>
                <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Branch</p>
                <p className="text-mono-black">{worker.branch?.name || 'N/A'}</p>
              </div>
              <div>
                <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Role</p>
                <p className="text-mono-black">{worker.role}</p>
              </div>
              <div>
                <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Status</p>
                <Badge variant={worker.status === 'active' ? 'solid' : 'outline'} size="sm">
                  {worker.status}
                </Badge>
              </div>
            </div>
          </Card>
        </div>
      </section>
    </div>
  );
};

export default BranchWorkerShow;
