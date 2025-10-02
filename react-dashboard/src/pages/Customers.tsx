import React, { useMemo, useState } from 'react';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';

interface CustomerRecord {
  id: string;
  name: string;
  contact: string;
  industry: string;
  shipmentsThisMonth: number;
  lifetimeShipments: number;
  lastActivity: string;
  status: 'Active' | 'At Risk' | 'Dormant';
  avgSla: string;
}

const customers: CustomerRecord[] = [
  {
    id: 'CUS-001',
    name: 'Acme Retail Consortium',
    contact: 'Sarah Connor',
    industry: 'Retail & eCommerce',
    shipmentsThisMonth: 128,
    lifetimeShipments: 1842,
    lastActivity: '2 hours ago',
    status: 'Active',
    avgSla: '96%'
  },
  {
    id: 'CUS-019',
    name: 'Nimbus Health Labs',
    contact: 'Dr. Felix Mutesa',
    industry: 'Healthcare',
    shipmentsThisMonth: 64,
    lifetimeShipments: 921,
    lastActivity: 'Yesterday 17:40',
    status: 'At Risk',
    avgSla: '88%'
  },
  {
    id: 'CUS-084',
    name: 'Lumen Logistics Partners',
    contact: 'Gabriella Choi',
    industry: '3PL',
    shipmentsThisMonth: 212,
    lifetimeShipments: 5210,
    lastActivity: '21 minutes ago',
    status: 'Active',
    avgSla: '98%'
  },
  {
    id: 'CUS-204',
    name: 'Savanna Fresh Collective',
    contact: 'Michael Odhiambo',
    industry: 'Agriculture',
    shipmentsThisMonth: 39,
    lifetimeShipments: 418,
    lastActivity: '4 days ago',
    status: 'Dormant',
    avgSla: '81%'
  },
  {
    id: 'CUS-377',
    name: 'Orbit Media Labs',
    contact: 'Fatima Hussein',
    industry: 'Media & Entertainment',
    shipmentsThisMonth: 78,
    lifetimeShipments: 1306,
    lastActivity: '6 hours ago',
    status: 'At Risk',
    avgSla: '89%'
  }
];

const statusBadgeMap: Record<CustomerRecord['status'], { label: string; className: string }> = {
  Active: {
    label: 'Active',
    className: 'bg-mono-black text-mono-white'
  },
  'At Risk': {
    label: 'At Risk',
    className: 'border border-mono-gray-400 text-mono-gray-900 bg-mono-white'
  },
  Dormant: {
    label: 'Dormant',
    className: 'bg-mono-gray-200 text-mono-gray-700'
  }
};

