import React, { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Select from '../../components/ui/Select';
import Input from '../../components/ui/Input';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import Badge from '../../components/ui/Badge';
import Spinner from '../../components/ui/Spinner';
import { salesApi } from '../../services/api';
import type {
  PaginationMeta,
  QuotationStatus,
  SalesQuotation,
  SalesQuotationListResponse,
  SalesSelectOption,
} from '../../types/sales';

const quotationStatusLabel: Record<QuotationStatus, string> = {
  draft: 'Draft',
  sent: 'Sent',
  accepted: 'Accepted',
  expired: 'Expired',
};

const statusBadgeVariant: Record<QuotationStatus, { variant: 'solid' | 'outline' | 'ghost'; className?: string }> = {
  draft: { variant: 'outline', className: 'border-mono-gray-400 text-mono-gray-900' },
  sent: { variant: 'ghost', className: 'text-mono-gray-700' },
  accepted: { variant: 'solid', className: 'bg-mono-black text-mono-white' },
  expired: { variant: 'solid', className: 'bg-red-600 text-white' },
};

type QuotationQueryParams = {
  page: number;
  per_page: number;
  status?: string;
  search?: string;
};

const buildQuotationParams = (params: QuotationQueryParams) => {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== '')
  );
};

const formatCurrency = (value: number, currency: string) => {
  try {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(value);
  } catch (error) {
    return `${currency} ${value.toFixed(2)}`;
  }
};

