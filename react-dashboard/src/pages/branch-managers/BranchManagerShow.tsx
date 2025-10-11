import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Badge from '../../components/ui/Badge';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { branchManagersApi } from '../../services/api';

const BranchManagerShow: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['branch-manager', id],
    queryFn: () => branchManagersApi.getManager(id!),
    enabled: !!id,
  });

  if (isLoading) {
    return <LoadingSpinner message="Loading manager details" />;
  }

  if (isError) {
    const message = error instanceof Error ? error.message : 'Unable to load manager details';
    return (
      <div className="flex min-h-[400px] flex-col items-center justify-center">
        <Card className="max-w-md text-center">
          <div className="space-y-4">
            <div className="inline-flex h-16 w-16 items-center justify-center rounded-full bg-mono-black text-mono-white">
              <i className="fas fa-exclamation-triangle text-2xl" aria-hidden="true" />
            </div>
            <div>
              <h2 className="text-2xl font-semibold text-mono-black">Unable to load manager</h2>
              <p className="text-sm text-mono-gray-600">{message}</p>
            </div>
            <Button variant="primary" onClick={() => navigate('/dashboard/branch-managers')}>
              Back to Managers
            </Button>
          </div>
        </Card>
      </div>
    );
  }

  const manager = data?.data?.manager;
  const analytics = data?.data?.analytics;

  if (!manager) {
    return null;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <section className="rounded-3xl border border-mono-gray-200 bg-mono-white shadow-xl">
        <header className="flex flex-col gap-6 border-b border-mono-gray-200 px-8 py-10 xl:flex-row xl:items-center xl:justify-between">
          <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Branch Manager
            </p>
            <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
              {manager.user?.name || 'Manager Details'}
            </h1>
            <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
              {manager.business_name} • {manager.branch?.name}
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <Button
              variant="secondary"
              size="sm"
              onClick={() => navigate(`/dashboard/branch-managers/${id}/edit`)}
            >
              <i className="fas fa-edit mr-2" aria-hidden="true" />
              Edit Manager
            </Button>
            <Button
              variant="primary"
              size="sm"
              onClick={() => navigate('/dashboard/branch-managers')}
            >
              <i className="fas fa-arrow-left mr-2" aria-hidden="true" />
              Back to List
            </Button>
          </div>
        </header>

        <div className="px-8 py-8">
          <div className="grid gap-6 lg:grid-cols-2">
            {/* Manager Information */}
            <Card className="border border-mono-gray-200">
              <div className="space-y-4">
                <h3 className="text-lg font-semibold text-mono-black">Manager Information</h3>
                
                <div className="space-y-3">
                  <div>
                    <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Name</p>
                    <p className="text-mono-black">{manager.user?.name || 'N/A'}</p>
                  </div>

                  <div>
                    <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Email</p>
                    <p className="text-mono-black">{manager.user?.email || 'N/A'}</p>
                  </div>

                  <div>
                    <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Phone</p>
                    <p className="text-mono-black">{manager.user?.phone || 'N/A'}</p>
                  </div>

                  <div>
                    <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Business Name</p>
                    <p className="text-mono-black">{manager.business_name}</p>
                  </div>

                  <div>
                    <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Status</p>
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
                  </div>
                </div>
              </div>
            </Card>

            {/* Branch Information */}
            <Card className="border border-mono-gray-200">
              <div className="space-y-4">
                <h3 className="text-lg font-semibold text-mono-black">Branch Information</h3>
                
                <div className="space-y-3">
                  <div>
                    <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Branch Name</p>
                    <p className="text-mono-black">{manager.branch?.name || 'N/A'}</p>
                  </div>

                  <div>
                    <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Branch Code</p>
                    <p className="text-mono-black">{manager.branch?.code || 'N/A'}</p>
                  </div>

                  <div>
                    <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Branch Type</p>
                    <p className="text-mono-black">{manager.branch?.type || 'N/A'}</p>
                  </div>

                  <div>
                    <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Current Balance</p>
                    <p className="text-2xl font-semibold text-mono-black">
                      ${manager.current_balance?.toFixed(2) || '0.00'}
                    </p>
                  </div>
                </div>
              </div>
            </Card>
          </div>

          {/* Analytics */}
          {analytics && Object.keys(analytics).length > 0 && (
            <div className="mt-6 grid gap-6 lg:grid-cols-3">
              <Card className="border border-mono-gray-200">
                <div className="space-y-2">
                  <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Total Shipments</p>
                  <p className="text-3xl font-semibold text-mono-black">
                    {(analytics as any).shipments?.total || 0}
                  </p>
                  <p className="text-sm text-mono-gray-600">
                    {(analytics as any).shipments?.last_30_days || 0} in last 30 days
                  </p>
                </div>
              </Card>

              <Card className="border border-mono-gray-200">
                <div className="space-y-2">
                  <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Total Revenue</p>
                  <p className="text-3xl font-semibold text-mono-black">
                    ${(analytics as any).revenue?.total?.toFixed(2) || '0.00'}
                  </p>
                  <p className="text-sm text-mono-gray-600">
                    ${(analytics as any).revenue?.last_30_days?.toFixed(2) || '0.00'} in last 30 days
                  </p>
                </div>
              </Card>

              <Card className="border border-mono-gray-200">
                <div className="space-y-2">
                  <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Delivery Success Rate</p>
                  <p className="text-3xl font-semibold text-mono-black">
                    {(analytics as any).performance?.delivery_success_rate?.toFixed(1) || '0'}%
                  </p>
                  <p className="text-sm text-mono-gray-600">
                    {(analytics as any).performance?.on_time_delivery_rate?.toFixed(1) || '0'}% on-time
                  </p>
                </div>
              </Card>
            </div>
          )}

          {/* Recent Shipments */}
          {manager.recent_shipments && manager.recent_shipments.length > 0 && (
            <Card className="mt-6 border border-mono-gray-200">
              <div className="space-y-4">
                <h3 className="text-lg font-semibold text-mono-black">Recent Shipments</h3>
                <div className="space-y-2">
                  {manager.recent_shipments.map((shipment: any) => (
                    <div key={shipment.id} className="flex items-center justify-between border-b border-mono-gray-100 pb-2">
                      <div>
                        <p className="font-medium text-mono-black">{shipment.tracking_number}</p>
                        <p className="text-sm text-mono-gray-600">{shipment.status}</p>
                      </div>
                      <p className="font-mono text-mono-black">${shipment.amount?.toFixed(2)}</p>
                    </div>
                  ))}
                </div>
              </div>
            </Card>
          )}
        </div>
      </section>
    </div>
  );
};

export default BranchManagerShow;
