import React, { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import Button from '../../components/ui/Button';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { branchManagersApi } from '../../services/api';
import type { BranchManagerFormData, BranchOption, UserOption } from '../../types/branchManagers';

type BranchManagerCreateFormState = BranchManagerFormData & {
  branch_id: string;
  user_id: string;
  name: string;
  email: string;
  phone: string;
  password: string;
  address: string;
  preferred_language: 'en' | 'fr' | 'sw';
};

const BranchManagerCreate: React.FC = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState<BranchManagerCreateFormState>({
    branch_id: '',
    user_id: '',
    business_name: '',
    status: 'active',
    name: '',
    email: '',
    phone: '',
    password: '',
    address: '',
    preferred_language: 'en',
  });
  const [useExistingUser, setUseExistingUser] = useState(true);

  const { data: formMeta, isLoading: loadingMeta } = useQuery({
    queryKey: ['branch-managers', 'form-meta'],
    queryFn: () => branchManagersApi.getAvailableBranches(),
  });

  const createMutation = useMutation({
    mutationFn: (data: BranchManagerFormData) => branchManagersApi.createManager(data),
    onSuccess: () => {
      navigate('/dashboard/branch-managers');
    },
    onError: (error: any) => {
      console.error('Failed to create manager:', error);
      alert(error?.response?.data?.message || 'Failed to create manager. Please try again.');
    },
  });

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault();

    if (!formData.branch_id || !formData.business_name.trim()) {
      alert('Please select a branch and provide the business name.');
      return;
    }

    if (useExistingUser) {
      if (!formData.user_id) {
        alert('Please select an existing user to assign as branch manager.');
        return;
      }
    } else {
      if (!formData.name.trim() || !formData.email.trim() || !formData.phone.trim() || !formData.password) {
        alert('Please provide name, email, phone, and password for the new manager account.');
        return;
      }
    }

    const payload: BranchManagerFormData = {
      branch_id: Number(formData.branch_id),
      business_name: formData.business_name.trim(),
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
    }

    createMutation.mutate(payload);
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
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

  if (loadingMeta) {
    return <LoadingSpinner message="Loading form data" />;
  }

  const availableBranches: BranchOption[] = formMeta?.data?.branches || [];
  const availableUsers: UserOption[] = formMeta?.data?.users || [];
  const languageOptions = useMemo(() => ([
    { value: 'en', label: 'English' },
    { value: 'fr', label: 'Français' },
    { value: 'sw', label: 'Kiswahili' },
  ]), []);

  return (
    <div className="space-y-6">
      <section className="rounded-3xl border border-mono-gray-200 bg-mono-white shadow-xl">
        <header className="flex flex-col gap-6 border-b border-mono-gray-200 px-8 py-10">
          <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Branch Management
            </p>
            <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
              Create Branch Manager
            </h1>
            <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
              Assign a new manager to a branch. The manager will be responsible for overseeing operations at the branch.
            </p>
          </div>
        </header>

        <form onSubmit={handleSubmit} className="px-8 py-8">
          <div className="max-w-2xl space-y-6">
            {/* Branch Selection */}
            <div>
              <label className="block text-sm font-medium text-mono-black mb-2">
                Branch <span className="text-red-600">*</span>
              </label>
              <select
                name="branch_id"
                value={formData.branch_id}
                onChange={handleChange}
                required
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 text-sm focus:border-mono-black focus:outline-none"
              >
                <option value="">Select a branch</option>
                {availableBranches.map((branch: BranchOption) => (
                  <option key={branch.value} value={branch.value}>
                    {branch.label} ({branch.code})
                  </option>
                ))}
              </select>
              <p className="mt-1 text-xs text-mono-gray-600">
                Select the branch this manager will oversee
              </p>
            </div>

            {/* Manager account mode */}
            <div>
              <label className="block text-sm font-medium text-mono-black mb-2">
                Manager Account
              </label>
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
                <label className="block text-sm font-medium text-mono-black mb-2">
                  Select Existing User <span className="text-red-600">*</span>
                </label>
                <select
                  name="user_id"
                  value={formData.user_id}
                  onChange={handleChange}
                  required={useExistingUser}
                  className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 text-sm focus:border-mono-black focus:outline-none"
                >
                  <option value="">Choose a user</option>
                  {availableUsers.map((user: UserOption) => (
                    <option key={user.value} value={user.value}>
                      {user.label} ({user.email})
                      {user.preferred_language ? ` · ${user.preferred_language.toUpperCase()}` : ''}
                    </option>
                  ))}
                </select>
                <p className="mt-1 text-xs text-mono-gray-600">
                  Link an existing system user as this branch manager.
                </p>
                {availableUsers.length === 0 && (
                  <p className="mt-2 text-xs text-mono-red-600">
                    No eligible users available. Switch to "Create new user account" to provision a manager.
                  </p>
                )}
              </div>
            ) : (
              <div className="grid gap-4 md:grid-cols-2">
                <div>
                  <label className="block text-sm font-medium text-mono-black mb-2">
                    Full Name <span className="text-red-600">*</span>
                  </label>
                  <input
                    type="text"
                    name="name"
                    value={formData.name}
                    onChange={handleChange}
                    className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 text-sm focus:border-mono-black focus:outline-none"
                    placeholder="Enter full name"
                    required={!useExistingUser}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-mono-black mb-2">
                    Email <span className="text-red-600">*</span>
                  </label>
                  <input
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 text-sm focus:border-mono-black focus:outline-none"
                    placeholder="manager@example.com"
                    required={!useExistingUser}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-mono-black mb-2">
                    Phone <span className="text-red-600">*</span>
                  </label>
                  <input
                    type="tel"
                    name="phone"
                    value={formData.phone}
                    onChange={handleChange}
                    className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 text-sm focus:border-mono-black focus:outline-none"
                    placeholder="e.g. +254700000000"
                    required={!useExistingUser}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-mono-black mb-2">
                    Password <span className="text-red-600">*</span>
                  </label>
                  <input
                    type="password"
                    name="password"
                    value={formData.password}
                    onChange={handleChange}
                    className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 text-sm focus:border-mono-black focus:outline-none"
                    placeholder="Set initial password"
                    required={!useExistingUser}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-mono-black mb-2">
                    Preferred Language
                  </label>
                  <select
                    name="preferred_language"
                    value={formData.preferred_language}
                    onChange={handleChange}
                    className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 text-sm focus:border-mono-black focus:outline-none"
                  >
                    {languageOptions.map(language => (
                      <option key={language.value} value={language.value}>
                        {language.label}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-mono-black mb-2">
                    Address
                  </label>
                  <textarea
                    name="address"
                    value={formData.address}
                    onChange={handleChange}
                    className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 text-sm focus:border-mono-black focus:outline-none"
                    rows={3}
                    placeholder="Optional address or notes"
                  />
                </div>
              </div>
            )}

            {/* Business Name */}
            <div>
              <label className="block text-sm font-medium text-mono-black mb-2">
                Business Name <span className="text-red-600">*</span>
              </label>
              <input
                type="text"
                name="business_name"
                value={formData.business_name}
                onChange={handleChange}
                required
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 text-sm focus:border-mono-black focus:outline-none"
                placeholder="Enter business name"
              />
              <p className="mt-1 text-xs text-mono-gray-600">
                The name of the business entity
              </p>
            </div>

            {/* Status */}
            <div>
              <label className="block text-sm font-medium text-mono-black mb-2">
                Status <span className="text-red-600">*</span>
              </label>
              <select
                name="status"
                value={formData.status}
                onChange={handleChange}
                required
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 text-sm focus:border-mono-black focus:outline-none"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>

            {/* Form Actions */}
            <div className="flex gap-3 pt-4">
              <Button
                type="submit"
                variant="primary"
                disabled={createMutation.isPending}
              >
                {createMutation.isPending ? (
                  <>
                    <i className="fas fa-spinner fa-spin mr-2" aria-hidden="true" />
                    Creating...
                  </>
                ) : (
                  <>
                    <i className="fas fa-check mr-2" aria-hidden="true" />
                    Create Manager
                  </>
                )}
              </Button>
              <Button
                type="button"
                variant="secondary"
                onClick={() => navigate('/dashboard/branch-managers')}
                disabled={createMutation.isPending}
              >
                Cancel
              </Button>
            </div>
          </div>
        </form>
      </section>
    </div>
  );
};

export default BranchManagerCreate;
