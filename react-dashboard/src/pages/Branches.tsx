import React, { useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Badge from '../components/ui/Badge';
import LoadingSpinner from '../components/ui/LoadingSpinner';
import { useBranchList } from '../hooks/useBranches';
import type { BranchListItem, BranchOperationalState, BranchQueue } from '../types/branches';

const statusBadge: Record<BranchOperationalState, React.ReactNode> = {
  operational: (
    <Badge variant="solid" size="sm" className="bg-mono-black text-mono-white">
      Operational
    </Badge>
  ),
  delayed: (
    <Badge variant="outline" size="sm">
      Delayed
    </Badge>
  ),
  maintenance: (
    <Badge variant="ghost" size="sm" className="text-mono-gray-600">
      Maintenance
    </Badge>
  ),
};

const numberFormatter = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 });
const percentFormatter = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 });

const renderQueueBar = ({ id, label, value, max }: BranchQueue) => {
  const normalizedMax = Math.max(max, 1);
  const percentage = Math.min(100, Math.round((value / normalizedMax) * 100));

  return (
    <div key={id} className="space-y-1">
      <div className="flex items-center justify-between text-xs text-mono-gray-500">
        <span>{label}</span>
        <span aria-hidden="true">{value} / {normalizedMax}</span>
      </div>
      <div
        role="progressbar"
        aria-label={`${label} queue load`}
        aria-valuemin={0}
        aria-valuemax={normalizedMax}
        aria-valuenow={value}
        className="h-2 w-full rounded-full bg-mono-gray-200"
      >
        <div
          className="h-2 rounded-full bg-mono-black transition-all"
          style={{ width: `${percentage}%` }}
          aria-hidden="true"
        />
      </div>
      <span className="sr-only">{label} queue at {percentage}%</span>
    </div>
  );
};

const BranchCard: React.FC<{ branch: BranchListItem; onOpen: (id: number) => void }> = ({ branch, onOpen }) => {
  const managerName = branch.manager?.name ?? 'Manager not assigned';
  const locationLabel = branch.address ?? branch.hierarchy_path;
  const throughputLabel = `${numberFormatter.format(branch.metrics.throughput_24h)} shipments / 24h`;
  const capacityLabel = `${percentFormatter.format(branch.metrics.capacity_utilization)}% utilisation`;
  const openingLabel = branch.operating.opening_time ?? 'Schedule not set';

  return (
    <Card
      className="border border-mono-gray-200 transition-transform hover:-translate-y-1"
      header={
        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">{branch.code}</p>
            <h2 className="text-xl font-semibold text-mono-black">{branch.name}</h2>
            <p className="text-sm text-mono-gray-600">{locationLabel}</p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <div className="flex items-center gap-2 text-sm text-mono-gray-600">
              <i className="fas fa-user-tie" aria-hidden="true" />
              <span>{managerName}</span>
            </div>
            {statusBadge[branch.status_state]}
          </div>
        </div>
      }
      footer={
        <div className="flex flex-wrap items-center justify-between gap-3 text-xs uppercase tracking-[0.25em] text-mono-gray-500">
          <span>Opens at {openingLabel}</span>
          <div className="flex items-center gap-3">
            <Button variant="ghost" size="sm" className="uppercase tracking-[0.25em]">
              Incident Log
            </Button>
                      <Button variant="ghost" size="sm" className="uppercase tracking-[0.25em]" onClick={() => onOpen(branch.id)}>
                        Staffing Matrix
                      </Button>
                      <Button variant="primary" size="sm" className="uppercase tracking-[0.25em]" onClick={() => onOpen(branch.id)}>
                        Open Branch
                      </Button>
                    </div>
                  </div>
                }
    >
      <div className="grid gap-6 lg:grid-cols-[2fr,1fr]">
        <div className="space-y-4">
          <div className="flex flex-wrap items-center gap-6">
            <div className="text-sm text-mono-gray-600">
              <span className="block text-xs uppercase tracking-[0.25em] text-mono-gray-500">
                Throughput
              </span>
              <span className="text-mono-black">{throughputLabel}</span>
            </div>
            <div className="text-sm text-mono-gray-600">
              <span className="block text-xs uppercase tracking-[0.25em] text-mono-gray-500">
                Capacity
              </span>
              <span className="text-mono-black">{capacityLabel}</span>
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-3">
            {branch.queues.map((queue) => renderQueueBar(queue))}
          </div>
        </div>

        <div className="space-y-4 rounded-2xl border border-mono-gray-200 bg-mono-gray-50 p-4">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Active Workforce</p>
            <p className="text-lg font-semibold text-mono-black">
              {numberFormatter.format(branch.workforce.active)} <span className="text-sm font-normal text-mono-gray-600">/ {numberFormatter.format(branch.workforce.total)}</span>
            </p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Active Clients</p>
            <p className="text-lg font-semibold text-mono-black">{numberFormatter.format(branch.metrics.active_clients)}</p>
          </div>
        </div>
      </div>
    </Card>
  );
};

