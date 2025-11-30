import React, { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import Button from '../../components/ui/Button';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { branchWorkersApi } from '../../services/api';
import type { BranchWorkerFormData, BranchOption, UserOption } from '../../types/branchWorkers';

type BranchWorkerCreateFormState = BranchWorkerFormData & {
  branch_id: string;
  user_id: string;
  name: string;
  email: string;
  phone: string;
  password: string;
  address: string;
  preferred_language: 'en' | 'fr' | 'sw';
};

const BranchWorkerCreate: React.FC = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState<BranchWorkerCreateFormState>({
    branch_id: '',
    user_id: '',
    role: '',
    status: 'active',
    name: '',
    email: '',
    phone: '',
    password: '',
    address: '',
    preferred_language: 'en',
  });
  const [useExistingUser, setUseExistingUser] = useState(true);

  const { data: resourcesData, isLoading } = useQuery({
    queryKey: ['available-worker-resources'],
    queryFn: () => branchWorkersApi.getAvailableResources(),
  });

  const createMutation = useMutation({
    mutationFn: (data: BranchWorkerFormData) => branchWorkersApi.createWorker(data),
    onSuccess: () => navigate('/admin/dashboard/branch-workers'),
    onError: (error: any) => alert(error?.response?.data?.message || 'Failed to create worker'),
  });

  const handleChange = (
    event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>
  ) => {
    const { name, value } = event.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleAccountModeChange = (mode: 'existing' | 'new') => {
    setUseExistingUser(mode === 'existing');
    setFormData((prev) => ({
      ...prev,
      user_id: mode === 'existing' ? prev.user_id : '',
      name: mode === 'existing' ? '' : prev.name,
      email: mode === 'existing' ? '' : prev.email,
      phone: mode === 'existing' ? '' : prev.phone,
      address: mode === 'existing' ? '' : prev.address,
      password: '',
    }));
  };

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault();

    if (!formData.branch_id || !formData.role) {
      alert('Please choose a branch and role for this worker.');
      return;
    }

    if (useExistingUser) {
      if (!formData.user_id) {
        alert('Please select an existing user to assign as branch worker.');
        return;
      }
    } else {
      if (!formData.name.trim() || !formData.email.trim() || !formData.phone.trim() || !formData.password) {
        alert('Please provide name, email, phone, and password for the new worker account.');
        return;
      }
    }

    const payload: BranchWorkerFormData = {
      branch_id: Number(formData.branch_id),
      role: formData.role,
      status: formData.status,
    };

    if (useExistingUser) {
      payload.user_id = Number(formData.user_id);
    } else {
      payload.name = formData.name.trim();
      payload.email = formData.email.trim();
      payload.phone = formData.phone.trim();
      payload.password = formData.password;
      if (formData.address.trim()) {
        payload.address = formData.address.trim();
      }
      payload.preferred_language = formData.preferred_language;
      payload.contact_phone = formData.phone.trim();
    }

    createMutation.mutate(payload);
  };

  if (isLoading) return <LoadingSpinner message="Loading form data" />;

  const branches: BranchOption[] = resourcesData?.data?.branches || [];
  const users: UserOption[] = resourcesData?.data?.users || [];
  const roles: Array<{ value: string; label: string }> = resourcesData?.data?.roles || [];
  const languageOptions = useMemo(() => ([
    { value: 'en', label: 'English' },
    { value: 'fr', label: 'Français' },
    { value: 'sw', label: 'Kiswahili' },
  ]), []);

  return (
    <div className="space-y-6">
      <section className="rounded-3xl border border-mono-gray-200 bg-mono-white shadow-xl">
        <header className="border-b border-mono-gray-200 px-8 py-10">
          <h1 className="text-3xl font-semibold text-mono-black">Create Branch Worker</h1>
        </header>
        <form onSubmit={handleSubmit} className="px-8 py-8">
          <div className="max-w-2xl space-y-6">
            <div>
              <label className="block text-sm font-medium mb-2">Branch <span className="text-red-600">*</span></label>
              <select
                name="branch_id"
                value={formData.branch_id}
                onChange={handleChange}
                required
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2"
              >
                <option value="">Select a branch</option>
                {branches.map((branch: BranchOption) => (
                  <option key={branch.value} value={branch.value}>
                    {branch.label} ({branch.code})
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium mb-2">Worker Account</label>
              <div className="flex flex-wrap gap-4 text-sm text-mono-gray-700">
                <label className="inline-flex items-center gap-2">
                  <input
                    type="radio"
                    name="account_mode"
                    value="existing"
                    checked={useExistingUser}
                    onChange={() => handleAccountModeChange('existing')}
                  />
                  Link existing user
                </label>
                <label className="inline-flex items-center gap-2">
                  <input
                    type="radio"
                    name="account_mode"
                    value="new"
                    checked={!useExistingUser}
                    onChange={() => handleAccountModeChange('new')}
                  />
                  Create new user account
                </label>
              </div>
            </div>

            {useExistingUser ? (
              <div>
                <label className="block text-sm font-medium mb-2">Select User <span className="text-red-600">*</span></label>
                <select
                  name="user_id"
                  value={formData.user_id}
                  onChange={handleChange}
                  required={useExistingUser}
                  className="w-full rounded-lg border border-mono-gray-300 px-4 py-2"
                >
                  <option value="">Select a user</option>
                  {users.map((user: UserOption) => (
                    <option key={user.value} value={user.value}>
                      {user.label} ({user.email})
                      {user.preferred_language ? ` · ${user.preferred_language.toUpperCase()}` : ''}
                    </option>
                  ))}
                </select>
                {users.length === 0 && (
                  <p className="mt-2 text-xs text-mono-red-600">
                    No available workers to link. Switch to "Create new user account" to provision a worker.
                  </p>
                )}
              </div>
            ) : (
              <div className="grid gap-4 md:grid-cols-2">
                <div>
                  <label className="block text-sm font-medium mb-2">Full Name <span className="text-red-600">*</span></label>
                  <input
                    type="text"
                    name="name"
                    value={formData.name}
                    onChange={handleChange}
                    className="w-full rounded-lg border border-mono-gray-300 px-4 py-2"
                    placeholder="Enter full name"
                    required={!useExistingUser}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-2">Email <span className="text-red-600">*</span></label>
                  <input
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    className="w-full rounded-lg border border-mono-gray-300 px-4 py-2"
                    placeholder="agent@example.com"
                    required={!useExistingUser}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-2">Phone <span className="text-red-600">*</span></label>
                  <input
                    type="tel"
                    name="phone"
                    value={formData.phone}
                    onChange={handleChange}
                    className="w-full rounded-lg border border-mono-gray-300 px-4 py-2"
                    placeholder="e.g. +2557..."
                    required={!useExistingUser}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-2">Password <span className="text-red-600">*</span></label>
                  <input
                    type="password"
                    name="password"
                    value={formData.password}
                    onChange={handleChange}
                    className="w-full rounded-lg border border-mono-gray-300 px-4 py-2"
                    placeholder="Set initial password"
                    required={!useExistingUser}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-2">Preferred Language</label>
                  <select
                    name="preferred_language"
                    value={formData.preferred_language}
                    onChange={handleChange}
                    className="w-full rounded-lg border border-mono-gray-300 px-4 py-2"
                  >
                    {languageOptions.map((language) => (
                      <option key={language.value} value={language.value}>
                        {language.label}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="md:col-span-2">
                  <label className="block text-sm font-medium mb-2">Address</label>
                  <textarea
                    name="address"
                    value={formData.address}
                    onChange={handleChange}
                    className="w-full rounded-lg border border-mono-gray-300 px-4 py-2"
                    rows={3}
                    placeholder="Optional address or deployment notes"
                  />
                </div>
              </div>
            )}

            <div>
              <label className="block text-sm font-medium mb-2">Role <span className="text-red-600">*</span></label>
              <select
                name="role"
                value={formData.role}
                onChange={handleChange}
                required
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2"
              >
                <option value="">Select role</option>
                {roles.map((role) => (
                  <option key={role.value} value={role.value}>{role.label}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium mb-2">Status <span className="text-red-600">*</span></label>
              <select
                name="status"
                value={formData.status}
                onChange={handleChange}
                required
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>
            <div className="flex gap-3">
              <Button type="submit" variant="primary" disabled={createMutation.isPending}>
                {createMutation.isPending ? 'Creating...' : 'Create Worker'}
              </Button>
              <Button type="button" variant="secondary" onClick={() => navigate('/admin/dashboard/branch-workers')}>
                Cancel
              </Button>
            </div>
          </div>
        </form>
      </section>
    </div>
  );
};

export default BranchWorkerCreate;