const Quotations: React.FC = () => {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(10);
  const [statusFilter, setStatusFilter] = useState('');
  const [searchInput, setSearchInput] = useState('');
  const [searchTerm, setSearchTerm] = useState('');

  const queryParams = useMemo(
    () => buildQuotationParams({ page, per_page: perPage, status: statusFilter || undefined, search: searchTerm || undefined }),
    [page, perPage, statusFilter, searchTerm]
  );

  const { data, isLoading, isFetching, isError, error } = useQuery<SalesQuotationListResponse, Error>({
    queryKey: ['sales', 'quotations', queryParams],
    queryFn: async () => {
      const response = await salesApi.getQuotations(queryParams);
      return response.data;
    },
  });

  const quotations = data?.items ?? [];
  const pagination: PaginationMeta | undefined = data?.pagination;
  const summary = data?.summary;
  const filters = data?.filters;

  const statusOptions = useMemo(() => {
    const base: SalesSelectOption[] = [{ value: '', label: 'All statuses' }];
    return filters ? base.concat(filters.status_options) : base;
  }, [filters]);

  if (isLoading && !data) {
    return (
      <div className="flex h-full items-center justify-center">
        <LoadingSpinner message="Loading quotations" />
      </div>
    );
  }

  if (isError && !data) {
    return (
      <div className="space-y-4">
        <Card>
          <div className="space-y-2 text-center">
            <h1 className="text-xl font-semibold text-mono-black">Unable to load quotations</h1>
            <p className="text-sm text-mono-gray-700">{error?.message ?? 'Something went wrong while fetching quotations.'}</p>
          </div>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-10">
      <section className="space-y-6">
        <header className="space-y-3">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
            Quotations
          </p>
          <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div className="space-y-3">
              <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
                Pipeline Quotations
              </h1>
              <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
                Track commercial proposals, monitor conversion signals, and ensure follow-up before expiry.
              </p>
            </div>
            {isFetching && (
              <div className="flex items-center gap-2 text-sm text-mono-gray-600">
                <Spinner size="sm" />
                <span className="uppercase tracking-[0.25em]">Refreshing</span>
              </div>
            )}
          </div>
        </header>

        {summary && (
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <Card className="border border-mono-gray-200 shadow-inner">
              <div className="space-y-2">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Total Quotations</p>
                <h2 className="text-3xl font-semibold text-mono-black">{summary.totals.all.toLocaleString()}</h2>
                <p className="text-sm text-mono-gray-600">Across all filtered records</p>
              </div>
            </Card>
            <Card className="border border-mono-gray-200 shadow-inner">
              <div className="space-y-2">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Total Value</p>
                <h2 className="text-3xl font-semibold text-mono-black">{formatCurrency(summary.value.total, summary.value.currency)}</h2>
                <p className="text-sm text-mono-gray-600">Average deal {formatCurrency(summary.value.average, summary.value.currency)}</p>
              </div>
            </Card>
            <Card className="border border-mono-gray-200 shadow-inner">
              <div className="space-y-2">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Status Mix</p>
                <p className="text-sm text-mono-gray-700">
                  {Object.entries(summary.totals.by_status).map(([status, count]) => `${quotationStatusLabel[status as QuotationStatus] ?? status}: ${count}`).join(' • ')}
                </p>
              </div>
            </Card>
            <Card className="border border-mono-gray-200 shadow-inner">
              <div className="space-y-2">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Latest Valid Until</p>
                <h2 className="text-3xl font-semibold text-mono-black">{summary.latest_valid_until ? new Date(summary.latest_valid_until).toLocaleDateString() : '—'}</h2>
                <p className="text-sm text-mono-gray-600">Generated {new Date(summary.generated_at).toLocaleString()}</p>
              </div>
            </Card>
          </div>
        )}

        <Card className="border border-mono-gray-200">
          <div className="flex flex-col gap-6">
            <form
              onSubmit={(event) => {
                event.preventDefault();
                setPage(1);
                setSearchTerm(searchInput.trim());
              }}
              className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
            >
              <div className="flex flex-1 flex-col gap-3 md:flex-row">
                <Input
                  label="Search"
                  placeholder="Reference, customer, or destination"
                  value={searchInput}
                  onChange={(event) => setSearchInput(event.target.value)}
                />
                <Select
                  label="Status"
                  value={statusFilter}
                  onChange={(event) => {
                    setStatusFilter(event.target.value);
                    setPage(1);
                  }}
                  options={statusOptions}
                />
              </div>
              <div className="flex items-center gap-2">
                <Button type="submit" variant="primary" className="uppercase tracking-[0.25em]">
                  Apply
                </Button>
                <Button
                  type="button"
                  variant="ghost"
                  className="uppercase tracking-[0.25em]"
                  onClick={() => {
                    setStatusFilter('');
                    setSearchInput('');
                    setSearchTerm('');
                    setPage(1);
                  }}
                >
                  Reset
                </Button>
              </div>
            </form>

            <div className="overflow-x-auto rounded-2xl border border-mono-gray-200">
              <table className="min-w-full divide-y divide-mono-gray-200">
                <thead className="bg-mono-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Reference</th>
                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Customer</th>
                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Service</th>
                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Destination</th>
                    <th className="px-6 py-3 text-right text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Amount</th>
                    <th className="px-6 py-3 text-center text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Status</th>
                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Valid Until</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-mono-gray-200 bg-mono-white">
                  {quotations.length === 0 && (
                    <tr>
                      <td colSpan={7} className="px-6 py-10 text-center text-sm text-mono-gray-600">
                        No quotations match the current filters.
                      </td>
                    </tr>
                  )}
                  {quotations.map((quotation: SalesQuotation) => {
                    const statusMeta = statusBadgeVariant[quotation.status];
                    return (
                      <tr key={quotation.id} className="transition-colors hover:bg-mono-gray-50">
                        <td className="whitespace-nowrap px-6 py-4 text-sm font-semibold text-mono-black">
                          {quotation.reference}
                        </td>
                        <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                          {quotation.customer?.name ?? '—'}
                        </td>
                        <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                          {quotation.service_type}
                        </td>
                        <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                          {quotation.destination_country ?? '—'}
                        </td>
                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm text-mono-gray-900">
                          {formatCurrency(quotation.total_amount, quotation.currency)}
                        </td>
                        <td className="whitespace-nowrap px-6 py-4 text-center text-sm text-mono-gray-900">
                          <Badge variant={statusMeta.variant} className={statusMeta.className ?? ''}>
                            {quotationStatusLabel[quotation.status]}
                          </Badge>
                        </td>
                        <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                          {quotation.valid_until ? new Date(quotation.valid_until).toLocaleDateString() : '—'}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>

            {pagination && (
              <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div className="flex items-center gap-2">
                  <Button
                    variant="secondary"
                    size="sm"
                    disabled={page <= 1}
                    onClick={() => setPage((current) => Math.max(1, current - 1))}
                  >
                    Previous
                  </Button>
                  <Button
                    variant="secondary"
                    size="sm"
                    disabled={page >= pagination.last_page}
                    onClick={() => setPage((current) => Math.min(pagination.last_page, current + 1))}
                  >
                    Next
                  </Button>
                </div>
                <div className="flex items-center gap-4 text-sm text-mono-gray-700">
                  <span>Page {pagination.current_page} of {pagination.last_page}</span>
                  <Select
                    label="Results"
                    value={String(perPage)}
                    onChange={(event) => {
                      setPerPage(Number(event.target.value));
                      setPage(1);
                    }}
                    options={[
                      { value: '10', label: '10 per page' },
                      { value: '25', label: '25 per page' },
                      { value: '50', label: '50 per page' },
                    ]}
                  />
                </div>
              </div>
            )}
          </div>
        </Card>
      </section>
    </div>
  );
};

export default Quotations;
