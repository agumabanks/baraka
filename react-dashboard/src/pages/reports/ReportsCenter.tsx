import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { reportsApi } from '../../services/api';

type SummaryResponse = {
  parcelStatusWiseCount?: Record<string, number>;
  profitInfo?: Record<string, unknown>;
  cashCollectionInfo?: Record<string, unknown>;
  payableToMerchant?: Record<string, unknown>;
  merchant?: Record<string, unknown>;
  request?: Record<string, unknown>;
};

const ReportsCenter: React.FC = () => {
  const [filters, setFilters] = useState({
    date_from: '',
    date_to: '',
  });

  const {
    data,
    isLoading,
    isFetching,
    refetch,
    isError,
    error,
  } = useQuery<SummaryResponse>({
    queryKey: ['reports-summary', filters],
    queryFn: async () => {
      const payload: Record<string, unknown> = {};
      if (filters.date_from) payload.date_from = filters.date_from;
      if (filters.date_to) payload.date_to = filters.date_to;
      const response = await reportsApi.getSummary(payload);
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to load reports');
      }
      return response.data as SummaryResponse;
    },
  });

  const parcelStatusCounts = useMemo(() => {
    return data?.parcelStatusWiseCount ?? {};
  }, [data?.parcelStatusWiseCount]);

  const profitInfo = data?.profitInfo ?? {};
  const cashCollection = data?.cashCollectionInfo ?? {};
  const payableToMerchant = data?.payableToMerchant ?? {};

  if (isLoading && !data) {
    return <LoadingSpinner message="Loading reports" />;
  }

  if (isError) {
    const message = error instanceof Error ? error.message : 'Unable to load reports';
    return (
      <Card className="p-8 text-center">
        <h2 className="text-2xl font-semibold text-mono-black mb-2">Reports unavailable</h2>
        <p className="text-sm text-mono-gray-600">{message}</p>
        <Button variant="primary" size="md" className="mt-6" onClick={() => refetch()}>
          Retry
        </Button>
      </Card>
    );
  }

  return (
    <div className="space-y-8">
      <header className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Insights</p>
          <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">Reports Center</h1>
          <p className="text-sm text-mono-gray-600 max-w-2xl">
            Analyse parcel performance, profitability, and merchant settlements across the network. Use date filters to refine the dataset.
          </p>
        </div>
        <div className="flex items-center gap-3 text-xs text-mono-gray-500 uppercase tracking-[0.3em]">
          {isFetching ? 'Refreshing…' : 'Up to date'}
        </div>
      </header>

      <Card className="border border-mono-gray-200 p-6">
        <form
          className="flex flex-col gap-4 md:flex-row md:items-end"
          onSubmit={(event) => {
            event.preventDefault();
            refetch();
          }}
        >
          <div className="grid flex-1 gap-2">
            <label className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Date From</label>
            <Input
              type="date"
              value={filters.date_from}
              onChange={(event) => setFilters((prev) => ({ ...prev, date_from: event.target.value }))}
            />
          </div>
          <div className="grid flex-1 gap-2">
            <label className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Date To</label>
            <Input
              type="date"
              value={filters.date_to}
              onChange={(event) => setFilters((prev) => ({ ...prev, date_to: event.target.value }))}
            />
          </div>
          <div className="flex gap-3">
            <Button type="submit" variant="primary" size="md">
              Apply Filters
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="md"
              onClick={() => {
                setFilters({ date_from: '', date_to: '' });
                refetch();
              }}
            >
              Clear
            </Button>
          </div>
        </form>
      </Card>

      <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {Object.entries(parcelStatusCounts).map(([status, count]) => (
          <Card key={status} className="border border-mono-gray-200 p-6">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">{status}</p>
            <p className="mt-3 text-3xl font-semibold text-mono-black">{Number(count).toLocaleString()}</p>
          </Card>
        ))}
        {Object.keys(parcelStatusCounts).length === 0 && (
          <Card className="border border-dashed border-mono-gray-200 bg-mono-gray-50 p-6 text-sm text-mono-gray-600">
            No parcel status data available for the selected period.
          </Card>
        )}
      </section>

      <section className="grid gap-6 lg:grid-cols-3">
        <Card className="border border-mono-gray-200 p-6">
          <h2 className="text-lg font-semibold text-mono-black">Profitability</h2>
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500 mt-1">Income vs expense breakdown</p>
          <dl className="mt-4 space-y-3 text-sm text-mono-gray-700">
            {Object.entries(profitInfo).map(([key, value]) => (
              <div key={key} className="flex items-center justify-between">
                <dt className="font-medium capitalize">{key.replace(/_/g, ' ')}</dt>
                <dd>{typeof value === 'number' ? value.toLocaleString() : String(value ?? '—')}</dd>
              </div>
            ))}
            {Object.keys(profitInfo).length === 0 && <p>No profit data available.</p>}
          </dl>
        </Card>

        <Card className="border border-mono-gray-200 p-6">
          <h2 className="text-lg font-semibold text-mono-black">Cash Collection</h2>
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500 mt-1">Totals derived from parcel workflow</p>
          <dl className="mt-4 space-y-3 text-sm text-mono-gray-700">
            {Object.entries(cashCollection).map(([key, value]) => (
              <div key={key} className="flex items-center justify-between">
                <dt className="font-medium capitalize">{key.replace(/_/g, ' ')}</dt>
                <dd>{typeof value === 'number' ? value.toLocaleString() : String(value ?? '—')}</dd>
              </div>
            ))}
            {Object.keys(cashCollection).length === 0 && <p>No cash collection data available.</p>}
          </dl>
        </Card>

        <Card className="border border-mono-gray-200 p-6">
          <h2 className="text-lg font-semibold text-mono-black">Payable to Merchant</h2>
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500 mt-1">Settlement posture overview</p>
          <dl className="mt-4 space-y-3 text-sm text-mono-gray-700">
            {Object.entries(payableToMerchant).map(([key, value]) => (
              <div key={key} className="flex items-center justify-between">
                <dt className="font-medium capitalize">{key.replace(/_/g, ' ')}</dt>
                <dd>{typeof value === 'number' ? value.toLocaleString() : String(value ?? '—')}</dd>
              </div>
            ))}
            {Object.keys(payableToMerchant).length === 0 && <p>No settlement data available.</p>}
          </dl>
        </Card>
      </section>

      <section className="grid gap-4 lg:grid-cols-2">
        <Card className="border border-mono-gray-200 p-6">
          <h2 className="text-lg font-semibold text-mono-black">Raw Data Snapshot</h2>
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500 mt-1">Useful for exporting to BI tools</p>
          <pre className="mt-4 max-h-80 overflow-y-auto rounded-xl bg-mono-gray-50 p-4 text-xs text-mono-gray-700">
            {JSON.stringify(data, null, 2)}
          </pre>
        </Card>
        <Card className="border border-mono-gray-200 p-6">
          <h2 className="text-lg font-semibold text-mono-black">Filter Context</h2>
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500 mt-1">Parameters applied to this extract</p>
          <pre className="mt-4 max-h-80 overflow-y-auto rounded-xl bg-mono-gray-50 p-4 text-xs text-mono-gray-700">
            {JSON.stringify(filters, null, 2)}
          </pre>
        </Card>
      </section>
    </div>
  );
};

export default ReportsCenter;