const Branches: React.FC = () => {
  const navigate = useNavigate();
  const { data, isLoading, isError, error, refetch, isFetching } = useBranchList();

  const branches = useMemo(() => data?.items ?? [], [data]);

  const summary = useMemo(() => {
    if (!branches.length) {
      return {
        throughput: 'No throughput data',
        exceptions: 'No exceptions logged',
        utilisation: '0% utilisation',
      };
    }

    const totalThroughput = branches.reduce((sum: number, branch: any) => sum + (branch.metrics.throughput_24h ?? 0), 0);
    const totalExceptions = branches.reduce((sum: number, branch: any) => {
      const exceptionQueue = branch.queues.find((queue: any) => queue.id === 'exceptions');
      return sum + (exceptionQueue?.value ?? 0);
    }, 0);
    const averageUtilisation = branches.reduce((sum: number, branch: any) => sum + (branch.metrics.capacity_utilization ?? 0), 0) / branches.length;

    return {
      throughput: `${numberFormatter.format(totalThroughput)} shipments / 24h`,
      exceptions: `${numberFormatter.format(totalExceptions)} open`,
      utilisation: `${percentFormatter.format(Math.round(averageUtilisation))}% average utilisation`,
    };
  }, [branches]);

  if (isLoading && !data) {
    return <LoadingSpinner message="Loading branch network" />;
  }

  if (isError) {
    const message = error instanceof Error ? error.message : 'Unable to load branch network';
    return (
      <div className="flex min-h-[400px] flex-col items-center justify-center">
        <Card className="max-w-md text-center">
          <div className="space-y-4">
            <div className="inline-flex h-16 w-16 items-center justify-center rounded-full bg-mono-black text-mono-white">
              <i className="fas fa-exclamation-triangle text-2xl" aria-hidden="true" />
            </div>
            <div>
              <h2 className="text-2xl font-semibold text-mono-black">Unable to reach branch services</h2>
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
              Network Command Centre
            </p>
            <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
              Branch Performance
            </h1>
            <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
              Monitor throughput, queue health, and workforce readiness across the branch network in real time.
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
              Refresh Data
            </Button>
            <Button variant="primary" size="sm" className="uppercase tracking-[0.25em]">
              <i className="fas fa-plus mr-2" aria-hidden="true" />
              Register Branch
            </Button>
          </div>
        </header>

        <div className="grid gap-6 px-8 py-8 lg:grid-cols-3">
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Network Capacity</p>
              <h2 className="text-3xl font-semibold text-mono-black">{summary.throughput}</h2>
              <p className="text-sm text-mono-gray-600">Total processed shipments across the past 24 hours</p>
            </div>
          </Card>
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Exceptions</p>
              <h2 className="text-3xl font-semibold text-mono-black">{summary.exceptions}</h2>
              <p className="text-sm text-mono-gray-600">Exceptions awaiting control centre action</p>
            </div>
          </Card>
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Utilisation</p>
              <h2 className="text-3xl font-semibold text-mono-black">{summary.utilisation}</h2>
              <p className="text-sm text-mono-gray-600">Average load across active branches</p>
            </div>
          </Card>
        </div>

        <div className="border-t border-mono-gray-200 px-8 py-8">
          {branches.length === 0 ? (
            <Card className="text-center">
              <div className="space-y-3">
                <h2 className="text-xl font-semibold text-mono-black">No branches discovered</h2>
                <p className="text-sm text-mono-gray-600">
                  Start by registering a branch or adjust the filters to view existing locations.
                </p>
                <Button variant="primary" size="sm">
                  <i className="fas fa-plus mr-2" aria-hidden="true" />
                  Add Branch
                </Button>
              </div>
            </Card>
          ) : (
            <div className="grid gap-6">
              {branches.map((branch: any) => (
                <BranchCard key={branch.id} branch={branch} onOpen={(id: any) => navigate(`/dashboard/branches/${id}`)} />
              ))}
            </div>
          )}
        </div>
      </section>
    </div>
  );
};

export default Branches;
