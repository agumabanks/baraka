import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { financeApi } from '../../services/api';

type SettlementPayload = Record<string, any>;
type SettlementResponse =
  | SettlementPayload
  | SettlementPayload[]
  | {
      data?: SettlementPayload[];
      settlements?: SettlementPayload[];
    };

const SettlementsPage: React.FC = () => {
  const [filters, setFilters] = useState({ date_from: '', date_to: '' });

  const settlementsQuery = useQuery<SettlementResponse>({
    queryKey: ['finance', 'settlements', filters],
    queryFn: async () => {
      const response = await financeApi.getSettlements();
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to load settlements');
      }
      return response.data as SettlementResponse;
    },
  });

  const settlementEntries = useMemo(() => {
    const raw = settlementsQuery.data;
    if (!raw) {
      return [];
    }

    if (Array.isArray(raw)) {
      return raw as Array<Record<string, any>>;
    }

    const container = raw as Record<string, any>;
    if (Array.isArray(container.data)) {
      return container.data as Array<Record<string, any>>;
    }
    if (Array.isArray(container.settlements)) {
      return container.settlements as Array<Record<string, any>>;
    }

    return [];
  }, [settlementsQuery.data]);

  if (settlementsQuery.isLoading && !settlementsQuery.data) {
    return <LoadingSpinner message="Loading settlements" />;
  }

  if (settlementsQuery.isError) {
    const message = settlementsQuery.error instanceof Error ? settlementsQuery.error.message : 'Unable to load settlements';
    return (
      <Card className="p-8 text-center">
        <h2 className="text-2xl font-semibold text-mono-black">Settlements unavailable</h2>
        <p className="text-sm text-mono-gray-600 mt-2">{message}</p>
        <Button className="mt-6" variant="primary" onClick={() => settlementsQuery.refetch()}>
          Retry
        </Button>
      </Card>
    );
  }

  return (
    <div className="space-y-8">
      <header className="space-y-2">
        <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Finance</p>
        <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">Settlements</h1>
        <p className="text-sm text-mono-gray-600 max-w-2xl">
          Keep tabs on settlements, outstanding balances, and reconciliation history.
        </p>
      </header>

      <Card className="border border-mono-gray-200 p-6">
        <form
          className="flex flex-col gap-4 md:flex-row md:items-end"
          onSubmit={(event) => {
            event.preventDefault();
            settlementsQuery.refetch();
          }}
        >
          <div className="grid gap-2">
            <label className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Date From</label>
            <Input
              type="date"
              value={filters.date_from}
              onChange={(event) => setFilters((prev) => ({ ...prev, date_from: event.target.value }))}
            />
          </div>
          <div className="grid gap-2">
            <label className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Date To</label>
            <Input
              type="date"
              value={filters.date_to}
              onChange={(event) => setFilters((prev) => ({ ...prev, date_to: event.target.value }))}
            />
          </div>
          <div className="flex gap-3">
            <Button type="submit" variant="primary" size="md">
              Apply
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="md"
              onClick={() => {
                setFilters({ date_from: '', date_to: '' });
                settlementsQuery.refetch();
              }}
            >
              Clear
            </Button>
          </div>
        </form>
      </Card>

      <Card className="border border-mono-gray-200 p-6">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-mono-gray-200 text-left text-sm">
            <thead>
              <tr className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">
                <th className="px-4 py-3">Reference</th>
                <th className="px-4 py-3">Merchant</th>
                <th className="px-4 py-3">Amount</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3">Updated</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-mono-gray-100">
              {settlementEntries.map((entry, index) => (
                <tr key={entry.id ?? index} className="hover:bg-mono-gray-50">
                  <td className="px-4 py-3 font-medium text-mono-black">{entry.reference ?? entry.id ?? `Settlement ${index + 1}`}</td>
                  <td className="px-4 py-3">{entry.merchant_name ?? entry.merchant?.name ?? '—'}</td>
                  <td className="px-4 py-3">{entry.amount ? Number(entry.amount).toLocaleString() : '—'}</td>
                  <td className="px-4 py-3 text-xs uppercase tracking-[0.2em] text-mono-gray-600">{entry.status ?? 'pending'}</td>
                  <td className="px-4 py-3">{entry.updated_at ?? entry.created_at ?? '—'}</td>
                </tr>
              ))}
              {settlementEntries.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-4 py-6 text-center text-mono-gray-500">
                    No settlement records available.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </Card>

      <Card className="border border-mono-gray-200 p-6">
        <h2 className="text-lg font-semibold text-mono-black">Raw Payload</h2>
        <pre className="mt-4 max-h-72 overflow-y-auto rounded-xl bg-mono-gray-50 p-4 text-xs text-mono-gray-700">
          {JSON.stringify(settlementsQuery.data, null, 2)}
        </pre>
      </Card>
    </div>
  );
};

export default SettlementsPage;
