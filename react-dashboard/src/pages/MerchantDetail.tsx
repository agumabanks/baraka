import React from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Badge from '../components/ui/Badge';
import LoadingSpinner from '../components/ui/LoadingSpinner';
import { useMerchantDetail } from '../hooks/useMerchants';

const currencyFormatter = new Intl.NumberFormat('en-US', {
  style: 'currency',
  currency: 'UGX',
  maximumFractionDigits: 0,
});

const MerchantDetail: React.FC = () => {
  const navigate = useNavigate();
  const { merchantId } = useParams();

  const {
    data,
    isLoading,
    isError,
    error,
    refetch,
  } = useMerchantDetail(merchantId ?? null);

  if (isLoading && !data) {
    return <LoadingSpinner message="Loading merchant profile" />;
  }

  if (isError || !data) {
    const message = error instanceof Error ? error.message : 'Unable to load merchant';
    return (
      <div className="flex min-h-[400px] flex-col items-center justify-center">
        <Card className="max-w-md text-center">
          <div className="space-y-4">
            <div className="inline-flex h-16 w-16 items-center justify-center rounded-full bg-mono-black text-mono-white">
              <i className="fas fa-exclamation-circle text-2xl" aria-hidden="true" />
            </div>
            <div>
              <h2 className="text-2xl font-semibold text-mono-black">Merchant unavailable</h2>
              <p className="text-sm text-mono-gray-600">{message}</p>
            </div>
            <div className="flex justify-center gap-3">
              <Button variant="secondary" size="sm" onClick={() => navigate(-1)}>
                Go Back
              </Button>
              <Button variant="primary" size="sm" onClick={() => refetch()}>
                <i className="fas fa-redo mr-2" aria-hidden="true" />
                Retry
              </Button>
            </div>
          </div>
        </Card>
      </div>
    );
  }

  const { merchant } = data;

  return (
    <div className="space-y-8">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div className="space-y-2">
          <button
            type="button"
            onClick={() => navigate(-1)}
            className="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500 transition-colors hover:text-mono-black"
          >
            <i className="fas fa-arrow-left" aria-hidden="true" />
            Back to Merchants
          </button>
          <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">{merchant.business_name}</h1>
          <p className="text-sm text-mono-gray-600">Primary contact • {merchant.primary_contact?.name ?? 'Not assigned'}</p>
        </div>
        <Badge variant="outline" size="sm">{merchant.status ?? 'Active'}</Badge>
      </div>

      <section className="grid gap-6 lg:grid-cols-3">
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Current Balance</p>
            <p className="text-3xl font-semibold text-mono-black">{currencyFormatter.format(merchant.finance.current_balance)}</p>
            <p className="text-sm text-mono-gray-600">Available for settlements</p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">COD Outstanding</p>
            <p className="text-3xl font-semibold text-mono-black">{currencyFormatter.format(merchant.finance.cod_outstanding)}</p>
            <p className="text-sm text-mono-gray-600">Awaiting handover from network</p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">COD Collected</p>
            <p className="text-3xl font-semibold text-mono-black">{currencyFormatter.format(merchant.finance.cod_collected)}</p>
            <p className="text-sm text-mono-gray-600">Delivered & reconciled this cycle</p>
          </div>
        </Card>
      </section>

      <section className="grid gap-6 lg:grid-cols-[2fr,1fr]">
        <Card className="border border-mono-gray-200">
          <header className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Payment Accounts</p>
              <h2 className="text-xl font-semibold text-mono-black">Settlement Destinations</h2>
            </div>
            <Button variant="ghost" size="sm" onClick={() => refetch()}>
              <i className="fas fa-sync-alt mr-2" aria-hidden="true" />
              Refresh
            </Button>
          </header>
          <div className="mt-4 space-y-3">
            {merchant.payment_accounts.length === 0 ? (
              <div className="rounded-2xl border border-dashed border-mono-gray-300 p-4 text-sm text-mono-gray-600">
                No payout accounts configured.
              </div>
            ) : (
              merchant.payment_accounts.map((account) => (
                <div key={account.id} className="rounded-2xl border border-mono-gray-200 p-4">
                  <p className="text-sm font-semibold text-mono-black">{account.payment_method ?? 'Unspecified method'}</p>
                  <p className="text-sm text-mono-gray-600">{account.bank_name ?? account.mobile_company ?? '—'}</p>
                  <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">{account.account_no ?? account.mobile_no ?? 'Account pending'}</p>
                </div>
              ))
            )}
          </div>
        </Card>

        <Card className="border border-mono-gray-200">
          <header className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Contact</p>
            <h2 className="text-lg font-semibold text-mono-black">Primary Stakeholder</h2>
          </header>
          <div className="mt-4 space-y-3 text-sm text-mono-gray-600">
            <div>
              <span className="block text-xs uppercase tracking-[0.25em] text-mono-gray-500">Name</span>
              <span>{merchant.primary_contact?.name ?? 'Not assigned'}</span>
            </div>
            <div>
              <span className="block text-xs uppercase tracking-[0.25em] text-mono-gray-500">Email</span>
              <span>{merchant.primary_contact?.email ?? '—'}</span>
            </div>
            <div>
              <span className="block text-xs uppercase tracking-[0.25em] text-mono-gray-500">Phone</span>
              <span>{merchant.primary_contact?.phone ?? '—'}</span>
            </div>
          </div>
        </Card>
      </section>

      <section className="grid gap-6 lg:grid-cols-[2fr,1fr]">
        <Card className="border border-mono-gray-200">
          <header className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Recent Parcels</p>
              <h2 className="text-xl font-semibold text-mono-black">Latest fulfilment</h2>
            </div>
            <Button variant="ghost" size="sm" onClick={() => navigate('/dashboard/parcels')}>
              Open Parcel Console
            </Button>
          </header>
          <div className="mt-6 overflow-x-auto">
            <table className="min-w-full divide-y divide-mono-gray-200 text-left">
              <thead className="bg-mono-gray-50 text-xs uppercase tracking-[0.3em] text-mono-gray-500">
                <tr>
                  <th scope="col" className="px-4 py-3">Tracking</th>
                  <th scope="col" className="px-4 py-3">Status</th>
                  <th scope="col" className="px-4 py-3">COD</th>
                  <th scope="col" className="px-4 py-3">Delivery Charge</th>
                  <th scope="col" className="px-4 py-3">Shop</th>
                  <th scope="col" className="px-4 py-3">Created</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-mono-gray-100 text-sm text-mono-gray-700">
                {merchant.recent_parcels.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="px-4 py-6 text-center text-sm text-mono-gray-500">
                      No recent parcels recorded.
                    </td>
                  </tr>
                ) : (
                  merchant.recent_parcels.map((parcel) => (
                    <tr key={parcel.id}>
                      <td className="px-4 py-3 font-medium text-mono-black">{parcel.tracking_id ?? '—'}</td>
                      <td className="px-4 py-3">{parcel.status ?? '—'}</td>
                      <td className="px-4 py-3">{currencyFormatter.format(Number(parcel.cod_amount ?? 0))}</td>
                      <td className="px-4 py-3">{currencyFormatter.format(Number(parcel.delivery_charge ?? 0))}</td>
                      <td className="px-4 py-3">{parcel.merchant_shop ?? '—'}</td>
                      <td className="px-4 py-3">{parcel.created_at ?? '—'}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </Card>

        <Card className="border border-mono-gray-200">
          <header className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Shops</p>
            <h2 className="text-lg font-semibold text-mono-black">Fulfilment locations</h2>
          </header>
          <div className="mt-4 space-y-3 text-sm text-mono-gray-600">
            {merchant.shops.length === 0 ? (
              <p>No shops onboarded.</p>
            ) : (
              merchant.shops.map((shop) => (
                <div key={shop.id} className="rounded-2xl border border-mono-gray-200 p-3">
                  <p className="font-medium text-mono-black">{shop.name}</p>
                  <p>{shop.address ?? 'Address pending'}</p>
                  <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">{shop.contact_no ?? '—'}</p>
                </div>
              ))
            )}
          </div>
        </Card>
      </section>
    </div>
  );
};

export default MerchantDetail;
