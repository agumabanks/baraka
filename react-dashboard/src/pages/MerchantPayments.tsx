import React, { useMemo } from 'react';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import LoadingSpinner from '../components/ui/LoadingSpinner';
import { useMerchantList } from '../hooks/useMerchants';

const currencyFormatter = new Intl.NumberFormat('en-US', {
  style: 'currency',
  currency: 'UGX',
  maximumFractionDigits: 0,
});

const MerchantPayments: React.FC = () => {
  const { data, isLoading, isError, error, refetch, isFetching } = useMerchantList();

  const merchants = data?.items ?? [];

  const totals = useMemo(() => {
    return merchants.reduce(
      (acc: any, merchant: any) => {
        acc.codOutstanding += merchant.metrics.cod_open_balance;
        acc.codCollected += merchant.metrics.cod_collected;
        acc.currentBalance += merchant.current_balance;
        return acc;
      },
      { codOutstanding: 0, codCollected: 0, currentBalance: 0 },
    );
  }, [merchants]);

  if (isLoading && !data) {
    return <LoadingSpinner message="Loading payouts desk" />;
  }

  if (isError) {
    const message = error instanceof Error ? error.message : 'Unable to load payouts desk';
    return (
      <div className="flex min-h-[400px] flex-col items-center justify-center">
        <Card className="max-w-md text-center">
          <div className="space-y-4">
            <div className="inline-flex h-16 w-16 items-center justify-center rounded-full bg-mono-black text-mono-white">
              <i className="fas fa-exclamation-triangle text-2xl" aria-hidden="true" />
            </div>
            <div>
              <h2 className="text-2xl font-semibold text-mono-black">Settlement desk offline</h2>
              <p className="text-sm text-mono-gray-600">{message}</p>
            </div>
            <Button variant="primary" size="md" onClick={() => refetch()}>
              <i className="fas fa-redo mr-2" aria-hidden="true" />
              Retry
            </Button>
          </div>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-8">
      <header className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="space-y-2">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Settlement Control</p>
          <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">Merchant Payments Desk</h1>
          <p className="text-sm text-mono-gray-600">Coordinate COD payouts and credit balances across merchants in real time.</p>
        </div>
        <div className="flex flex-wrap items-center gap-3">
          {isFetching && (
            <span className="text-xs uppercase tracking-[0.3em] text-mono-gray-500" aria-live="polite">
              Refreshing…
            </span>
          )}
          <Button variant="secondary" size="sm" className="uppercase tracking-[0.25em]" onClick={() => refetch()}>
            <i className="fas fa-sync-alt mr-2" aria-hidden="true" />
            Refresh
          </Button>
        </div>
      </header>

      <section className="grid gap-6 lg:grid-cols-3">
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Current Balances</p>
            <p className="text-3xl font-semibold text-mono-black">{currencyFormatter.format(totals.currentBalance)}</p>
            <p className="text-sm text-mono-gray-600">Merchant wallet balances pending settlement</p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">COD Outstanding</p>
            <p className="text-3xl font-semibold text-mono-black">{currencyFormatter.format(totals.codOutstanding)}</p>
            <p className="text-sm text-mono-gray-600">COD to be disbursed on delivery confirmation</p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">COD Collected</p>
            <p className="text-3xl font-semibold text-mono-black">{currencyFormatter.format(totals.codCollected)}</p>
            <p className="text-sm text-mono-gray-600">Delivered orders credited to merchants</p>
          </div>
        </Card>
      </section>

      <Card className="border border-mono-gray-200">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-mono-gray-200 text-left">
            <thead className="bg-mono-gray-50 text-xs uppercase tracking-[0.3em] text-mono-gray-500">
              <tr>
                <th scope="col" className="px-4 py-3">Merchant</th>
                <th scope="col" className="px-4 py-3">Contact</th>
                <th scope="col" className="px-4 py-3">Current Balance</th>
                <th scope="col" className="px-4 py-3">COD Outstanding</th>
                <th scope="col" className="px-4 py-3">COD Collected</th>
                <th scope="col" className="px-4 py-3">Active Shipments</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-mono-gray-100 text-sm text-mono-gray-700">
              {merchants.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-4 py-6 text-center text-sm text-mono-gray-500">
                    No merchant balances to display.
                  </td>
                </tr>
              ) : (
                merchants.map((merchant: any) => (
                  <tr key={merchant.id}>
                    <td className="px-4 py-3 font-medium text-mono-black">{merchant.business_name}</td>
                    <td className="px-4 py-3">{merchant.primary_contact?.email ?? merchant.primary_contact?.name ?? '—'}</td>
                    <td className="px-4 py-3">{currencyFormatter.format(merchant.current_balance)}</td>
                    <td className="px-4 py-3">{currencyFormatter.format(merchant.metrics.cod_open_balance)}</td>
                    <td className="px-4 py-3">{currencyFormatter.format(merchant.metrics.cod_collected)}</td>
                    <td className="px-4 py-3">{merchant.metrics.active_shipments}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  );
};

export default MerchantPayments;
