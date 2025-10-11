import React, { useState } from 'react';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Badge from '../../components/ui/Badge';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';

const ShipmentsByBranch: React.FC = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedBranch, setSelectedBranch] = useState('');
  const [selectedStatus, setSelectedStatus] = useState('');
  const [viewType, setViewType] = useState<'origin' | 'destination' | 'both'>('both');

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
            <Button variant="secondary" size="sm" className="uppercase tracking-[0.25em]">
              <i className="fas fa-download mr-2" />
              Export
            </Button>
            <Button variant="primary" size="sm" className="uppercase tracking-[0.25em]">
              <i className="fas fa-plus mr-2" />
              Create Shipment
            </Button>
          </div>
        </header>

        <div className="px-8 py-6 border-b border-mono-gray-200">
          <div className="space-y-4">
            <div className="flex gap-2">
              <Button
                variant={viewType === 'both' ? 'primary' : 'ghost'}
                size="sm"
                onClick={() => setViewType('both')}
              >
                All Shipments
              </Button>
              <Button
                variant={viewType === 'origin' ? 'primary' : 'ghost'}
                size="sm"
                onClick={() => setViewType('origin')}
              >
                <i className="fas fa-arrow-right mr-2" />
                Outbound
              </Button>
              <Button
                variant={viewType === 'destination' ? 'primary' : 'ghost'}
                size="sm"
                onClick={() => setViewType('destination')}
              >
                <i className="fas fa-arrow-left mr-2" />
                Inbound
              </Button>
            </div>

            <div className="grid gap-4 md:grid-cols-4">
              <div className="md:col-span-2">
                <Input
                  type="text"
                  placeholder="Search by tracking number, AWB, or client..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="w-full"
                />
              </div>
              <Select
                value={selectedBranch}
                onChange={(e) => setSelectedBranch(e.target.value)}
                className="w-full"
                options={[
                  { value: '', label: 'All Branches' },
                  { value: '1', label: 'Hub - Kampala' },
                  { value: '2', label: 'Regional - Central' },
                  { value: '3', label: 'Local - Nakawa' }
                ]}
              />
              <Select
                value={selectedStatus}
                onChange={(e) => setSelectedStatus(e.target.value)}
                className="w-full"
                options={[
                  { value: '', label: 'All Statuses' },
                  { value: 'created', label: 'Created' },
                  { value: 'in_transit', label: 'In Transit' },
                  { value: 'out_for_delivery', label: 'Out for Delivery' },
                  { value: 'delivered', label: 'Delivered' },
                  { value: 'exception', label: 'Exception' }
                ]}
              />
            </div>
          </div>
        </div>

        <div className="px-8 py-6">
          <div className="grid gap-4 md:grid-cols-4 mb-6">
            <Card className="border border-mono-gray-200 shadow-inner">
              <div className="space-y-2">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Total Shipments</p>
                <h2 className="text-3xl font-semibold text-mono-black">1,234</h2>
              </div>
            </Card>
            <Card className="border border-mono-gray-200 shadow-inner">
              <div className="space-y-2">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">In Transit</p>
                <h2 className="text-3xl font-semibold text-mono-black">456</h2>
              </div>
            </Card>
            <Card className="border border-mono-gray-200 shadow-inner">
              <div className="space-y-2">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Delivered Today</p>
                <h2 className="text-3xl font-semibold text-mono-black">89</h2>
              </div>
            </Card>
            <Card className="border border-mono-gray-200 shadow-inner">
              <div className="space-y-2">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Exceptions</p>
                <h2 className="text-3xl font-semibold text-amber-600">12</h2>
              </div>
            </Card>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="border-b border-mono-gray-200">
                <tr>
                  <th className="pb-4 text-left text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                    Tracking
                  </th>
                  <th className="pb-4 text-left text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                    Origin
                  </th>
                  <th className="pb-4 text-left text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                    Destination
                  </th>
                  <th className="pb-4 text-left text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                    Status
                  </th>
                  <th className="pb-4 text-left text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                    Worker
                  </th>
                  <th className="pb-4 text-left text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                    Date
                  </th>
                  <th className="pb-4 text-right text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-mono-gray-200">
                <tr className="hover:bg-mono-gray-50">
                  <td className="py-4">
                    <div>
                      <p className="font-mono font-semibold text-mono-black">BRK-2024-001234</p>
                      <p className="text-sm text-mono-gray-600">AWB: 1234567890</p>
                    </div>
                  </td>
                  <td className="py-4">
                    <Badge variant="outline" size="sm">Hub - Kampala</Badge>
                  </td>
                  <td className="py-4">
                    <Badge variant="outline" size="sm">Local - Nakawa</Badge>
                  </td>
                  <td className="py-4">
                    <Badge variant="solid" size="sm" className="bg-blue-600 text-white">
                      In Transit
                    </Badge>
                  </td>
                  <td className="py-4">
                    <p className="text-sm text-mono-black">John Worker</p>
                  </td>
                  <td className="py-4">
                    <div className="text-sm">
                      <p className="text-mono-black">Oct 10, 2024</p>
                      <p className="text-mono-gray-600">14:30</p>
                    </div>
                  </td>
                  <td className="py-4 text-right">
                    <Button variant="ghost" size="sm">
                      <i className="fas fa-eye mr-2" />
                      Track
                    </Button>
                  </td>
                </tr>
                {/* More rows would be generated dynamically */}
              </tbody>
            </table>
          </div>

          <div className="mt-6 flex items-center justify-between">
            <p className="text-sm text-mono-gray-600">
              Showing <span className="font-semibold">1</span> to <span className="font-semibold">20</span> of{' '}
              <span className="font-semibold">1,234</span> shipments
            </p>
            <div className="flex items-center gap-2">
              <Button variant="ghost" size="sm" disabled>
                <i className="fas fa-chevron-left" />
              </Button>
              <Button variant="primary" size="sm">1</Button>
              <Button variant="ghost" size="sm">2</Button>
              <Button variant="ghost" size="sm">3</Button>
              <Button variant="ghost" size="sm">
                <i className="fas fa-chevron-right" />
              </Button>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};

export default ShipmentsByBranch;
