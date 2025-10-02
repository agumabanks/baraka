import React from 'react';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Badge from '../components/ui/Badge';

type BranchStatus = 'Operational' | 'Delayed' | 'Maintenance';

interface BranchRecord {
  id: string;
  name: string;
  location: string;
  manager: string;
  status: BranchStatus;
  throughput: string;
  capacity: string;
  openingTime: string;
  queues: Array<{ label: string; value: number; max: number }>;
}

const branches: BranchRecord[] = [
  {
    id: 'BR-001',
    name: 'Kampala Central Branch',
    location: 'Industrial Area • Kampala',
    manager: 'Brian Akena',
    status: 'Operational',
    throughput: '4,820 parcels / day',
    capacity: '82% utilisation',
    openingTime: '05:30',
    queues: [
      { label: 'Inbound', value: 42, max: 120 },
      { label: 'Outbound', value: 58, max: 150 },
      { label: 'Exceptions', value: 6, max: 30 },
    ],
  },
  {
    id: 'BR-007',
    name: 'Entebbe Airside Branch',
    location: 'Airport Cargo Village • Entebbe',
    manager: 'Grace Wamalwa',
    status: 'Delayed',
    throughput: '2,140 parcels / day',
    capacity: '67% utilisation',
    openingTime: '04:10',
    queues: [
      { label: 'Inbound', value: 65, max: 100 },
      { label: 'Outbound', value: 74, max: 120 },
      { label: 'Exceptions', value: 14, max: 25 },
    ],
  },
  {
    id: 'BR-014',
    name: 'Gulu Regional Branch',
    location: 'Ring Road • Gulu',
    manager: 'Dinah Komakech',
    status: 'Operational',
    throughput: '1,280 parcels / day',
    capacity: '58% utilisation',
    openingTime: '06:15',
    queues: [
      { label: 'Inbound', value: 24, max: 80 },
      { label: 'Outbound', value: 37, max: 90 },
      { label: 'Exceptions', value: 3, max: 20 },
    ],
  },
];

const statusBadge: Record<BranchStatus, React.ReactNode> = {
  Operational: (
    <Badge variant="solid" size="sm" className="bg-mono-black text-mono-white">
      Operational
    </Badge>
  ),
  Delayed: (
    <Badge variant="outline" size="sm">
      Delayed
    </Badge>
  ),
  Maintenance: (
    <Badge variant="ghost" size="sm" className="text-mono-gray-600">
      Maintenance
    </Badge>
  ),
};

const renderQueueBar = (value: number, max: number) => {
  const percentage = Math.min(100, Math.round((value / max) * 100));
  return (
    <div className="h-2 w-full rounded-full bg-mono-gray-200" aria-hidden="true">
      <div className="h-2 rounded-full bg-mono-black transition-all" style={{ width: `${percentage}%` }} />
    </div>
  );
};

const Branches: React.FC = () => {
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
              Track throughput, queue health, and incident flags across regional branches. Act before site resilience dips.
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <Button variant="secondary" size="sm" className="uppercase tracking-[0.25em]">
              <i className="fas fa-broadcast-tower mr-2" aria-hidden="true" />
              Dispatch Alert
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
              <h2 className="text-3xl font-semibold text-mono-black">8,240 Parcels / hr</h2>
              <p className="text-sm text-mono-gray-600">Across all active branches in the current duty window</p>
            </div>
          </Card>
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Exceptions</p>
              <h2 className="text-3xl font-semibold text-mono-black">23 Outstanding</h2>
              <p className="text-sm text-mono-gray-600">Escalated to control centre within 45 minutes</p>
            </div>
          </Card>
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">SLA Integrity</p>
              <h2 className="text-3xl font-semibold text-mono-black">94% Protected</h2>
              <p className="text-sm text-mono-gray-600">On-time handoffs across first and last mile</p>
            </div>
          </Card>
        </div>

        <div className="border-t border-mono-gray-200 px-8 py-8">
          <div className="grid gap-6">
            {branches.map((branch) => (
              <Card
                key={branch.id}
                className="border border-mono-gray-200 transition-transform hover:-translate-y-1"
                header={
                  <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">{branch.id}</p>
                      <h2 className="text-xl font-semibold text-mono-black">{branch.name}</h2>
                      <p className="text-sm text-mono-gray-600">{branch.location}</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                      <div className="flex items-center gap-2 text-sm text-mono-gray-600">
                        <i className="fas fa-user-tie" aria-hidden="true" />
                        {branch.manager}
                      </div>
                      {statusBadge[branch.status]}
                    </div>
                  </div>
                }
                footer={
                  <div className="flex flex-wrap items-center justify-between gap-3 text-xs uppercase tracking-[0.25em] text-mono-gray-500">
                    <span>Opened at {branch.openingTime}</span>
                    <div className="flex items-center gap-3">
                      <Button variant="ghost" size="sm" className="uppercase tracking-[0.25em]">
                        Incident Log
                      </Button>
                      <Button variant="ghost" size="sm" className="uppercase tracking-[0.25em]">
                        Staffing Matrix
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
                        <span className="text-mono-black">{branch.throughput}</span>
                      </div>
                      <div className="text-sm text-mono-gray-600">
                        <span className="block text-xs uppercase tracking-[0.25em] text-mono-gray-500">
                          Capacity
                        </span>
                        <span className="text-mono-black">{branch.capacity}</span>
                      </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                      {branch.queues.map((queue) => (
                        <div key={queue.label} className="space-y-2">
                          <div className="flex items-center justify-between text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                            <span>{queue.label}</span>
                            <span>{queue.value}</span>
                          </div>
                          {renderQueueBar(queue.value, queue.max)}
                          <p className="text-xs text-mono-gray-500">Max {queue.max}</p>
                        </div>
                      ))}
                    </div>
                  </div>

                  <div className="space-y-4 rounded-2xl border border-dashed border-mono-gray-300 bg-mono-gray-50 p-4">
                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                      Focus Actions
                    </p>
                    <ul className="space-y-3 text-sm text-mono-gray-600">
                      <li className="flex items-start gap-2">
                        <span className="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-mono-black" aria-hidden="true" />
                        Synchronise linehaul manifest for {branch.name.split(' ')[0]} corridor.
                      </li>
                      <li className="flex items-start gap-2">
                        <span className="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-mono-black" aria-hidden="true" />
                        Audit exception cage and reconcile with operations control.
                      </li>
                      <li className="flex items-start gap-2">
                        <span className="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-mono-black" aria-hidden="true" />
                        Confirm staffing roster for twilight sort window.
                      </li>
                    </ul>
                  </div>
                </div>
              </Card>
            ))}
          </div>
        </div>
      </section>
    </div>
  );
};

export default Branches;
