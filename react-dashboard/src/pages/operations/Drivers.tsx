import React, { useMemo, useState } from 'react';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Badge from '../../components/ui/Badge';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import DriverFormModal from '../../components/drivers/DriverFormModal';
import RosterFormModal from '../../components/drivers/RosterFormModal';
import { useBranchList } from '../../hooks/useBranches';
import {
  useDriverDetail,
  useDriverList,
  useDriverMutations,
  useDriverRosterList,
  useDriverRosterMutations,
  useDriverTimeLogMutations,
  useDriverTimeLogs,
} from '../../hooks/useDrivers';
import {
  DRIVER_STATUS_LABELS,
  EMPLOYMENT_STATUS_LABELS,
  DRIVER_TIME_LOG_LABELS,
  ROSTER_STATUS_LABELS,
  type DriverStatus,
  type EmploymentStatus,
  type DriverRecord,
  type DriverTimeLogType,
  type DriverRosterRecord,
} from '../../types/drivers';

const statusOptions = [{ value: undefined, label: 'All statuses' }].concat(
  Object.entries(DRIVER_STATUS_LABELS).map(([value, label]) => ({ value, label }))
);

const employmentOptions = [{ value: undefined, label: 'All employment states' }].concat(
  Object.entries(EMPLOYMENT_STATUS_LABELS).map(([value, label]) => ({ value, label }))
);

const timeLogOptions = Object.entries(DRIVER_TIME_LOG_LABELS).map(([value, label]) => ({ value, label }));

