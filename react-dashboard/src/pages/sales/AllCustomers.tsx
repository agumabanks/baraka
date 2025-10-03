import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Select from '../../components/ui/Select';
import Input from '../../components/ui/Input';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import Spinner from '../../components/ui/Spinner';
import { salesApi } from '../../services/api';
import CustomerActionModal from '../../components/sales/CustomerActionModal';
import type { ActionView } from '../../components/sales/CustomerActionModal';
import type {
  PaginationMeta,
  SalesCustomer,
  SalesCustomerListResponse,
  SalesEngagementStatus,
  SalesSelectOption,
} from '../../types/sales';

const engagementBadgeMap: Record<SalesEngagementStatus, { label: string; className: string }> = {
  active: {
    label: 'Active',
    className: 'bg-mono-black text-mono-white',
  },
  at_risk: {
    label: 'At Risk',
    className: 'border border-mono-gray-400 text-mono-gray-900 bg-mono-white',
  },
  dormant: {
    label: 'Dormant',
    className: 'bg-mono-gray-200 text-mono-gray-700',
  },
};

const pageSizeOptions: SalesSelectOption[] = [
  { value: '10', label: '10 per page' },
  { value: '25', label: '25 per page' },
  { value: '50', label: '50 per page' },
];

type CustomerQueryParams = {
  page: number;
  per_page: number;
  status?: string;
  engagement?: string;
  hub_id?: string;
  search?: string;
};

const buildQueryParams = (params: CustomerQueryParams) => {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== '')
  );
};

