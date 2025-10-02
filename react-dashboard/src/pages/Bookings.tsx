import React, { useMemo, useState } from 'react';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Badge from '../components/ui/Badge';

interface BookingRecord {
  id: string;
  customer: string;
  channel: 'Portal' | 'API' | 'Sales Desk';
  createdAt: string;
  promisedDate: string;
  status: 'Draft' | 'Pending Dispatch' | 'In Transit' | 'Delivered' | 'Exception';
  slaBreached: boolean;
  parcels: number;
}

const bookingPipeline: Array<{ label: string; total: number; icon: string }> = [
  { label: 'New Requests', total: 38, icon: 'fas fa-inbox' },
  { label: 'Route Planning', total: 24, icon: 'fas fa-route' },
  { label: 'Loading Bays', total: 18, icon: 'fas fa-dolly' },
  { label: 'Linehaul', total: 12, icon: 'fas fa-truck' },
  { label: 'Out for Delivery', total: 26, icon: 'fas fa-map-marked-alt' }
];

const bookings: BookingRecord[] = [
  {
    id: 'BKG-20931',
    customer: 'Acme Retail Consortium',
    channel: 'Portal',
    createdAt: '2025-02-17 09:32',
    promisedDate: '2025-02-18 18:00',
    status: 'Pending Dispatch',
    slaBreached: false,
    parcels: 42
  },
  {
    id: 'BKG-20926',
    customer: 'Nimbus Health Labs',
    channel: 'API',
    createdAt: '2025-02-17 07:18',
    promisedDate: '2025-02-17 23:00',
    status: 'In Transit',
    slaBreached: false,
    parcels: 18
  },
  {
    id: 'BKG-20910',
    customer: 'Savanna Fresh Collective',
    channel: 'Sales Desk',
    createdAt: '2025-02-16 20:05',
    promisedDate: '2025-02-18 09:00',
    status: 'Exception',
    slaBreached: true,
    parcels: 6
  },
  {
    id: 'BKG-20894',
    customer: 'Lumen Logistics Partners',
    channel: 'Portal',
    createdAt: '2025-02-16 18:42',
    promisedDate: '2025-02-17 14:00',
    status: 'Delivered',
    slaBreached: false,
    parcels: 28
  },
  {
    id: 'BKG-20877',
    customer: 'Orbit Media Labs',
    channel: 'API',
    createdAt: '2025-02-16 10:12',
    promisedDate: '2025-02-17 10:00',
    status: 'Pending Dispatch',
    slaBreached: false,
    parcels: 12
  }
];

const statusVariantMap: Record<BookingRecord['status'], { badge: React.ReactNode; tone: string }> = {
  Draft: {
    badge: <Badge variant="outline" size="sm">Draft</Badge>,
    tone: 'text-mono-gray-500'
  },
  'Pending Dispatch': {
    badge: <Badge variant="solid" size="sm">Pending Dispatch</Badge>,
    tone: 'text-mono-black'
  },
  'In Transit': {
    badge: <Badge variant="outline" size="sm">In Transit</Badge>,
    tone: 'text-mono-black'
  },
  Delivered: {
    badge: <Badge variant="ghost" size="sm" className="text-mono-black">Delivered</Badge>,
    tone: 'text-mono-gray-600'
  },
  Exception: {
    badge: (
      <Badge variant="solid" size="sm" className="bg-mono-black text-mono-white">
        Exception
      </Badge>
    ),
    tone: 'text-mono-black'
  }
};

const filterOptions: BookingRecord['status'][] = [
  'Pending Dispatch',
  'In Transit',
  'Delivered',
  'Exception'
];