const DriversPage: React.FC = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<DriverStatus | undefined>(undefined);
  const [employmentFilter, setEmploymentFilter] = useState<EmploymentStatus | undefined>(undefined);
  const [branchFilter, setBranchFilter] = useState<number | undefined>(undefined);
  const [selectedDriverId, setSelectedDriverId] = useState<number | null>(null);

  const filters = useMemo(
    () => ({
      search: searchTerm || undefined,
      status: statusFilter,
      employment_status: employmentFilter,
      branch_id: branchFilter,
      per_page: 25,
    }),
    [branchFilter, employmentFilter, searchTerm, statusFilter]
  );

  const {
    data: driverList,
    isLoading: driversLoading,
    isError: driversError,
    refetch: refetchDrivers,
  } = useDriverList(filters);

  const branchesQuery = useBranchList({ per_page: 100 });
  const branchOptions = useMemo(() => branchesQuery.data?.items ?? [], [branchesQuery.data]);

  const {
    createDriver,
    updateDriver,
    toggleStatus,
  } = useDriverMutations();

  const { createRoster, updateRoster, deleteRoster } = useDriverRosterMutations();
  const { createLog } = useDriverTimeLogMutations();

  const [isDriverModalOpen, setDriverModalOpen] = useState(false);
  const [driverEditing, setDriverEditing] = useState<DriverRecord | null>(null);
  const [isRosterModalOpen, setRosterModalOpen] = useState(false);
  const [rosterEditing, setRosterEditing] = useState<DriverRosterRecord | null>(null);
  const [logType, setLogType] = useState<DriverTimeLogType>('CHECK_IN');
  const [logTimestamp, setLogTimestamp] = useState(() => new Date().toISOString().slice(0, 16));

  const driverDetail = useDriverDetail(selectedDriverId, Boolean(selectedDriverId));
  const driverRosters = useDriverRosterList(
    selectedDriverId ? { driver_id: selectedDriverId, per_page: 50 } : undefined,
    Boolean(selectedDriverId)
  );
  const driverLogs = useDriverTimeLogs(
    selectedDriverId ? { driver_id: selectedDriverId, per_page: 25 } : undefined,
    Boolean(selectedDriverId)
  );

  const handleCreateDriver = (payload: Parameters<typeof createDriver.mutate>[0]) => {
    createDriver.mutate(payload, {
      onSuccess: () => {
        setDriverModalOpen(false);
        refetchDrivers();
      },
    });
  };

  const handleUpdateDriver = (driverId: number | string, payload: Parameters<typeof updateDriver.mutate>[0]['payload']) => {
    updateDriver.mutate({ driverId, payload }, {
      onSuccess: () => {
        setDriverModalOpen(false);
        refetchDrivers();
      },
    });
  };

  const handleToggleStatus = (driver: DriverRecord) => {
    const nextStatus: DriverStatus = driver.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
    toggleStatus.mutate({ driverId: driver.id, status: nextStatus });
  };

  const openCreateDriverModal = () => {
    setDriverEditing(null);
    setDriverModalOpen(true);
  };

  const openEditDriverModal = (driver: DriverRecord) => {
    setDriverEditing(driver);
    setDriverModalOpen(true);
  };

  const openRosterModal = (roster?: DriverRosterRecord) => {
    setRosterEditing(roster ?? null);
    setRosterModalOpen(true);
  };

  const handleRosterSubmit = (payload: Parameters<typeof createRoster.mutate>[0]) => {
    if (rosterEditing) {
      updateRoster.mutate({ rosterId: rosterEditing.id, payload }, {
        onSuccess: () => {
          setRosterModalOpen(false);
          driverRosters.refetch();
        },
      });
    } else {
      createRoster.mutate(payload, {
        onSuccess: () => {
          setRosterModalOpen(false);
          driverRosters.refetch();
        },
      });
    }
  };

  const handleDeleteRoster = (rosterId: number) => {
    if (!confirm('Remove this shift?')) return;
    deleteRoster.mutate(rosterId, {
      onSuccess: () => {
        driverRosters.refetch();
      },
    });
  };

  const handleCreateLog = (event: React.FormEvent) => {
    event.preventDefault();
    if (!selectedDriverId) return;
    const isoTimestamp = new Date(logTimestamp).toISOString();
    createLog.mutate(
      {
        driver_id: selectedDriverId,
        log_type: logType,
        logged_at: isoTimestamp,
      },
      {
        onSuccess: () => {
          driverLogs.refetch();
        },
      }
    );
  };

  const resetFilters = () => {
    setSearchTerm('');
    setStatusFilter(undefined);
    setEmploymentFilter(undefined);
    setBranchFilter(undefined);
  };

  return (
    <div className="space-y-8">
      <header className="flex flex-col gap-4 border-b border-mono-gray-200 pb-4 md:flex-row md:items-center md:justify-between">
        <div className="space-y-2">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">OPERATIONS</p>
          <h1 className="text-3xl font-semibold text-mono-black">Driver Workforce</h1>
          <p className="text-sm text-mono-gray-600">
            Manage courier onboarding, rosters, and time tracking for the field operations team.
          </p>
        </div>
        <Button variant="primary" onClick={openCreateDriverModal}>
          <i className="fas fa-user-plus mr-2" aria-hidden="true" />
          Add Driver
        </Button>
      </header>

      <section className="space-y-6">
        <Card className="border border-mono-gray-200">
          <form
            className="grid gap-4 md:grid-cols-5"
            onSubmit={(event) => {
              event.preventDefault();
              refetchDrivers();
            }}
          >
            <label className="md:col-span-2">
              <span className="block text-xs font-semibold uppercase tracking-[0.2em] text-mono-gray-500">Search</span>
              <input
                type="search"
                value={searchTerm}
                onChange={(event) => setSearchTerm(event.target.value)}
                placeholder="Search name, phone, or code"
                className="mt-1 w-full rounded-lg border border-mono-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
              />
            </label>

            <label>
              <span className="block text-xs font-semibold uppercase tracking-[0.2em] text-mono-gray-500">Status</span>
              <select
                value={statusFilter ?? ''}
                onChange={(event) => setStatusFilter((event.target.value || undefined) as DriverStatus | undefined)}
                className="mt-1 w-full rounded-lg border border-mono-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
              >
                {statusOptions.map((option) => (
                  <option key={option.label} value={option.value ?? ''}>
                    {option.label}
                  </option>
                ))}
              </select>
            </label>

            <label>
              <span className="block text-xs font-semibold uppercase tracking-[0.2em] text-mono-gray-500">Employment</span>
              <select
                value={employmentFilter ?? ''}
                onChange={(event) =>
                  setEmploymentFilter((event.target.value || undefined) as EmploymentStatus | undefined)
                }
                className="mt-1 w-full rounded-lg border border-mono-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
              >
                {employmentOptions.map((option) => (
                  <option key={option.label} value={option.value ?? ''}>
                    {option.label}
                  </option>
                ))}
              </select>
            </label>

            <label>
              <span className="block text-xs font-semibold uppercase tracking-[0.2em] text-mono-gray-500">Branch</span>
              <select
                value={branchFilter ?? ''}
                onChange={(event) => setBranchFilter(event.target.value ? Number(event.target.value) : undefined)}
                className="mt-1 w-full rounded-lg border border-mono-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
              >
                <option value="">All branches</option>
                {branchOptions.map((branch) => (
                  <option key={branch.id} value={branch.id}>
                    {branch.name} ({branch.code})
                  </option>
                ))}
              </select>
            </label>

            <div className="flex items-end gap-2">
              <Button type="submit" variant="primary" size="sm" className="w-full">
                Apply
              </Button>
              <Button type="button" variant="secondary" size="sm" className="w-full" onClick={resetFilters}>
                Reset
              </Button>
            </div>
          </form>
        </Card>

        <Card className="border border-mono-gray-200">
          {driversLoading ? (
            <LoadingSpinner message="Loading drivers" />
          ) : driversError ? (
            <div className="flex flex-col items-center gap-3 py-10 text-center">
              <i className="fas fa-triangle-exclamation text-3xl text-red-500" aria-hidden="true" />
              <p className="text-sm text-mono-gray-600">Unable to load drivers. Try refreshing the list.</p>
              <Button variant="secondary" onClick={() => refetchDrivers()}>
                Retry
              </Button>
            </div>
          ) : driverList && driverList.data.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-mono-gray-200 text-sm">
                <thead className="bg-mono-gray-50">
                  <tr>
                    <th scope="col" className="px-4 py-3 text-left font-semibold text-mono-gray-600">Driver</th>
                    <th scope="col" className="px-4 py-3 text-left font-semibold text-mono-gray-600">Branch</th>
                    <th scope="col" className="px-4 py-3 text-left font-semibold text-mono-gray-600">Status</th>
                    <th scope="col" className="px-4 py-3 text-left font-semibold text-mono-gray-600">Employment</th>
                    <th scope="col" className="px-4 py-3 text-left font-semibold text-mono-gray-600">Contacts</th>
                    <th scope="col" className="px-4 py-3 text-left font-semibold text-mono-gray-600">License</th>
                    <th scope="col" className="px-4 py-3 text-left font-semibold text-mono-gray-600">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-mono-gray-100">
                  {driverList.data.map((driver) => (
                    <tr
                      key={driver.id}
                      className={`cursor-pointer transition-colors hover:bg-mono-gray-50 ${selectedDriverId === driver.id ? 'bg-mono-gray-100' : ''}`}
                      onClick={() => setSelectedDriverId(driver.id)}
                    >
                      <td className="px-4 py-3">
                        <div className="space-y-1">
                          <p className="font-semibold text-mono-black">{driver.name}</p>
                          <p className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">{driver.code}</p>
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        <p className="text-sm text-mono-gray-700">{driver.branch?.name ?? 'Unassigned'}</p>
                        <p className="text-xs text-mono-gray-500">{driver.branch?.code ?? '—'}</p>
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant={driver.status === 'ACTIVE' ? 'solid' : 'outline'} size="sm">
                          {DRIVER_STATUS_LABELS[driver.status]}
                        </Badge>
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant="outline" size="sm">{EMPLOYMENT_STATUS_LABELS[driver.employment_status]}</Badge>
                      </td>
                      <td className="px-4 py-3">
                        <p className="text-sm text-mono-gray-700">{driver.phone ?? '—'}</p>
                        <p className="text-xs text-mono-gray-500">{driver.email ?? '—'}</p>
                      </td>
                      <td className="px-4 py-3">
                        <p className="text-sm text-mono-gray-700">{driver.license_number ?? '—'}</p>
                        <p className="text-xs text-mono-gray-500">{driver.license_expiry ? new Date(driver.license_expiry).toLocaleDateString() : '—'}</p>
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex flex-wrap gap-2">
                          <Button
                            type="button"
                            variant="secondary"
                            size="xs"
                            onClick={(event) => {
                              event.stopPropagation();
                              openEditDriverModal(driver);
                            }}
                          >
                            Edit
                          </Button>
                          <Button
                            type="button"
                            variant="ghost"
                            size="xs"
                            onClick={(event) => {
                              event.stopPropagation();
                              handleToggleStatus(driver);
                            }}
                          >
                            {driver.status === 'ACTIVE' ? 'Pause' : 'Activate'}
                          </Button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="py-10 text-center text-sm text-mono-gray-600">No drivers found for the current filters.</div>
          )}
        </Card>
      </section>

      {selectedDriverId && (
        <section className="space-y-6">
          <div className="flex items-center justify-between">
            <h2 className="text-2xl font-semibold text-mono-black">Driver Profile</h2>
            {driverDetail.data && (
              <div className="flex gap-2">
                <Button variant="secondary" size="sm" onClick={() => openEditDriverModal(driverDetail.data)}>
                  Edit Details
                </Button>
                <Button
                  variant={driverDetail.data.status === 'ACTIVE' ? 'ghost' : 'primary'}
                  size="sm"
                  onClick={() => handleToggleStatus(driverDetail.data as DriverRecord)}
                >
                  {driverDetail.data.status === 'ACTIVE' ? 'Mark Inactive' : 'Activate'}
                </Button>
              </div>
            )}
          </div>

          <Card className="border border-mono-gray-200">
            {driverDetail.isLoading ? (
              <LoadingSpinner message="Loading driver" />
            ) : driverDetail.data ? (
              <div className="grid gap-6 md:grid-cols-2">
                <div className="space-y-3">
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-mono-gray-500">Identity</p>
                  <p className="text-xl font-semibold text-mono-black">{driverDetail.data.name}</p>
                  <div className="space-y-2 text-sm text-mono-gray-600">
                    <p>Driver code: <span className="font-medium text-mono-black">{driverDetail.data.code}</span></p>
                    <p>Branch: {driverDetail.data.branch?.name ?? 'Unassigned'}</p>
                    <p>Vehicle: {driverDetail.data.vehicle?.registration_number ?? 'Not assigned'}</p>
                  </div>
                </div>
                <div className="space-y-3">
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-mono-gray-500">Contact</p>
                  <div className="space-y-2 text-sm text-mono-gray-600">
                    <p>Email: {driverDetail.data.email ?? '—'}</p>
                    <p>Phone: {driverDetail.data.phone ?? '—'}</p>
                    <p>Onboarded: {driverDetail.data.onboarded_at ? new Date(driverDetail.data.onboarded_at).toLocaleString() : '—'}</p>
                  </div>
                </div>
              </div>
            ) : (
              <p className="py-6 text-center text-sm text-mono-gray-600">Driver unavailable.</p>
            )}
          </Card>

          <div className="grid gap-6 lg:grid-cols-2">
            <Card className="border border-mono-gray-200">
              <div className="flex items-center justify-between">
                <div>
                  <h3 className="text-lg font-semibold text-mono-black">Rosters</h3>
                  <p className="text-sm text-mono-gray-600">Scheduled and active shifts</p>
                </div>
                <Button variant="secondary" size="sm" onClick={() => openRosterModal()}>
                  <i className="fas fa-calendar-plus mr-2" aria-hidden="true" />
                  Add Shift
                </Button>
              </div>

              <div className="mt-4 space-y-4">
                {driverRosters.isLoading ? (
                  <LoadingSpinner message="Loading rosters" />
                ) : driverRosters.data && driverRosters.data.length > 0 ? (
                  driverRosters.data.map((roster) => (
                    <div key={roster.id} className="rounded-lg border border-mono-gray-200 p-4">
                      <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                          <p className="text-sm font-semibold text-mono-black">{roster.shift_type || 'General shift'}</p>
                          <p className="text-xs text-mono-gray-500">
                            {new Date(roster.start_time).toLocaleString()} → {new Date(roster.end_time).toLocaleString()}
                          </p>
                        </div>
                        <Badge variant="outline" size="sm">{ROSTER_STATUS_LABELS[roster.status]}</Badge>
                      </div>
                      <div className="mt-3 flex gap-2">
                        <Button variant="secondary" size="xs" onClick={() => openRosterModal(roster)}>
                          Edit
                        </Button>
                        <Button variant="ghost" size="xs" onClick={() => handleDeleteRoster(roster.id)}>
                          Remove
                        </Button>
                      </div>
                    </div>
                  ))
                ) : (
                  <p className="text-sm text-mono-gray-600">No roster records yet.</p>
                )}
              </div>
            </Card>

            <Card className="border border-mono-gray-200">
              <div className="flex items-center justify-between">
                <div>
                  <h3 className="text-lg font-semibold text-mono-black">Time Logs</h3>
                  <p className="text-sm text-mono-gray-600">Driver attendance events</p>
                </div>
              </div>

              <form className="mt-4 grid gap-3 md:grid-cols-[1fr,1fr,auto]" onSubmit={handleCreateLog}>
                <select
                  value={logType}
                  onChange={(event) => setLogType(event.target.value as DriverTimeLogType)}
                  className="rounded-lg border border-mono-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
                >
                  {timeLogOptions.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
                <input
                  type="datetime-local"
                  value={logTimestamp}
                  onChange={(event) => setLogTimestamp(event.target.value)}
                  className="rounded-lg border border-mono-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
                  required
                />
                <Button type="submit" variant="primary" size="sm">
                  Log Event
                </Button>
              </form>

              <div className="mt-4 space-y-3">
                {driverLogs.isLoading ? (
                  <LoadingSpinner message="Loading logs" />
                ) : driverLogs.data && driverLogs.data.length > 0 ? (
                  driverLogs.data.map((log) => (
                    <div key={log.id} className="flex items-center justify-between rounded-lg border border-mono-gray-200 px-4 py-3 text-sm">
                      <div>
                        <p className="font-medium text-mono-black">{DRIVER_TIME_LOG_LABELS[log.log_type]}</p>
                        <p className="text-xs text-mono-gray-500">
                          {new Date(log.logged_at).toLocaleString()}
                        </p>
                      </div>
                      {log.source && <span className="text-xs text-mono-gray-500">{log.source}</span>}
                    </div>
                  ))
                ) : (
                  <p className="text-sm text-mono-gray-600">No time logs recorded yet.</p>
                )}
              </div>
            </Card>
          </div>
        </section>
      )}

      {isDriverModalOpen && (
        <DriverFormModal
          isOpen={isDriverModalOpen}
          onClose={() => setDriverModalOpen(false)}
          initialData={driverEditing ?? undefined}
          branches={branchOptions}
          onSubmit={(payload) => {
            if (driverEditing) {
              handleUpdateDriver(driverEditing.id, payload);
            } else {
              handleCreateDriver(payload);
            }
          }}
          isSubmitting={createDriver.isPending || updateDriver.isPending}
        />
      )}

      {isRosterModalOpen && selectedDriverId && (
        <RosterFormModal
          isOpen={isRosterModalOpen}
          onClose={() => setRosterModalOpen(false)}
          driverId={selectedDriverId}
          branches={branchOptions}
          initialData={rosterEditing ?? undefined}
          onSubmit={handleRosterSubmit}
          isSubmitting={createRoster.isPending || updateRoster.isPending}
        />
      )}
    </div>
  );
};

export default DriversPage;
