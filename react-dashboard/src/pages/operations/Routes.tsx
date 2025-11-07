import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { unifiedShipmentsApi } from '../../services/api';

type TransferPayload = {
  transfers?: Array<Record<string, any>>;
  total?: number;
  grouped_by_route?: Record<string, number>;
};

const RoutesPage: React.FC = () => {
  const {
    data,
    isLoading,
    isFetching,
    isError,
    error,
    refetch,
  } = useQuery<TransferPayload>({
    queryKey: ['inter-branch-transfers'],
    queryFn: async () => {
      const response = await unifiedShipmentsApi.getInterBranchTransfers();
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to load route data');
      }
      return response.data as TransferPayload;
    },
    refetchInterval: 2 * 60 * 1000,
  });

  const transfers = data?.transfers ?? [];
  const grouped = useMemo(() => data?.grouped_by_route ?? {}, [data?.grouped_by_route]);

  if (isLoading && !data) {
    return <LoadingSpinner message="Loading route analytics" />;
  }

  if (isError) {
    const message = error instanceof Error ? error.message : 'Unable to load routes';
    return (
      <Card className="p-8 text-center">
        <h2 className="text-2xl font-semibold text-mono-black">Routes unavailable</h2>
        <p className="text-sm text-mono-gray-600 mt-2">{message}</p>
        <Button className="mt-6" variant="primary" onClick={() => refetch()}>
          Retry
        </Button>
      </Card>
    );
  }

  return (
    <div className="space-y-8">
      <header className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Operations</p>
          <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">Route Control</h1>
          <p className="text-sm text-mono-gray-600 max-w-2xl">
            Visualise inter-branch transfers, prioritise congested corridors, and keep the linehaul network balanced.
          </p>
        </div>
        <div className="flex items-center gap-3">
          {isFetching && <span className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Refreshing…</span>}
          <Button variant="secondary" size="sm" onClick={() => refetch()}>
            <i className="fas fa-sync-alt mr-2" aria-hidden="true" />
            Refresh
          </Button>
        </div>
      </header>

      <section className="grid gap-4 md:grid-cols-3">
        <Card className="border border-mono-gray-200 p-6">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Active transfers</p>
          <p className="text-3xl font-semibold text-mono-black mt-2">{(data?.total ?? transfers.length).toLocaleString()}</p>
        </Card>
        <Card className="border border-mono-gray-200 p-6">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Routes</p>
          <p className="text-3xl font-semibold text-mono-black mt-2">{Object.keys(grouped).length}</p>
        </Card>
        <Card className="border border-mono-gray-200 p-6">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Mean workload</p>
          <p className="text-3xl font-semibold text-mono-black mt-2">
            {Object.keys(grouped).length > 0 ? Math.round(((transfers.length / Object.keys(grouped).length) || 0) * 10) / 10 : 0}
          </p>
        </Card>
      </section>

      <Card className="border border-mono-gray-200 p-6">
        <h2 className="text-lg font-semibold text-mono-black">Route Workload</h2>
        <div className="mt-4 overflow-x-auto">
          <table className="min-w-full divide-y divide-mono-gray-200 text-left text-sm">
            <thead>
              <tr className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">
                <th className="px-4 py-3">Route</th>
                <th className="px-4 py-3">Transfers</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-mono-gray-100">
              {Object.entries(grouped).map(([route, count]) => (
                <tr key={route} className="hover:bg-mono-gray-50">
                  <td className="px-4 py-3 font-medium text-mono-black">{route}</td>
                  <td className="px-4 py-3">{count}</td>
                </tr>
              ))}
              {Object.keys(grouped).length === 0 && (
                <tr>
                  <td colSpan={2} className="px-4 py-6 text-center text-mono-gray-500">
                    No inter-branch transfers recorded.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </Card>

      <Card className="border border-mono-gray-200 p-6">
        <h2 className="text-lg font-semibold text-mono-black">Transfer Feed</h2>
        <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500 mt-1">Latest 25 transfers with operational context</p>
        <div className="mt-4 max-h-96 overflow-y-auto space-y-3">
          {transfers.slice(0, 25).map((transfer, index) => (
            <div key={transfer.id ?? index} className="rounded-2xl border border-mono-gray-200 p-4">
              <div className="flex items-center justify-between">
                <span className="text-sm font-semibold text-mono-black">
                  {(transfer.originBranch?.name ?? transfer.origin_branch?.name ?? 'Unknown')}
                  {' '}→{' '}
                  {(transfer.destBranch?.name ?? transfer.destination_branch?.name ?? 'Unknown')}
                </span>
                <span className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">{transfer.priority ?? 'normal'}</span>
              </div>
              <p className="mt-1 text-xs text-mono-gray-500">Shipments: {transfer.shipment_count ?? transfer.count ?? '—'} | Weight: {transfer.total_weight ?? '—'}</p>
              {transfer.notes && (
                <p className="mt-2 text-xs text-mono-gray-600">Notes: {transfer.notes}</p>
              )}
            </div>
          ))}
          {transfers.length === 0 && <p className="text-sm text-mono-gray-500">No transfer events found.</p>}
        </div>
      </Card>
    </div>
  );
};

export default RoutesPage;
