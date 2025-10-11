import React, { useState } from 'react';
import Button from '../../components/ui/Button';
import Badge from '../../components/ui/Badge';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';

const LocalClients: React.FC = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedBranch, setSelectedBranch] = useState('');
  const [selectedStatus, setSelectedStatus] = useState('');

  return (
    <div className="space-y-6">
      <section className="rounded-3xl border border-mono-gray-200 bg-mono-white shadow-xl">
        <header className="flex flex-col gap-6 border-b border-mono-gray-200 px-8 py-10 xl:flex-row xl:items-center xl:justify-between">
          <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Client Management
            </p>
            <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
              Local Clients
            </h1>
            <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
              Manage and view clients assigned to specific branches across your network.
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <Button variant="secondary" size="sm" className="uppercase tracking-[0.25em]">
              <i className="fas fa-download mr-2" />
              Export
            </Button>
            <Button variant="primary" size="sm" className="uppercase tracking-[0.25em]">
              <i className="fas fa-plus mr-2" />
              Add Client
            </Button>
          </div>
        </header>

        <div className="px-8 py-6 border-b border-mono-gray-200">
          <div className="grid gap-4 md:grid-cols-4">
            <div className="md:col-span-2">
              <Input
                type="text"
                placeholder="Search by name, company, email, or phone..."
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
                { value: 'active', label: 'Active' },
                { value: 'inactive', label: 'Inactive' },
                { value: 'suspended', label: 'Suspended' }
              ]}
            />
          </div>
        </div>

        <div className="px-8 py-8">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="border-b border-mono-gray-200">
                <tr>
                  <th className="pb-4 text-left text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                    Client
                  </th>
                  <th className="pb-4 text-left text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                    Branch
                  </th>
                  <th className="pb-4 text-left text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                    Contact
                  </th>
                  <th className="pb-4 text-left text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                    Status
                  </th>
                  <th className="pb-4 text-left text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                    Shipments
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
                      <p className="font-semibold text-mono-black">ABC Company Ltd</p>
                      <p className="text-sm text-mono-gray-600">Client #12345</p>
                    </div>
                  </td>
                  <td className="py-4">
                    <Badge variant="outline" size="sm">Hub - Kampala</Badge>
                  </td>
                  <td className="py-4">
                    <div className="text-sm">
                      <p className="text-mono-black">John Doe</p>
                      <p className="text-mono-gray-600">john@abc.com</p>
                      <p className="text-mono-gray-600">+256 123 456 789</p>
                    </div>
                  </td>
                  <td className="py-4">
                    <Badge variant="solid" size="sm" className="bg-mono-black text-mono-white">
                      Active
                    </Badge>
                  </td>
                  <td className="py-4">
                    <p className="font-semibold text-mono-black">142</p>
                    <p className="text-xs text-mono-gray-600">Total shipments</p>
                  </td>
                  <td className="py-4 text-right">
                    <Button variant="ghost" size="sm">
                      <i className="fas fa-eye mr-2" />
                      View
                    </Button>
                  </td>
                </tr>
                {/* More rows would be generated dynamically */}
              </tbody>
            </table>
          </div>

          <div className="mt-6 flex items-center justify-between">
            <p className="text-sm text-mono-gray-600">
              Showing <span className="font-semibold">1</span> to <span className="font-semibold">10</span> of{' '}
              <span className="font-semibold">45</span> clients
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

export default LocalClients;