const AllCustomers: React.FC = () => {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(10);
  const [statusFilter, setStatusFilter] = useState('');
  const [engagementFilter, setEngagementFilter] = useState('');
  const [hubFilter, setHubFilter] = useState('');
  const [searchInput, setSearchInput] = useState('');
  const [searchTerm, setSearchTerm] = useState('');

  const queryParams = useMemo(
    () =>
      buildQueryParams({
        page,
        per_page: perPage,
        status: statusFilter || undefined,
        engagement: engagementFilter || undefined,
        hub_id: hubFilter || undefined,
        search: searchTerm || undefined,
      }),
    [page, perPage, statusFilter, engagementFilter, hubFilter, searchTerm]
  );

  const {
    data,
    isLoading,
    isFetching,
    isError,
    error,
  } = useQuery<SalesCustomerListResponse, Error>({
    queryKey: ['sales', 'customers', queryParams],
    queryFn: async () => {
      const response = await salesApi.getCustomers(queryParams);
      return response.data;
    },
  });

  const navigate = useNavigate();

  const queryClient = useQueryClient();

  const customers = data?.items ?? [];
  const pagination: PaginationMeta | undefined = data?.pagination;
  const summary = data?.summary;
  const filters = data?.filters;

  const [selectedCustomer, setSelectedCustomer] = useState<SalesCustomer | null>(null);
  const [selectedActionView, setSelectedActionView] = useState<ActionView>('overview');
  const [isActionModalOpen, setActionModalOpen] = useState(false);
  const [openActionMenuId, setOpenActionMenuId] = useState<number | null>(null);

  const engagementOptions = useMemo(() => {
    const base: SalesSelectOption[] = [{ value: '', label: 'All engagement states' }];
    return filters ? base.concat(filters.engagement_options) : base;
  }, [filters]);

  const statusOptions = useMemo(() => {
    const base: SalesSelectOption[] = [{ value: '', label: 'All account states' }];
    return filters ? base.concat(filters.status_options) : base;
  }, [filters]);

  const hubOptions = useMemo(() => {
    const base: SalesSelectOption[] = [{ value: '', label: 'All hubs' }];
    return filters ? base.concat(filters.hub_options) : base;
  }, [filters]);

  const handleSearchSubmit = useCallback((event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setPage(1);
    setSearchTerm(searchInput.trim());
  }, [searchInput]);

  const handleResetFilters = useCallback(() => {
    setStatusFilter('');
    setEngagementFilter('');
    setHubFilter('');
    setSearchInput('');
    setSearchTerm('');
    setPage(1);
  }, []);

  const handleOpenModalWithView = useCallback((customer: SalesCustomer, view: ActionView) => {
    setSelectedCustomer(customer);
    setSelectedActionView(view);
    setActionModalOpen(true);
    setOpenActionMenuId(null);
  }, []);

  const handleCloseModal = useCallback(() => {
    setActionModalOpen(false);
  }, []);

  const handleCustomerUpdated = useCallback((updatedCustomer: SalesCustomer) => {
    setSelectedCustomer(updatedCustomer);
  }, []);

  useEffect(() => {
    const handleDocumentClick = () => setOpenActionMenuId(null);
    document.addEventListener('click', handleDocumentClick);
    return () => document.removeEventListener('click', handleDocumentClick);
  }, []);

  useEffect(() => {
    if (!selectedCustomer) {
      return;
    }
    const latest = customers.find((customer) => customer.id === selectedCustomer.id);
    if (latest) {
      setSelectedCustomer(latest);
    }
  }, [customers, selectedCustomer]);

  const handleDeleteCustomer = useCallback(async (customer: SalesCustomer) => {
    const confirmed = window.confirm(`Delete client “${customer.name}”? This action cannot be undone.`);
    if (!confirmed) {
      return;
    }

    try {
      await salesApi.deleteCustomer(customer.id);
      await queryClient.invalidateQueries({ queryKey: ['sales', 'customers'] });
      if (selectedCustomer?.id === customer.id) {
        setSelectedCustomer(null);
        setActionModalOpen(false);
      }
      setOpenActionMenuId(null);
      window.alert('Customer deleted successfully.');
    } catch (error) {
      console.error('Failed to delete customer', error);
      window.alert('Failed to delete customer. Please try again.');
    }
  }, [queryClient, selectedCustomer]);

  const handlePageChange = useCallback((direction: 'prev' | 'next') => {
    setPage((current) => {
      if (!pagination) {
        return current;
      }
      if (direction === 'prev') {
        return Math.max(1, current - 1);
      }
      return Math.min(pagination.last_page, current + 1);
    });
  }, [pagination]);

  if (isLoading && !data) {
    return (
      <div className="flex h-full items-center justify-center">
        <LoadingSpinner message="Loading customers" />
      </div>
    );
  }

  if (isError && !data) {
    return (
      <div className="space-y-4">
        <Card>
          <div className="space-y-2 text-center">
            <h1 className="text-xl font-semibold text-mono-black">Unable to load customers</h1>
            <p className="text-sm text-mono-gray-700">{error?.message ?? 'Something went wrong while fetching customer data.'}</p>
            <Button onClick={() => window.location.reload()} variant="primary">
              Retry
            </Button>
          </div>
        </Card>
      </div>
    );
  }

  const renderSummary = () => {
    if (!summary) {
      return null;
    }

    return (
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Portfolio</p>
            <h2 className="text-3xl font-semibold text-mono-black">{summary.totals.customers.toLocaleString()} Customers</h2>
            <p className="text-sm text-mono-gray-600">Across all filtered accounts</p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Engagement</p>
            <h2 className="text-3xl font-semibold text-mono-black">{summary.totals.active.toLocaleString()} Active</h2>
            <p className="text-sm text-mono-gray-600">{summary.totals.at_risk.toLocaleString()} at-risk • {summary.totals.dormant.toLocaleString()} dormant</p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Fulfilment</p>
            <h2 className="text-3xl font-semibold text-mono-black">{summary.shipments.this_month.toLocaleString()} Shipments</h2>
            <p className="text-sm text-mono-gray-600">Dispatched this month</p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Lifecycle</p>
            <h2 className="text-3xl font-semibold text-mono-black">{summary.shipments.lifetime.toLocaleString()} Lifetime</h2>
            <p className="text-sm text-mono-gray-600">Report generated {new Date(summary.generated_at).toLocaleString()}</p>
          </div>
        </Card>
      </div>
    );
  };

  const renderTable = () => (
    <div className="overflow-x-auto rounded-2xl border border-mono-gray-200">
      <table className="min-w-full divide-y divide-mono-gray-200">
        <thead className="bg-mono-gray-50">
          <tr>
            <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Account
            </th>
            <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Contact
            </th>
            <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              30-Day Volume
            </th>
            <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Lifetime Volume
            </th>
            <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Engagement
            </th>
            <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Last Activity
            </th>
            <th scope="col" className="px-6 py-3 text-right text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Actions
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-mono-gray-200 bg-mono-white">
          {customers.length === 0 && (
            <tr>
              <td colSpan={7} className="px-6 py-10 text-center text-sm text-mono-gray-600">
                No customers match the current filters.
              </td>
            </tr>
          )}
          {customers.map((customer: SalesCustomer) => {
            const badge = engagementBadgeMap[customer.status.engagement];
            return (
              <tr key={customer.id} className="transition-colors hover:bg-mono-gray-50">
                <td className="whitespace-nowrap px-6 py-4">
                  <div className="flex flex-col">
                    <span className="text-sm font-semibold text-mono-black">{customer.name}</span>
                    <span className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">
                      {customer.hub?.name ?? 'Unassigned'}
                    </span>
                  </div>
                </td>
                <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                  <div className="flex flex-col">
                    <span>{customer.email ?? '—'}</span>
                    <span className="text-xs text-mono-gray-500">{customer.phone ?? 'No phone on file'}</span>
                  </div>
                </td>
                <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                  {customer.shipments.this_month.toLocaleString()} shipments
                </td>
                <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                  {customer.shipments.total.toLocaleString()} shipments
                </td>
                <td className="whitespace-nowrap px-6 py-4 text-sm">
                  <span className={`rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] ${badge.className}`}>
                    {badge.label}
                  </span>
                </td>
                <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                  {customer.last_activity_human ?? 'No activity recorded'}
                </td>
                <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                  <div className="relative inline-block text-left" onClick={(event) => event.stopPropagation()}>
                    <Button
                      variant="secondary"
                      size="sm"
                      className="uppercase tracking-[0.2em]"
                      onClick={() => setOpenActionMenuId((prev) => (prev === customer.id ? null : customer.id))}
                    >
                      Manage
                    </Button>
                    {openActionMenuId === customer.id && (
                      <div className="absolute right-0 z-20 mt-2 w-48 origin-top-right rounded-xl border border-mono-gray-200 bg-mono-white shadow-2xl">
                        <button
                          type="button"
                          className="block w-full px-4 py-2 text-left text-sm text-mono-gray-700 hover:bg-mono-gray-50"
                          onClick={() => handleOpenModalWithView(customer, 'overview')}
                        >
                          View Profile
                        </button>
                        <button
                          type="button"
                          className="block w-full px-4 py-2 text-left text-sm text-mono-gray-700 hover:bg-mono-gray-50"
                          onClick={() => handleOpenModalWithView(customer, 'edit')}
                        >
                          Edit Details
                        </button>
                        <button
                          type="button"
                          className="block w-full px-4 py-2 text-left text-sm text-mono-gray-700 hover:bg-mono-gray-50"
                          onClick={() => handleOpenModalWithView(customer, 'shipments')}
                        >
                          Shipment History
                        </button>
                        <button
                          type="button"
                          className="block w-full px-4 py-2 text-left text-sm text-mono-gray-700 hover:bg-mono-gray-50"
                          onClick={() => handleOpenModalWithView(customer, 'billing')}
                        >
                          Billing Records
                        </button>
                        <button
                          type="button"
                          className="block w-full px-4 py-2 text-left text-sm text-mono-gray-700 hover:bg-mono-gray-50"
                          onClick={() => handleOpenModalWithView(customer, 'payments')}
                        >
                          Payment Tracking
                        </button>
                        <button
                          type="button"
                          className="block w-full px-4 py-2 text-left text-sm text-mono-gray-700 hover:bg-mono-gray-50"
                          onClick={() => handleOpenModalWithView(customer, 'preferences')}
                        >
                          Delivery Preferences
                        </button>
                        <button
                          type="button"
                          className="block w-full px-4 py-2 text-left text-sm text-mono-gray-700 hover:bg-mono-gray-50"
                          onClick={() => handleOpenModalWithView(customer, 'feedback')}
                        >
                          Feedback & Analytics
                        </button>
                        <button
                          type="button"
                          className="block w-full px-4 py-2 text-left text-sm text-mono-gray-700 hover:bg-mono-gray-50"
                          onClick={() => handleOpenModalWithView(customer, 'history')}
                        >
                          Historical Archive
                        </button>
                        <button
                          type="button"
                          className="block w-full px-4 py-2 text-left text-sm text-mono-gray-700 hover:bg-mono-gray-50"
                          onClick={() => handleOpenModalWithView(customer, 'quotation')}
                        >
                          New Quotation
                        </button>
                        <button
                          type="button"
                          className="block w-full px-4 py-2 text-left text-sm text-mono-gray-700 hover:bg-mono-gray-50"
                          onClick={() => handleOpenModalWithView(customer, 'contract')}
                        >
                          New Contract
                        </button>
                        <button
                          type="button"
                          className="block w-full px-4 py-2 text-left text-sm text-mono-gray-700 hover:bg-mono-gray-50"
                          onClick={() => handleOpenModalWithView(customer, 'address')}
                        >
                          Add Address
                        </button>
                        <div className="border-t border-mono-gray-200" />
                        <button
                          type="button"
                          className="block w-full px-4 py-2 text-left text-sm text-mono-gray-700 hover:bg-mono-gray-50"
                          onClick={() => {
                            setOpenActionMenuId(null);
                            navigate('/dashboard/customers/create');
                          }}
                        >
                          Add New Client
                        </button>
                        <button
                          type="button"
                          className="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                          onClick={() => handleDeleteCustomer(customer)}
                        >
                          Delete Client
                        </button>
                      </div>
                    )}
                  </div>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );

  return (
    <div className="space-y-10">
      <section className="space-y-6">
        <header className="space-y-3">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
            Sales Intelligence
          </p>
          <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div className="space-y-3">
              <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
                Customer Portfolio
              </h1>
              <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
                Monitor strategic accounts, spot retention risks, and keep tabs on fulfilment cadence across the network.
              </p>
            </div>
            <div className="flex items-center gap-3 text-sm text-mono-gray-600">
              <Button
                variant="primary"
                className="uppercase tracking-[0.25em]"
                onClick={() => navigate('/dashboard/customers/create')}
              >
                New Customer
              </Button>
              {isFetching && (
                <div className="flex items-center gap-2">
                  <Spinner size="sm" />
                  <span className="uppercase tracking-[0.25em]">Refreshing</span>
                </div>
              )}
            </div>
          </div>
        </header>

        {renderSummary()}

        <Card className="border border-mono-gray-200">
          <div className="flex flex-col gap-6">
            <form onSubmit={handleSearchSubmit} className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
              <div className="flex flex-1 flex-col gap-3 md:flex-row">
                <Input
                  label="Search"
                  placeholder="Search by name, email, or phone"
                  value={searchInput}
                  onChange={(event) => setSearchInput(event.target.value)}
                />
                <Select
                  label="Engagement"
                  value={engagementFilter}
                  onChange={(event) => {
                    setEngagementFilter(event.target.value);
                    setPage(1);
                  }}
                  options={engagementOptions}
                />
                <Select
                  label="Account"
                  value={statusFilter}
                  onChange={(event) => {
                    setStatusFilter(event.target.value);
                    setPage(1);
                  }}
                  options={statusOptions}
                />
                <Select
                  label="Hub"
                  value={hubFilter}
                  onChange={(event) => {
                    setHubFilter(event.target.value);
                    setPage(1);
                  }}
                  options={hubOptions}
                />
              </div>
              <div className="flex items-center gap-2">
                <Button type="submit" variant="primary" className="uppercase tracking-[0.25em]">
                  Apply
                </Button>
                <Button type="button" variant="ghost" className="uppercase tracking-[0.25em]" onClick={handleResetFilters}>
                  Reset
                </Button>
              </div>
            </form>

            {renderTable()}

            {pagination && (
              <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div className="flex items-center gap-2">
                  <Button
                    variant="secondary"
                    size="sm"
                    disabled={page <= 1}
                    onClick={() => handlePageChange('prev')}
                  >
                    Previous
                  </Button>
                  <Button
                    variant="secondary"
                    size="sm"
                    disabled={page >= pagination.last_page}
                    onClick={() => handlePageChange('next')}
                  >
                    Next
                  </Button>
                </div>
                <div className="flex items-center gap-4 text-sm text-mono-gray-700">
                  <span>
                    Page {pagination.current_page} of {pagination.last_page}
                  </span>
                  <Select
                    label="Results"
                    value={String(perPage)}
                    onChange={(event) => {
                      setPerPage(Number(event.target.value));
                      setPage(1);
                    }}
                    options={pageSizeOptions}
                  />
                </div>
              </div>
            )}
          </div>
        </Card>
      </section>

      {selectedCustomer && (
        <CustomerActionModal
          customer={selectedCustomer}
          hubOptions={filters?.hub_options ?? []}
          isOpen={isActionModalOpen}
          onClose={handleCloseModal}
          onUpdated={handleCustomerUpdated}
          initialView={selectedActionView}
        />
      )}
    </div>
  );
};

export default AllCustomers;
