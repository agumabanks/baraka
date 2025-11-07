import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { unifiedShipmentsApi } from '../../services/api';

type HubSortationPayload = {
  shipments?: Array<Record<string, any>>;
  total?: number;
  grouped_by_destination?: Record<string, {
    destination?: string;
    count?: number;
    priority_breakdown?: Record<string, number>;
  }>;
};

const BagsPage: React.FC = () => {
  const {
    data,
    isLoading,
    isFetching,
    isError,
    error,
    refetch,
  } = useQuery<HubSortationPayload>({
    queryKey: ['hub-sortation'],
    queryFn: async () => {
      const response = await unifiedShipmentsApi.getHubSortation();
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to load bag data');
      }
      return response.data as HubSortationPayload;
    },
    refetchInterval: 2 * 60 * 1000,
  });

  const shipments = data?.shipments ?? [];
  const grouped = useMemo(() => data?.grouped_by_destination ?? {}, [data?.grouped_by_destination]);

  if (isLoading && !data) {
    return <LoadingSpinner message="Loading bag operations" />;
  }

  if (isError) {
    const message = error instanceof Error ? error.message : 'Unable to load bag operations';
    return (
      <Card className="p-8 text-center">
        <h2 className="text-2xl font-semibold text-mono-black">Bags data unavailable</h2>
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
          <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">Bag Assembly</h1>
          <p className="text-sm text-mono-gray-600 max-w-2xl">
            Monitor hub sortation, destination workload, and bag priority mix to keep dispatch flowing.
          </p>
        </div>
        <div className="flex items-center gap-3">
          {isFetching && (
            <span className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Refreshing…</span>
          )}
          <Button variant="secondary" size="sm" onClick={() => refetch()}>
            <i className="fas fa-sync-alt mr-2" aria-hidden="true" />
            Refresh
          </Button>
        </div>
      </header>

      <section className="grid gap-4 md:grid-cols-3">
        <Card className="border border-mono-gray-200 p-6">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Shipments queued</p>
          <p className="text-3xl font-semibold text-mono-black mt-2">{(data?.total ?? shipments.length).toLocaleString()}</p>
        </Card>
        <Card className="border border-mono-gray-200 p-6">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Destinations</p>
          <p className="text-3xl font-semibold text-mono-black mt-2">{Object.keys(grouped).length}</p>
        </Card>
        <Card className="border border-mono-gray-200 p-6">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Average per destination</p>
          <p className="text-3xl font-semibold text-mono-black mt-2">
            {Object.keys(grouped).length > 0 ? Math.round((shipments.length / Object.keys(grouped).length) * 10) / 10 : 0}
          </p>
        </Card>
      </section>

      <section className="grid gap-6 lg:grid-cols-2">
        <Card className="border border-mono-gray-200 p-6">
          <h2 className="text-lg font-semibold text-mono-black">Destination Load</h2>
          <div className="mt-4 overflow-x-auto">
            <table className="min-w-full divide-y divide-mono-gray-200 text-left text-sm">
              <thead>
                <tr className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">
                  <th className="px-4 py-3">Destination</th>
                  <th className="px-4 py-3">Total</th>
                  <th className="px-4 py-3">High</th>
                  <th className="px-4 py-3">Medium</th>
                  <th className="px-4 py-3">Low</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-mono-gray-100">
                {Object.entries(grouped).map(([destinationKey, info]) => (
                  <tr key={destinationKey} className="hover:bg-mono-gray-50">
                    <td className="px-4 py-3 font-medium text-mono-black">{info?.destination ?? destinationKey}</td>
                    <td className="px-4 py-3">{info?.count ?? 0}</td>
                    <td className="px-4 py-3">{info?.priority_breakdown?.high ?? 0}</td>
                    <td className="px-4 py-3">{info?.priority_breakdown?.medium ?? 0}</td>
                    <td className="px-4 py-3">{info?.priority_breakdown?.low ?? 0}</td>
                  </tr>
                ))}
                {Object.keys(grouped).length === 0 && (
                  <tr>
                    <td colSpan={5} className="px-4 py-6 text-center text-mono-gray-500">
                      No destination data available.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </Card>

        <Card className="border border-mono-gray-200 p-6">
          <h2 className="text-lg font-semibold text-mono-black">Queue Details</h2>
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500 mt-1">First 25 shipments awaiting bag assignment</p>
          <div className="mt-4 max-h-96 overflow-y-auto space-y-3">
            {shipments.slice(0, 25).map((shipment, index) => (
              <div key={shipment.id ?? index} className="rounded-2xl border border-mono-gray-200 p-4">
                <div className="flex items-center justify-between">
                  <span className="text-sm font-semibold text-mono-black">{shipment.tracking_number ?? `Shipment #${shipment.id ?? index}`}</span>
                  <span className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">{shipment.priority ?? 'normal'}</span>
                </div>
                <p className="mt-2 text-xs text-mono-gray-500">Origin: {shipment.origin_branch?.name ?? 'N/A'} → Destination: {shipment.destBranch?.name ?? shipment.destination_branch?.name ?? 'N/A'}</p>
                <p className="mt-1 text-xs text-mono-gray-500">Pieces: {shipment.pieces ?? '—'} • Weight: {shipment.weight ?? '—'}</p>
              </div>
            ))}
            {shipments.length === 0 && <p className="text-sm text-mono-gray-500">No shipments pending bag assembly.</p>}
          </div>
        </Card>
      </section>
    </div>
  );
};

export default BagsPage;
