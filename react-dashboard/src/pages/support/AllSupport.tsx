import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Select from '../../components/ui/Select';
import Input from '../../components/ui/Input';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import Spinner from '../../components/ui/Spinner';
import { supportApi } from '../../services/api';
import type {
  PaginationMeta,
  Support,
  SupportListResponse,
  SupportPriority,
  SupportSelectOption,
  SupportStatus,
  SupportSummary,
} from '../../types/support';

const statusBadgeMap: Record<SupportStatus, { label: string; className: string }> = {
  pending: {
    label: 'Pending',
    className: 'bg-yellow-100 text-yellow-800 border border-yellow-300',
  },
  processing: {
    label: 'Processing',
    className: 'bg-blue-100 text-blue-800 border border-blue-300',
  },
  resolved: {
    label: 'Resolved',
    className: 'bg-green-100 text-green-800 border border-green-300',
  },
  closed: {
    label: 'Closed',
    className: 'bg-mono-gray-200 text-mono-gray-700',
  },
};

const priorityBadgeMap: Record<SupportPriority, { label: string; className: string }> = {
  low: {
    label: 'Low',
    className: 'bg-mono-gray-100 text-mono-gray-700',
  },
  medium: {
    label: 'Medium',
    className: 'bg-blue-100 text-blue-700',
  },
  high: {
    label: 'High',
    className: 'bg-orange-100 text-orange-700 border border-orange-300',
  },
  urgent: {
    label: 'Urgent',
    className: 'bg-red-100 text-red-700 border border-red-300',
  },
};

const computeSupportSummary = (tickets: Support[]): SupportSummary | undefined => {
  if (tickets.length === 0) {
    return undefined;
  }

  const statusTotals: Partial<Record<SupportStatus, number>> = {};
  const priorityTotals: Partial<Record<SupportPriority, number>> = {};

  tickets.forEach((ticket) => {
    const statusKey = (ticket.status ?? 'pending') as SupportStatus;
    statusTotals[statusKey] = (statusTotals[statusKey] ?? 0) + 1;

    const priorityKey = ticket.priority;
    priorityTotals[priorityKey] = (priorityTotals[priorityKey] ?? 0) + 1;
  });

  return {
    totals: {
      all: tickets.length,
      by_status: statusTotals,
    },
    by_priority: priorityTotals,
    generated_at: new Date().toISOString(),
  } satisfies SupportSummary;
};

const pageSizeOptions: SupportSelectOption[] = [
  { value: '10', label: '10 per page' },
  { value: '25', label: '25 per page' },
  { value: '50', label: '50 per page' },
];

type SupportQueryParams = {
  page: number;
  per_page: number;
  status?: string;
  priority?: string;
  department?: string;
  search?: string;
};

const buildQueryParams = (params: SupportQueryParams) => {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== '')
  );
};

