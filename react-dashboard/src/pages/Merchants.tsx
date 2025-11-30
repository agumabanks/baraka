import React, { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Badge from '../components/ui/Badge';
import LoadingSpinner from '../components/ui/LoadingSpinner';
import { useMerchantList } from '../hooks/useMerchants';
import type { MerchantListItem } from '../types/merchants';

const currencyFormatter = new Intl.NumberFormat('en-US', {
  style: 'currency',
  currency: 'UGX',
  maximumFractionDigits: 0,
});

const resolveStatusLabel = (status: number | string | null): string => {
  if (status === 1 || status === '1' || status === 'active') {
    return 'Active';
  }

  if (status === 0 || status === '0' || status === 'inactive') {
    return 'Inactive';
  }

  if (status === 'suspended') {
    return 'Suspended';
  }

  return typeof status === 'string' ? status : 'Unknown';
};

const Merchants: React.FC = () => {
  const navigate = useNavigate();
  const [selectedStatus, setSelectedStatus] = useState<string | number | null>(null);
  const [copiedMerchantId, setCopiedMerchantId] = useState<number | null>(null);

  const listParams = useMemo(() => (selectedStatus !== null ? { status: selectedStatus } : undefined), [selectedStatus]);
  const { data, isLoading, isError, error, refetch, isFetching } = useMerchantList(listParams);

  const merchants = useMemo(() => data?.items ?? [], [data]);
  const statusFilters = useMemo(() => data?.filters?.statuses ?? [], [data]);

  const summary = useMemo(() => {
    if (!merchants.length) {
      return {
        totalMerchants: 0,
        codOutstanding: 0,
        codCollected: 0,
        activeShipments: 0,
      };
    }

    return merchants.reduce(
      (acc: any, merchant: any) => {
        acc.totalMerchants += 1;
        acc.codOutstanding += merchant.metrics.cod_open_balance;
        acc.codCollected += merchant.metrics.cod_collected;
        acc.activeShipments += merchant.metrics.active_shipments;
        return acc;
      },
      {
        totalMerchants: 0,
        codOutstanding: 0,
        codCollected: 0,
        activeShipments: 0,
      },
    );
  }, [merchants]);

  useEffect(() => {
    if (!copiedMerchantId) {
      return;
    }

    const timeout = window.setTimeout(() => setCopiedMerchantId(null), 2000);
    return () => window.clearTimeout(timeout);
  }, [copiedMerchantId]);

  const handleCopyCodSnapshot = async (merchant: MerchantListItem) => {
    const summaryText = [
      `Merchant: ${merchant.business_name}`,
      `Outstanding COD: ${merchant.metrics.cod_open_balance}`,
      `Collected COD: ${merchant.metrics.cod_collected}`,
      `Active Shipments: ${merchant.metrics.active_shipments}`,
    ].join('\n');

    try {
      if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(summaryText);
        setCopiedMerchantId(merchant.id);
      }
    } catch (clipboardError) {
      console.error('Failed to copy merchant snapshot', clipboardError);
    }
  };

  const handlePhoneDial = (phone: string | null | undefined) => {
    if (!phone) return;
    try {
      window.location.href = `tel:${phone}`;
    } catch (dialError) {
      console.error('Failed to initiate call', dialError);
    }
  };

  const handleEmail = (email: string | null | undefined) => {
    if (!email) return;
    try {
      window.location.href = `mailto:${email}`;
    } catch (emailError) {
      console.error('Failed to initiate email', emailError);
    }
  };

  if (isLoading && !data) {
    return <LoadingSpinner message="Loading merchant network" />;
  }

  if (isError) {
    const message = error instanceof Error ? error.message : 'Unable to load merchants';
    return (
      <div className="flex min-h-[400px] flex-col items-center justify-center">
        <Card className="max-w-md text-center">
          <div className="space-y-4">
            <div className="inline-flex h-16 w-16 items-center justify-center rounded-full bg-mono-black text-mono-white">
              <i className="fas fa-exclamation-triangle text-2xl" aria-hidden="true" />
            </div>
            <div>
              <h2 className="text-2xl font-semibold text-mono-black">Merchant data unavailable</h2>
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
    <div className="space-y-10">
      <section className="rounded-3xl border border-mono-gray-200 bg-mono-white shadow-xl">
        <header className="flex flex-col gap-6 border-b border-mono-gray-200 px-8 py-10 xl:flex-row xl:items-center xl:justify-between">
          <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Merchant Control Centre
            </p>
            <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
              Merchant Network
            </h1>
            <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
              Monitor merchant health, COD exposure, and operational throughput across every active account.
            </p>
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
            <Button variant="primary" size="sm" className="uppercase tracking-[0.25em]" onClick={() => navigate('/admin/dashboard/merchant/payments')}>
              <i className="fas fa-wallet mr-2" aria-hidden="true" />
              Payments Desk
            </Button>
          </div>
        </header>

        {statusFilters.length > 0 && (
          <div className="border-b border-mono-gray-200 bg-mono-gray-50 px-8 py-4">
            <div className="flex flex-wrap gap-3">
              <Button
                variant={selectedStatus === null ? 'primary' : 'ghost'}
                size="sm"
                onClick={() => setSelectedStatus(null)}
              >
                All Merchants
              </Button>
              {statusFilters.map((status) => (
                <Button
                  key={String(status)}
                  variant={selectedStatus === status ? 'primary' : 'ghost'}
                  size="sm"
                  onClick={() => setSelectedStatus(status)}
                >
                  {resolveStatusLabel(status)}
                </Button>
              ))}
            </div>
          </div>
        )}

        <div className="grid gap-6 px-8 py-8 lg:grid-cols-4">
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Merchants</p>
              <p className="text-3xl font-semibold text-mono-black">{summary.totalMerchants}</p>
              <p className="text-sm text-mono-gray-600">Accounts under active portfolio</p>
            </div>
          </Card>
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">COD Outstanding</p>
              <p className="text-3xl font-semibold text-mono-black">{currencyFormatter.format(summary.codOutstanding)}</p>
              <p className="text-sm text-mono-gray-600">Awaiting settlement</p>
            </div>
          </Card>
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">COD Collected</p>
              <p className="text-3xl font-semibold text-mono-black">{currencyFormatter.format(summary.codCollected)}</p>
              <p className="text-sm text-mono-gray-600">Delivered & reconciled</p>
            </div>
          </Card>
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Active Shipments</p>
              <p className="text-3xl font-semibold text-mono-black">{summary.activeShipments}</p>
              <p className="text-sm text-mono-gray-600">Across all merchants</p>
            </div>
          </Card>
        </div>

        <div className="border-t border-mono-gray-200 px-8 py-8">
          {merchants.length === 0 ? (
            <Card className="text-center">
              <div className="space-y-3">
                <h2 className="text-xl font-semibold text-mono-black">No merchants onboarded yet</h2>
                <p className="text-sm text-mono-gray-600">Invite your first merchant to unlock settlement workflows.</p>
              </div>
            </Card>
          ) : (
            <div className="grid gap-6">
              {merchants.map((merchant: MerchantListItem) => (
                <Card
                  key={merchant.id}
                  className="border border-mono-gray-200 transition-transform hover:-translate-y-1"
                  header={
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                      <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Merchant #{merchant.id}</p>
                        <h2 className="text-xl font-semibold text-mono-black">{merchant.business_name}</h2>
                        <p className="text-sm text-mono-gray-600">
                          {merchant.primary_contact?.name ?? 'Contact pending'} • {merchant.primary_contact?.email ?? '—'}
                        </p>
                      </div>
                      <div className="flex flex-wrap items-center gap-3">
                        <Badge variant="outline" size="sm">{resolveStatusLabel(merchant.status)}</Badge>
                        <Button variant="primary" size="sm" className="uppercase tracking-[0.25em]" onClick={() => navigate(`/admin/dashboard/merchants/${merchant.id}`)}>
                          View Merchant
                        </Button>
                      </div>
                    </div>
                  }
                >
                  <div className="grid gap-6 lg:grid-cols-[2fr,1fr]">
                    <div className="space-y-4">
                      <div className="flex flex-wrap items-center gap-3 text-sm text-mono-gray-600">
                        <Button
                          variant="ghost"
                          size="sm"
                          className="uppercase tracking-[0.25em]"
                          disabled={!merchant.primary_contact?.phone}
                          onClick={() => handlePhoneDial(merchant.primary_contact?.phone)}
                        >
                          <i className="fas fa-phone mr-2" aria-hidden="true" />
                          Call
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          className="uppercase tracking-[0.25em]"
                          disabled={!merchant.primary_contact?.email}
                          onClick={() => handleEmail(merchant.primary_contact?.email)}
                        >
                          <i className="fas fa-envelope mr-2" aria-hidden="true" />
                          Email
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          className="uppercase tracking-[0.25em]"
                          onClick={() => handleCopyCodSnapshot(merchant)}
                        >
                          <i className="fas fa-clipboard-list mr-2" aria-hidden="true" />
                          {copiedMerchantId === merchant.id ? 'Copied' : 'COD Snapshot'}
                        </Button>
                      </div>

                      <div className="grid gap-4 md:grid-cols-2">
                        <div className="rounded-2xl border border-mono-gray-200 bg-mono-gray-50 p-4">
                          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">COD Outstanding</p>
                          <p className="text-lg font-semibold text-mono-black">{currencyFormatter.format(merchant.metrics.cod_open_balance)}</p>
                        </div>
                        <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4">
                          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">COD Collected</p>
                          <p className="text-lg font-semibold text-mono-black">{currencyFormatter.format(merchant.metrics.cod_collected)}</p>
                        </div>
                      </div>

                      <div className="grid gap-4 md:grid-cols-3">
                        <div className="text-sm text-mono-gray-600">
                          <span className="block text-xs uppercase tracking-[0.25em] text-mono-gray-500">Active Shipments</span>
                          <span className="text-mono-black">{merchant.metrics.active_shipments}</span>
                        </div>
                        <div className="text-sm text-mono-gray-600">
                          <span className="block text-xs uppercase tracking-[0.25em] text-mono-gray-500">Delivered</span>
                          <span className="text-mono-black">{merchant.metrics.delivered_shipments}</span>
                        </div>
                        <div className="text-sm text-mono-gray-600">
                          <span className="block text-xs uppercase tracking-[0.25em] text-mono-gray-500">Active Shops</span>
                          <span className="text-mono-black">{merchant.metrics.active_shops}</span>
                        </div>
                      </div>
                    </div>

                    <div className="space-y-3">
                      <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Shops</p>
                      {merchant.shops.length === 0 ? (
                        <p className="text-sm text-mono-gray-600">No shops registered.</p>
                      ) : (
                        <ul className="space-y-3 text-sm text-mono-gray-600">
                          {merchant.shops.map((shop) => (
                            <li key={shop.id} className="rounded-xl border border-mono-gray-200 p-3">
                              <p className="font-medium text-mono-black">{shop.name}</p>
                              <p>{shop.address ?? 'Address not set'}</p>
                              <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">{shop.contact_no ?? '—'}</p>
                            </li>
                          ))}
                        </ul>
                      )}
                    </div>
                  </div>
                </Card>
              ))}
            </div>
          )}
        </div>
      </section>
    </div>
  );
};

export default Merchants;
