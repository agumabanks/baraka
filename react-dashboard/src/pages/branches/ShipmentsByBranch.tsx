import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Badge from '../../components/ui/Badge';
import Input from '../../components/ui/Input';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import api from '../../services/api';

interface Branch {
  id: number | string;
  name: string;
  code: string;
  type: string;
}

interface Shipment {
  id: number;
  tracking_number: string;
  current_status: string;
  origin_branch: Branch;
  destination_branch: Branch;
  client: {
    id: number;
    business_name: string;
  };
  assigned_worker?: {
    id: number;
    first_name: string;
    last_name: string;
  };
  created_at: string;
}

interface Statistics {
  total: number;
  outbound: number;
  inbound: number;
  active: number;
  delivered_today: number;
}

const ShipmentsByBranch: React.FC = () => {
  const [selectedBranchId, setSelectedBranchId] = useState<number | string | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedStatus, setSelectedStatus] = useState('');
  const [viewType, setViewType] = useState<'origin' | 'destination' | 'both'>('both');
  const [currentPage, setCurrentPage] = useState(1);

  // Fetch branches
  const { data: branchesData } = useQuery({
    queryKey: ['branches-list'],
    queryFn: async () => {
      const response = await api.get('/v10/branches?per_page=100');
      return response.data;
    },
  });

  const branches: Branch[] = branchesData?.data?.items || [];
  const usingFallbackData = branches.some((branch) => typeof branch.id === 'string');

  // Fetch shipments by branch
  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['branch-shipments', selectedBranchId, viewType, searchTerm, selectedStatus, currentPage],
    queryFn: async () => {
      if (!selectedBranchId || typeof selectedBranchId !== 'number') {
        return null;
      }
      const response = await api.get(`/v10/branches/${selectedBranchId}/shipments`, {
        params: {
          view_type: viewType,
          search: searchTerm,
          status: selectedStatus,
          page: currentPage,
          per_page: 20,
        },
      });
      return response.data;
    },
    enabled: typeof selectedBranchId === 'number',
  });

  const shipments: Shipment[] = data?.data?.shipments || [];
  const statistics: Statistics = data?.data?.statistics || {
    total: 0,
    outbound: 0,
    inbound: 0,
    active: 0,
    delivered_today: 0,
  };
  const pagination = data?.pagination;
  const branchInfo: Branch | null = data?.data?.branch || null;

  const getStatusColor = (status: string) => {
    const lowerStatus = status.toLowerCase();
    if (lowerStatus.includes('delivered')) return 'bg-green-100 text-green-800';
    if (lowerStatus.includes('transit') || lowerStatus.includes('picked')) return 'bg-blue-100 text-blue-800';
    if (lowerStatus.includes('pending') || lowerStatus.includes('processing')) return 'bg-yellow-100 text-yellow-800';
    if (lowerStatus.includes('exception') || lowerStatus.includes('failed')) return 'bg-red-100 text-red-800';
    return 'bg-mono-gray-100 text-mono-gray-800';
  };

  return (
    <div className="space-y-6">
      <section className="rounded-3xl border border-mono-gray-200 bg-mono-white shadow-xl">
        <header className="flex flex-col gap-6 border-b border-mono-gray-200 px-8 py-10 xl:flex-row xl:items-center xl:justify-between">
          <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Shipment Tracking
            </p>
            <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
              Shipments by Branch
            </h1>
            <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
              Track and manage shipments flowing through each branch in your network.
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <Button variant="secondary" size="sm" className="uppercase tracking-[0.25em]" onClick={() => refetch()}>
              <i className="fas fa-sync-alt mr-2" />
              Refresh
            </Button>
          </div>
        </header>

        {/* Branch Selection */}
        <div className="px-8 py-6 border-b border-mono-gray-200 bg-mono-gray-50">
          <div className="space-y-4">
            <label className="block text-sm font-semibold text-mono-black">
              Select Branch *
            </label>
            <select
              className="w-full px-4 py-3 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black"
              value={selectedBranchId?.toString() ?? ''}
              onChange={(e) => {
                const value = e.target.value;
                if (!value) {
                  setSelectedBranchId(null);
                } else if (/^\d+$/.test(value)) {
                  setSelectedBranchId(Number(value));
                } else {
                  setSelectedBranchId(value);
                }
                setCurrentPage(1);
              }}
            >
              <option value="">-- Select a branch --</option>
              {branches.map(branch => (
                <option key={branch.id} value={branch.id}>
                  {branch.name} ({branch.code}) - {branch.type}
                </option>
              ))}
            </select>
            {usingFallbackData && (
              <p className="text-xs text-amber-700">
                Branch list is currently backed by demo data. Register a live branch to unlock shipment analytics.
              </p>
            )}
          </div>
        </div>

        {typeof selectedBranchId === 'number' && (
          <>
            {/* Statistics Cards */}
            {branchInfo && (
              <div className="px-8 py-6 border-b border-mono-gray-200">
                <div className="mb-4">
                  <h2 className="text-xl font-semibold text-mono-black">
                    {branchInfo.name} ({branchInfo.code})
                  </h2>
                  <p className="text-sm text-mono-gray-600">Branch Type: {branchInfo.type}</p>
                </div>
                <div className="grid gap-4 md:grid-cols-5">
                  <Card className="p-4">
                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Total</p>
                    <p className="text-2xl font-bold text-mono-black mt-2">{statistics.total}</p>
                  </Card>
                  <Card className="p-4">
                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Outbound</p>
                    <p className="text-2xl font-bold text-blue-600 mt-2">{statistics.outbound}</p>
                  </Card>
                  <Card className="p-4">
                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Inbound</p>
                    <p className="text-2xl font-bold text-green-600 mt-2">{statistics.inbound}</p>
                  </Card>
                  <Card className="p-4">
                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Active</p>
                    <p className="text-2xl font-bold text-orange-600 mt-2">{statistics.active}</p>
                  </Card>
                  <Card className="p-4">
                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Today</p>
                    <p className="text-2xl font-bold text-mono-black mt-2">{statistics.delivered_today}</p>
                  </Card>
                </div>
              </div>
            )}

            {/* Filters */}
            <div className="px-8 py-6 border-b border-mono-gray-200">
              <div className="space-y-4">
                <div className="flex gap-2">
                  <Button
                    variant={viewType === 'both' ? 'primary' : 'ghost'}
                    size="sm"
                    onClick={() => {
                      setViewType('both');
                      setCurrentPage(1);
                    }}
                  >
                    All Shipments
                  </Button>
                  <Button
                    variant={viewType === 'origin' ? 'primary' : 'ghost'}
                    size="sm"
                    onClick={() => {
                      setViewType('origin');
                      setCurrentPage(1);
                    }}
                  >
                    <i className="fas fa-arrow-right mr-2" />
                    Outbound
                  </Button>
                  <Button
                    variant={viewType === 'destination' ? 'primary' : 'ghost'}
                    size="sm"
                    onClick={() => {
                      setViewType('destination');
                      setCurrentPage(1);
                    }}
                  >
                    <i className="fas fa-arrow-left mr-2" />
                    Inbound
                  </Button>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                  <div className="md:col-span-2">
                    <Input
                      type="text"
                      placeholder="Search by tracking number or client..."
                      value={searchTerm}
                      onChange={(e) => {
                        setSearchTerm(e.target.value);
                        setCurrentPage(1);
                      }}
                      className="w-full"
                    />
                  </div>
                  <select
                    value={selectedStatus}
                    onChange={(e) => {
                      setSelectedStatus(e.target.value);
                      setCurrentPage(1);
                    }}
                    className="px-4 py-2 border border-mono-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-mono-black"
                  >
                    <option value="">All Statuses</option>
                    <option value="pending_processing">Pending Processing</option>
                    <option value="in_transit">In Transit</option>
                    <option value="out_for_delivery">Out for Delivery</option>
                    <option value="delivered">Delivered</option>
                    <option value="exception">Exception</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Shipments List */}
            <div className="px-8 py-8">
              {isLoading ? (
                <LoadingSpinner message="Loading shipments..." />
              ) : isError ? (
                <Card className="text-center border-2 border-red-200 bg-red-50">
                  <p className="text-red-600">
                    {error instanceof Error ? error.message : 'Failed to load shipments'}
                  </p>
                </Card>
              ) : shipments.length === 0 ? (
                <Card className="text-center">
                  <div className="space-y-3">
                    <h2 className="text-xl font-semibold text-mono-black">No shipments found</h2>
                    <p className="text-sm text-mono-gray-600">
                      No shipments match your current filters for this branch.
                    </p>
                  </div>
                </Card>
              ) : (
                <>
                  <div className="overflow-x-auto">
                    <table className="w-full">
                      <thead>
                        <tr className="border-b border-mono-gray-200 text-left text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                          <th className="pb-4">Tracking Number</th>
                          <th className="pb-4">Client</th>
                          <th className="pb-4">Origin</th>
                          <th className="pb-4">Destination</th>
                          <th className="pb-4">Status</th>
                          <th className="pb-4">Worker</th>
                          <th className="pb-4">Created</th>
                        </tr>
                      </thead>
                      <tbody>
                        {shipments.map((shipment) => (
                          <tr 
                            key={shipment.id} 
                            className="border-b border-mono-gray-100 hover:bg-mono-gray-50 transition-colors"
                          >
                            <td className="py-4">
                              <p className="font-mono font-semibold text-mono-black">
                                {shipment.tracking_number}
                              </p>
                            </td>
                            <td className="py-4">
                              <p className="text-mono-black">{shipment.client.business_name}</p>
                            </td>
                            <td className="py-4">
                              <div>
                                <p className="font-medium text-mono-black">{shipment.origin_branch.name}</p>
                                <p className="text-xs text-mono-gray-600">{shipment.origin_branch.code}</p>
                              </div>
                            </td>
                            <td className="py-4">
                              <div>
                                <p className="font-medium text-mono-black">{shipment.destination_branch.name}</p>
                                <p className="text-xs text-mono-gray-600">{shipment.destination_branch.code}</p>
                              </div>
                            </td>
                            <td className="py-4">
                              <Badge className={getStatusColor(shipment.current_status)} size="sm">
                                {shipment.current_status.replace(/_/g, ' ')}
                              </Badge>
                            </td>
                            <td className="py-4">
                              {shipment.assigned_worker ? (
                                <p className="text-sm text-mono-black">
                                  {shipment.assigned_worker.first_name} {shipment.assigned_worker.last_name}
                                </p>
                              ) : (
                                <p className="text-sm text-mono-gray-500">Unassigned</p>
                              )}
                            </td>
                            <td className="py-4">
                              <p className="text-sm text-mono-gray-600">
                                {new Date(shipment.created_at).toLocaleDateString()}
                              </p>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>

                  {/* Pagination */}
                  {pagination && pagination.last_page > 1 && (
                    <div className="mt-6 flex items-center justify-between">
                      <p className="text-sm text-mono-gray-600">
                        Page {pagination.current_page} of {pagination.last_page} ({pagination.total} total)
                      </p>
                      <div className="flex gap-2">
                        <Button
                          variant="secondary"
                          size="sm"
                          disabled={pagination.current_page === 1}
                          onClick={() => setCurrentPage(pagination.current_page - 1)}
                        >
                          Previous
                        </Button>
                        <Button
                          variant="secondary"
                          size="sm"
                          disabled={pagination.current_page === pagination.last_page}
                          onClick={() => setCurrentPage(pagination.current_page + 1)}
                        >
                          Next
                        </Button>
                      </div>
                    </div>
                  )}
                </>
              )}
            </div>
          </>
        )}

        {!selectedBranchId && (
          <div className="px-8 py-12">
            <Card className="text-center">
              <div className="space-y-3">
                <div className="inline-flex h-16 w-16 items-center justify-center rounded-full bg-mono-gray-100 text-mono-gray-600 mb-4">
                  <i className="fas fa-map-marker-alt text-2xl" aria-hidden="true" />
                </div>
                <h2 className="text-xl font-semibold text-mono-black">Select a Branch</h2>
                <p className="text-sm text-mono-gray-600">
                  Choose a branch from the dropdown above to view its shipments.
                </p>
              </div>
            </Card>
          </div>
        )}

        {selectedBranchId && typeof selectedBranchId !== 'number' && (
          <div className="px-8 py-10">
            <Card className="border border-amber-200 bg-amber-50 p-6 text-sm text-amber-800">
              Shipment analytics require a real branch record. Please create branches in the core system to replace the demo identifiers.
            </Card>
          </div>
        )}
      </section>
    </div>
  );
};

export default ShipmentsByBranch;