const AllSupport: React.FC = () => {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(10);
  const [statusFilter, setStatusFilter] = useState('');
  const [priorityFilter, setPriorityFilter] = useState('');
  const [departmentFilter, setDepartmentFilter] = useState('');
  const [searchInput, setSearchInput] = useState('');
  const [searchTerm, setSearchTerm] = useState('');

  const queryParams = useMemo(
    () =>
      buildQueryParams({
        page,
        per_page: perPage,
        status: statusFilter || undefined,
        priority: priorityFilter || undefined,
        department: departmentFilter || undefined,
        search: searchTerm || undefined,
      }),
    [page, perPage, statusFilter, priorityFilter, departmentFilter, searchTerm]
  );

  const {
    data,
    isLoading,
    isFetching,
    isError,
    error,
  } = useQuery<SupportListResponse, Error>({
    queryKey: ['support', 'tickets', queryParams],
    queryFn: () => supportApi.getSupports(queryParams),
  });

  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const supports = data?.supports ?? [];
  const pagination: PaginationMeta | undefined = data?.pagination;
  const summaryFromApi = data?.summary;
  const filters = data?.filters;

  const [openActionMenuId, setOpenActionMenuId] = useState<number | null>(null);

  const statusOptions = useMemo(() => {
    const base: SupportSelectOption[] = [{ value: '', label: 'All statuses' }];
    return filters ? base.concat(filters.status_options) : base;
  }, [filters]);

  const priorityOptions = useMemo(() => {
    const base: SupportSelectOption[] = [{ value: '', label: 'All priorities' }];
    return filters ? base.concat(filters.priority_options) : base;
  }, [filters]);

  const departmentOptions = useMemo(() => {
    const base: SupportSelectOption[] = [{ value: '', label: 'All departments' }];
    return filters ? base.concat(filters.department_options) : base;
  }, [filters]);

  const handleSearchSubmit = useCallback((event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setPage(1);
    setSearchTerm(searchInput.trim());
  }, [searchInput]);

  const handleResetFilters = useCallback(() => {
    setStatusFilter('');
    setPriorityFilter('');
    setDepartmentFilter('');
    setSearchInput('');
    setSearchTerm('');
    setPage(1);
  }, []);

  const handleDeleteSupport = useCallback(async (support: Support) => {
    const confirmed = window.confirm(`Delete support ticket "${support.subject}"? This action cannot be undone.`);
    if (!confirmed) {
      return;
    }

    try {
      await supportApi.deleteSupport(support.id);
      await queryClient.invalidateQueries({ queryKey: ['support', 'tickets'] });
      setOpenActionMenuId(null);
      window.alert('Support ticket deleted successfully.');
    } catch (error) {
      console.error('Failed to delete support ticket', error);
      window.alert('Failed to delete support ticket. Please try again.');
    }
  }, [queryClient]);

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

  useEffect(() => {
    const handleDocumentClick = () => setOpenActionMenuId(null);
    document.addEventListener('click', handleDocumentClick);
    return () => document.removeEventListener('click', handleDocumentClick);
  }, []);

  if (isLoading && !data) {
    return (
      <div className="flex h-full items-center justify-center">
        <LoadingSpinner message="Loading support tickets" />
      </div>
    );
  }

  if (isError && !data) {
    return (
      <div className="space-y-4">
        <Card>
          <div className="space-y-2 text-center">
            <h1 className="text-xl font-semibold text-mono-black">Unable to load support tickets</h1>
            <p className="text-sm text-mono-gray-700">{error?.message ?? 'Something went wrong while fetching support data.'}</p>
            <Button onClick={() => window.location.reload()} variant="primary">
              Retry
            </Button>
          </div>
        </Card>
      </div>
    );
  }

  const derivedSummary = summaryFromApi ?? computeSupportSummary(supports);

  const renderSummary = () => {
    if (!derivedSummary) {
      return null;
    }

    return (
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Total Tickets</p>
            <h2 className="text-3xl font-semibold text-mono-black">{derivedSummary.totals.all.toLocaleString()}</h2>
            <p className="text-sm text-mono-gray-600">All support requests</p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Pending</p>
            <h2 className="text-3xl font-semibold text-mono-black">{(derivedSummary.totals.by_status.pending ?? 0).toLocaleString()}</h2>
            <p className="text-sm text-mono-gray-600">Awaiting response</p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Processing</p>
            <h2 className="text-3xl font-semibold text-mono-black">{(derivedSummary.totals.by_status.processing ?? 0).toLocaleString()}</h2>
            <p className="text-sm text-mono-gray-600">Currently being handled</p>
          </div>
        </Card>
        <Card className="border border-mono-gray-200 shadow-inner">
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Urgent</p>
            <h2 className="text-3xl font-semibold text-mono-black">{(derivedSummary.by_priority.urgent ?? 0).toLocaleString()}</h2>
            <p className="text-sm text-mono-gray-600">High priority issues</p>
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
              Ticket
            </th>
            <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              User
            </th>
            <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Department
            </th>
            <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Priority
            </th>
            <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Status
            </th>
            <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Date
            </th>
            <th scope="col" className="px-6 py-3 text-right text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Actions
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-mono-gray-200 bg-mono-white">
          {supports.length === 0 && (
            <tr>
              <td colSpan={7} className="px-6 py-10 text-center text-sm text-mono-gray-600">
                No support tickets match the current filters.
              </td>
            </tr>
          )}
          {supports.map((support: Support) => {
            const statusBadge = statusBadgeMap[support.status ?? 'pending'];
            const priorityBadge = priorityBadgeMap[support.priority];
            return (
              <tr key={support.id} className="transition-colors hover:bg-mono-gray-50">
                <td className="whitespace-nowrap px-6 py-4">
                  <div className="flex flex-col">
                    <span className="text-sm font-semibold text-mono-black">{support.subject}</span>
                    <span className="text-xs text-mono-gray-500">
                      ID: {support.id}
                    </span>
                  </div>
                </td>
                <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                  <div className="flex flex-col">
                    <span className="font-medium">{support.userName}</span>
                    <span className="text-xs text-mono-gray-500">{support.userEmail}</span>
                  </div>
                </td>
                <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                  {support.department}
                </td>
                <td className="whitespace-nowrap px-6 py-4 text-sm">
                  <span className={`rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] ${priorityBadge.className}`}>
                    {priorityBadge.label}
                  </span>
                </td>
                <td className="whitespace-nowrap px-6 py-4 text-sm">
                  <span className={`rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] ${statusBadge.className}`}>
                    {statusBadge.label}
                  </span>
                </td>
                <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                  {support.date}
                </td>
                <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                  <div className="relative inline-block text-left" onClick={(event) => event.stopPropagation()}>
                    <Button
                      variant="secondary"
                      size="sm"
                      className="uppercase tracking-[0.2em]"
                      onClick={() => setOpenActionMenuId((prev) => (prev === support.id ? null : support.id))}
                    >
                      Actions
                    </Button>
                    {openActionMenuId === support.id && (
                      <div className="absolute right-0 z-20 mt-2 w-48 origin-top-right rounded-xl border border-mono-gray-200 bg-mono-white shadow-2xl">
                        <button
                          type="button"
                          className="block w-full px-4 py-2 text-left text-sm text-mono-gray-700 hover:bg-mono-gray-50"
                          onClick={() => {
                            navigate(`/admin/dashboard/support/${support.id}`);
                            setOpenActionMenuId(null);
                          }}
                        >
                          View Details
                        </button>
                        <button
                          type="button"
                          className="block w-full px-4 py-2 text-left text-sm text-mono-gray-700 hover:bg-mono-gray-50"
                          onClick={() => {
                            navigate(`/admin/dashboard/support/${support.id}/edit`);
                            setOpenActionMenuId(null);
                          }}
                        >
                          Edit Ticket
                        </button>
                        <div className="border-t border-mono-gray-200" />
                        <button
                          type="button"
                          className="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                          onClick={() => handleDeleteSupport(support)}
                        >
                          Delete Ticket
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
            Customer Support
          </p>
          <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div className="space-y-3">
              <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
                Support Tickets
              </h1>
              <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
                Manage customer support requests, track ticket status, and respond to inquiries across all departments.
              </p>
            </div>
            <div className="flex items-center gap-3 text-sm text-mono-gray-600">
              <Button
                variant="primary"
                className="uppercase tracking-[0.25em]"
                onClick={() => navigate('/admin/dashboard/support/create')}
              >
                New Ticket
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
                  placeholder="Search by subject, user, or description"
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
                <Select
                  label="Priority"
                  value={priorityFilter}
                  onChange={(event) => {
                    setPriorityFilter(event.target.value);
                    setPage(1);
                  }}
                  options={priorityOptions}
                />
                <Select
                  label="Department"
                  value={departmentFilter}
                  onChange={(event) => {
                    setDepartmentFilter(event.target.value);
                    setPage(1);
                  }}
                  options={departmentOptions}
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
    </div>
  );
};

export default AllSupport;
