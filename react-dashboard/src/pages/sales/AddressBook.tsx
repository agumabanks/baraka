import React, { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Select from '../../components/ui/Select';
import Input from '../../components/ui/Input';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import Spinner from '../../components/ui/Spinner';
import { salesApi } from '../../services/api';
import type {
  AddressBookType,
  PaginationMeta,
  SalesAddressBookEntry,
  SalesAddressBookListResponse,
  SalesSelectOption,
} from '../../types/sales';

const typeLabel: Record<AddressBookType, string> = {
  shipper: 'Shipper',
  consignee: 'Consignee',
  payer: 'Payer',
};

type AddressBookQueryParams = {
  page: number;
  per_page: number;
  type?: string;
  search?: string;
};

const buildParams = (params: AddressBookQueryParams) => Object.fromEntries(
  Object.entries(params).filter(([, value]) => value !== undefined && value !== '')
);

const AddressBook: React.FC = () => {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(10);
  const [typeFilter, setTypeFilter] = useState('');
  const [searchInput, setSearchInput] = useState('');
  const [searchTerm, setSearchTerm] = useState('');

  const queryParams = useMemo(
    () => buildParams({ page, per_page: perPage, type: typeFilter || undefined, search: searchTerm || undefined }),
    [page, perPage, typeFilter, searchTerm]
  );

  const { data, isLoading, isFetching, isError, error } = useQuery<SalesAddressBookListResponse, Error>({
    queryKey: ['sales', 'address-book', queryParams],
    queryFn: async () => {
      const response = await salesApi.getAddressBook(queryParams);
      return response.data;
    },
  });

  const entries = data?.items ?? [];
  const pagination: PaginationMeta | undefined = data?.pagination;
  const summary = data?.summary;
  const filters = data?.filters;

  const typeOptions = useMemo(() => {
    const base: SalesSelectOption[] = [{ value: '', label: 'All entry types' }];
    return filters ? base.concat(filters.type_options) : base;
  }, [filters]);

  if (isLoading && !data) {
    return (
      <div className="flex h-full items-center justify-center">
        <LoadingSpinner message="Loading address book" />
      </div>
    );
  }

  if (isError && !data) {
    return (
      <div className="space-y-4">
        <Card>
          <div className="space-y-2 text-center">
            <h1 className="text-xl font-semibold text-mono-black">Unable to load address book</h1>
            <p className="text-sm text-mono-gray-700">{error?.message ?? 'Something went wrong while fetching addresses.'}</p>
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
            Address Book
          </p>
          <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div className="space-y-3">
              <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
                Shipping Addresses
              </h1>
              <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
                Maintain preferred shipper, consignee, and payer profiles for frictionless fulfilment.
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
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Total Entries</p>
                <h2 className="text-3xl font-semibold text-mono-black">{summary.totals.all.toLocaleString()}</h2>
                <p className="text-sm text-mono-gray-600">Across all filtered customers</p>
              </div>
            </Card>
            <Card className="border border-mono-gray-200 shadow-inner">
              <div className="space-y-2">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Shippers</p>
                <h2 className="text-3xl font-semibold text-mono-black">{summary.totals.by_type.shipper?.toLocaleString() ?? 0}</h2>
              </div>
            </Card>
            <Card className="border border-mono-gray-200 shadow-inner">
              <div className="space-y-2">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Consignees</p>
                <h2 className="text-3xl font-semibold text-mono-black">{summary.totals.by_type.consignee?.toLocaleString() ?? 0}</h2>
              </div>
            </Card>
            <Card className="border border-mono-gray-200 shadow-inner">
              <div className="space-y-2">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Snapshot</p>
                <h2 className="text-3xl font-semibold text-mono-black">{new Date(summary.generated_at).toLocaleString()}</h2>
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
                  placeholder="Name, email, or city"
                  value={searchInput}
                  onChange={(event) => setSearchInput(event.target.value)}
                />
                <Select
                  label="Type"
                  value={typeFilter}
                  onChange={(event) => {
                    setTypeFilter(event.target.value);
                    setPage(1);
                  }}
                  options={typeOptions}
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
                    setTypeFilter('');
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
                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Name</th>
                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Type</th>
                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Contact</th>
                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Customer</th>
                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Location</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-mono-gray-200 bg-mono-white">
                  {entries.length === 0 && (
                    <tr>
                      <td colSpan={5} className="px-6 py-10 text-center text-sm text-mono-gray-600">
                        No address book entries match the current filters.
                      </td>
                    </tr>
                  )}
                  {entries.map((entry: SalesAddressBookEntry) => (
                    <tr key={entry.id} className="transition-colors hover:bg-mono-gray-50">
                      <td className="whitespace-nowrap px-6 py-4 text-sm font-semibold text-mono-black">
                        <div className="flex flex-col">
                          <span>{entry.name}</span>
                          <span className="text-xs text-mono-gray-500">{entry.email ?? '—'}</span>
                        </div>
                      </td>
                      <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                        {typeLabel[entry.type]}
                      </td>
                      <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                        <div className="flex flex-col">
                          <span>{entry.phone}</span>
                          <span className="text-xs text-mono-gray-500">{entry.tax_id ?? 'No tax ID'}</span>
                        </div>
                      </td>
                      <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                        {entry.customer?.name ?? '—'}
                      </td>
                      <td className="px-6 py-4 text-sm text-mono-gray-700">
                        <div className="flex flex-col">
                          <span>{entry.city}, {entry.country}</span>
                          <span className="text-xs text-mono-gray-500">{entry.address_line}</span>
                        </div>
                      </td>
                    </tr>
                  ))}
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

export default AddressBook;