const Customers: React.FC = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [showOnlyAtRisk, setShowOnlyAtRisk] = useState(false);

  const filteredCustomers = useMemo(() => {
    return customers.filter((customer) => {
      if (showOnlyAtRisk && customer.status !== 'At Risk') {
        return false;
      }

      const term = searchTerm.trim().toLowerCase();
      if (!term) {
        return true;
      }

      return [
        customer.name,
        customer.contact,
        customer.industry,
        customer.id
      ].some((value) => value.toLowerCase().includes(term));
    });
  }, [searchTerm, showOnlyAtRisk]);

  const activeCustomers = customers.filter((customer) => customer.status === 'Active').length;
  const averageSla = Math.round(
    customers.reduce((total, customer) => total + parseInt(customer.avgSla.replace('%', ''), 10), 0) /
      customers.length
  );
  const totalShipments = customers.reduce((total, customer) => total + customer.shipmentsThisMonth, 0);

  return (
    <div className="space-y-10">
      <section className="rounded-3xl border border-mono-gray-200 bg-mono-white shadow-xl">
        <header className="flex flex-col gap-6 border-b border-mono-gray-200 px-8 py-10 sm:flex-row sm:items-center sm:justify-between">
          <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Customer Intelligence
            </p>
            <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
              Strategic Accounts
            </h1>
            <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
              Monitor customer health, operational cadence, and retention signals. Prioritise at-risk accounts before SLAs slip.
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <Button variant="secondary" size="sm" className="uppercase tracking-[0.25em]">
              <i className="fas fa-upload mr-2" aria-hidden="true" />
              Import CSV
            </Button>
            <Button variant="primary" size="sm" className="uppercase tracking-[0.25em]">
              <i className="fas fa-plus mr-2" aria-hidden="true" />
              New Customer
            </Button>
          </div>
        </header>

        <div className="grid gap-6 px-8 py-8 md:grid-cols-3">
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Portfolio Size</p>
              <h2 className="text-3xl font-semibold text-mono-black">{customers.length} Key Accounts</h2>
              <p className="text-sm text-mono-gray-600">{activeCustomers} actively shipping in the last 7 days</p>
            </div>
          </Card>
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Reliability</p>
              <h2 className="text-3xl font-semibold text-mono-black">{averageSla}% SLA</h2>
              <p className="text-sm text-mono-gray-600">Weighted average fulfilment across premium accounts</p>
            </div>
          </Card>
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Momentum</p>
              <h2 className="text-3xl font-semibold text-mono-black">{totalShipments.toLocaleString()} Shipments</h2>
              <p className="text-sm text-mono-gray-600">Dispatched in the current billing period</p>
            </div>
          </Card>
        </div>

        <div className="border-t border-mono-gray-200 px-8 py-8">
          <Card className="border border-mono-gray-200">
            <div className="space-y-6">
              <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div className="flex flex-1 items-center gap-3">
                  <label htmlFor="customer-search" className="sr-only">
                    Search customers
                  </label>
                  <div className="relative w-full md:w-80">
                    <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-mono-gray-500">
                      <i className="fas fa-search" aria-hidden="true" />
                    </span>
                    <input
                      id="customer-search"
                      type="search"
                      value={searchTerm}
                      onChange={(event) => setSearchTerm(event.target.value)}
                      placeholder="Search by name, industry, or ID"
                      className="w-full rounded-xl border border-mono-gray-300 bg-mono-gray-25 py-2 pl-10 pr-3 text-sm text-mono-gray-800 focus:border-mono-black focus:outline-none focus:ring-2 focus:ring-mono-black/10"
                      aria-label="Search customers"
                    />
                  </div>
                  <button
                    type="button"
                    onClick={() => setShowOnlyAtRisk((previous) => !previous)}
                    className={`flex items-center gap-2 rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] transition-colors ${
                      showOnlyAtRisk
                        ? 'border-mono-black bg-mono-black text-mono-white'
                        : 'border-mono-gray-300 bg-mono-white text-mono-gray-700 hover:border-mono-black hover:text-mono-black'
                    }`}
                    aria-pressed={showOnlyAtRisk}
                  >
                    <i className="fas fa-exclamation-triangle" aria-hidden="true" />
                    At-Risk Only
                  </button>
                </div>

                <div className="flex items-center gap-3">
                  <Button variant="ghost" size="sm" className="uppercase tracking-[0.25em]">
                    <i className="fas fa-sliders-h mr-2" aria-hidden="true" />
                    Advanced Filters
                  </Button>
                  <Button variant="secondary" size="sm" className="uppercase tracking-[0.25em]">
                    <i className="fas fa-history mr-2" aria-hidden="true" />
                    Export Audit
                  </Button>
                </div>
              </div>

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
                        SLA Performance
                      </th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                        Status
                      </th>
                      <th scope="col" className="px-6 py-3 text-right text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-mono-gray-200 bg-mono-white">
                    {filteredCustomers.length === 0 && (
                      <tr>
                        <td colSpan={7} className="px-6 py-10 text-center text-sm text-mono-gray-600">
                          No customers match the current filters.
                        </td>
                      </tr>
                    )}
                    {filteredCustomers.map((customer) => {
                      const badge = statusBadgeMap[customer.status];
                      return (
                        <tr key={customer.id} className="transition-colors hover:bg-mono-gray-50">
                          <td className="whitespace-nowrap px-6 py-4">
                            <div className="flex flex-col">
                              <span className="text-sm font-semibold text-mono-black">{customer.name}</span>
                              <span className="text-xs uppercase tracking-[0.25em] text-mono-gray-500">{customer.industry}</span>
                            </div>
                          </td>
                          <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                            {customer.contact}
                          </td>
                          <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                            {customer.shipmentsThisMonth.toLocaleString()} shipments
                          </td>
                          <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                            {customer.lifetimeShipments.toLocaleString()} shipments
                          </td>
                          <td className="whitespace-nowrap px-6 py-4 text-sm text-mono-gray-700">
                            {customer.avgSla}
                          </td>
                          <td className="whitespace-nowrap px-6 py-4 text-sm">
                            <span className={`rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] ${badge.className}`}>
                              {badge.label}
                            </span>
                          </td>
                          <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                            <div className="flex justify-end gap-2">
                              <Button variant="ghost" size="sm" className="uppercase tracking-[0.3em]">
                                View
                              </Button>
                              <Button variant="secondary" size="sm" className="uppercase tracking-[0.3em]">
                                Manage
                              </Button>
                            </div>
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
      </section>
    </div>
  );
};

export default Customers;