const Bookings: React.FC = () => {
  const [statusFilter, setStatusFilter] = useState<BookingRecord['status'][]>(['Pending Dispatch', 'In Transit']);
  const [query, setQuery] = useState('');

  const filteredBookings = useMemo(() => {
    return bookings.filter((booking) => {
      const matchesStatus = statusFilter.includes(booking.status);
      const search = query.trim().toLowerCase();
      const matchesQuery =
        !search ||
        booking.id.toLowerCase().includes(search) ||
        booking.customer.toLowerCase().includes(search) ||
        booking.channel.toLowerCase().includes(search);

      return matchesStatus && matchesQuery;
    });
  }, [statusFilter, query]);

  const toggleFilter = (status: BookingRecord['status']) => {
    setStatusFilter((previous) =>
      previous.includes(status)
        ? previous.filter((value) => value !== status)
        : [...previous, status]
    );
  };

  const totalExceptions = bookings.filter((booking) => booking.status === 'Exception').length;
  const onTimeRate = Math.round(
    (bookings.filter((booking) => !booking.slaBreached).length / bookings.length) * 100
  );

  return (
    <div className="space-y-10">
      <section className="rounded-3xl border border-mono-gray-200 bg-mono-white shadow-xl">
        <header className="flex flex-col gap-6 border-b border-mono-gray-200 px-8 py-10 lg:flex-row lg:items-center lg:justify-between">
          <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Booking Control Room
            </p>
            <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
              Movement Pipeline
            </h1>
            <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
              Streamline request triage, SLA adherence, and dispatch readiness across every booking channel.
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <Button variant="secondary" size="sm" className="uppercase tracking-[0.25em]">
              <i className="fas fa-clipboard-list mr-2" aria-hidden="true" />
              Bulk Actions
            </Button>
            <Button variant="primary" size="sm" className="uppercase tracking-[0.25em]">
              <i className="fas fa-plus mr-2" aria-hidden="true" />
              Create Booking
            </Button>
          </div>
        </header>

        <div className="grid gap-6 px-8 py-8 lg:grid-cols-4">
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Queue</p>
              <h2 className="text-3xl font-semibold text-mono-black">{bookings.length} Active</h2>
              <p className="text-sm text-mono-gray-600">Rolling 24-hour intake across all channels</p>
            </div>
          </Card>
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">On-Time</p>
              <h2 className="text-3xl font-semibold text-mono-black">{onTimeRate}%</h2>
              <p className="text-sm text-mono-gray-600">Promised handoffs meeting SLA commitments</p>
            </div>
          </Card>
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Exceptions</p>
              <h2 className="text-3xl font-semibold text-mono-black">{totalExceptions}</h2>
              <p className="text-sm text-mono-gray-600">Action immediately to protect customer NPS</p>
            </div>
          </Card>
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Linehaul Ready</p>
              <h2 className="text-3xl font-semibold text-mono-black">24 Manifests</h2>
              <p className="text-sm text-mono-gray-600">Prepared for late-night linehaul departures</p>
            </div>
          </Card>
        </div>

        <div className="border-t border-mono-gray-200 px-8 py-8">
          <div className="grid gap-6 lg:grid-cols-[2fr,3fr]">
            <Card className="border border-mono-gray-200">
              <div className="space-y-5">
                <header className="flex items-center justify-between">
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Execution Stages</p>
                    <h2 className="text-lg font-semibold text-mono-black">Same-Day Fulfilment</h2>
                  </div>
                  <Button variant="ghost" size="sm" className="uppercase tracking-[0.2em]">
                    View Playbook
                  </Button>
                </header>

                <ol className="space-y-4">
                  {bookingPipeline.map((stage) => (
                    <li key={stage.label} className="flex items-center justify-between rounded-2xl border border-mono-gray-200 bg-mono-gray-50 px-4 py-3">
                      <div className="flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-full bg-mono-white text-mono-black shadow-inner">
                          <i className={`${stage.icon} text-sm`} aria-hidden="true" />
                        </span>
                        <div>
                          <p className="text-sm font-semibold text-mono-black">{stage.label}</p>
                          <p className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">Capacity aligned</p>
                        </div>
                      </div>
                      <span className="text-sm font-semibold text-mono-black">{stage.total}</span>
                    </li>
                  ))}
                </ol>
              </div>
            </Card>

            <Card className="border border-mono-gray-200">
              <div className="space-y-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                  <div className="flex flex-1 items-center gap-3">
                    <label htmlFor="booking-search" className="sr-only">
                      Search bookings
                    </label>
                    <div className="relative w-full md:w-72">
                      <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-mono-gray-500">
                        <i className="fas fa-search" aria-hidden="true" />
                      </span>
                      <input
                        id="booking-search"
                        type="search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Search by ID, customer, or channel"
                        className="w-full rounded-xl border border-mono-gray-300 bg-mono-gray-25 py-2 pl-10 pr-3 text-sm text-mono-gray-800 focus:border-mono-black focus:outline-none focus:ring-2 focus:ring-mono-black/10"
                        aria-label="Search bookings"
                      />
                    </div>
                  </div>

                  <div className="flex flex-wrap gap-2">
                    {filterOptions.map((option) => (
                      <button
                        key={option}
                        type="button"
                        onClick={() => toggleFilter(option)}
                        className={`rounded-full border px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.25em] transition-colors ${
                          statusFilter.includes(option)
                            ? 'border-mono-black bg-mono-black text-mono-white'
                            : 'border-mono-gray-300 bg-mono-white text-mono-gray-700 hover:border-mono-black hover:text-mono-black'
                        }`}
                        aria-pressed={statusFilter.includes(option)}
                      >
                        {option}
                      </button>
                    ))}
                  </div>
                </div>

                <div className="overflow-x-auto rounded-2xl border border-mono-gray-200">
                  <table className="min-w-full divide-y divide-mono-gray-200">
                    <thead className="bg-mono-gray-50">
                      <tr>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                          Booking ID
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                          Customer
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                          Channel
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                          Created
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                          Promise
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                          Status
                        </th>
                        <th scope="col" className="px-6 py-3 text-right text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                          Parcels
                        </th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-mono-gray-200 bg-mono-white">
                      {filteredBookings.length === 0 && (
                        <tr>
                          <td colSpan={7} className="px-6 py-10 text-center text-sm text-mono-gray-600">
                            No bookings match the selected filters.
                          </td>
                        </tr>
                      )}
                      {filteredBookings.map((booking) => {
                        const { badge, tone } = statusVariantMap[booking.status];
                        return (
                          <tr key={booking.id} className="transition-colors hover:bg-mono-gray-50">
                            <td className="whitespace-nowrap px-6 py-4 text-sm font-semibold text-mono-black">
                              {booking.id}
                            </td>
                            <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                              {booking.customer}
                            </td>
                            <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                              {booking.channel}
                            </td>
                            <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                              {booking.createdAt}
                            </td>
                            <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                              {booking.promisedDate}
                            </td>
                            <td className={`whitespace-nowrap px-6 py-4 text-sm ${tone}`}>
                              {badge}
                            </td>
                            <td className="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-mono-black">
                              {booking.parcels}
                              {booking.slaBreached && (
                                <span className="ml-2 text-xs uppercase tracking-[0.25em] text-mono-gray-500">
                                  SLA BREACH
                                </span>
                              )}
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </section>
    </div>
  );
};

export default Bookings;
