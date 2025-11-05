import React, { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import type { AxiosError } from 'axios';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Badge from '../../components/ui/Badge';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import Avatar from '../../components/ui/Avatar';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { adminUsersApi } from '../../services/api';
import type { AdminUser, AdminUserCollection, AdminUserFilters, AdminUserPayload } from '../../types/settings';

type UserFormState = {
  name: string;
  email: string;
  mobile: string;
  password: string;
  nid_number: string;
  role_id: string;
  status: string;
  hub_id: string;
  department_id: string;
  designation_id: string;
  joining_date: string;
  salary: string;
  address: string;
  image: File | null;
};

type UserFormErrors = Partial<Record<keyof UserFormState, string>>;

const defaultUserForm: UserFormState = {
  name: '',
  email: '',
  mobile: '',
  password: '',
  nid_number: '',
  role_id: '',
  status: '',
  hub_id: '',
  department_id: '',
  designation_id: '',
  joining_date: '',
  salary: '',
  address: '',
  image: null,
};

const initialFilters: AdminUserFilters = {
  page: 1,
  per_page: 10,
};

const EMPTY_USERS: AdminUser[] = [];

const UsersManagement: React.FC = () => {
  const queryClient = useQueryClient();
  const [filters, setFilters] = useState<AdminUserFilters>(initialFilters);
  const [formState, setFormState] = useState<UserFormState>(defaultUserForm);
  const [formErrors, setFormErrors] = useState<UserFormErrors>({});
  const [selectedUser, setSelectedUser] = useState<AdminUser | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [feedback, setFeedback] = useState<string | null>(null);

  const { data: metaResponse } = useQuery({
    queryKey: ['admin-users', 'meta'],
    queryFn: async () => {
      const response = await adminUsersApi.getMeta();
      return response.data;
    },
    staleTime: 1000 * 60 * 10,
  });

  const { data: usersResponse, isLoading, isError, error } = useQuery<AdminUserCollection & { success?: boolean; message?: string }, Error>({
    queryKey: ['admin-users', filters],
    queryFn: () => adminUsersApi.getUsers(filters),
    placeholderData: (previous) => previous,
  });

  const users = usersResponse?.data ?? EMPTY_USERS;
  const pagination = usersResponse?.meta;

  useEffect(() => {
    if (!formState.status && metaResponse?.statuses?.length) {
      setFormState((prev) => ({
        ...prev,
        status: String(metaResponse.statuses[0].value),
      }));
    }
    if (!formState.role_id && metaResponse?.roles?.length) {
      setFormState((prev) => ({
        ...prev,
        role_id: String(metaResponse.roles[0].id),
      }));
    }
    if (!formState.department_id && metaResponse?.departments?.length) {
      setFormState((prev) => ({
        ...prev,
        department_id: String(metaResponse.departments[0].id),
      }));
    }
    if (!formState.designation_id && metaResponse?.designations?.length) {
      setFormState((prev) => ({
        ...prev,
        designation_id: String(metaResponse.designations[0].id),
      }));
    }
  }, [metaResponse, formState.status, formState.role_id, formState.department_id, formState.designation_id]);

  const createOrUpdateUser = useMutation({
    mutationFn: async (payload: AdminUserPayload) => {
      if (selectedUser) {
        return adminUsersApi.updateUser(selectedUser.id, payload);
      }
      return adminUsersApi.createUser(payload);
    },
    onSuccess: (response) => {
      setFeedback(response.message ?? 'User saved successfully.');
      setFormErrors({});
      setSelectedUser(null);
      setShowForm(false);
      setFormState((prev) => ({
        ...defaultUserForm,
        status: prev.status || (metaResponse?.statuses?.length ? String(metaResponse.statuses[0].value) : ''),
        role_id: prev.role_id || (metaResponse?.roles?.length ? String(metaResponse.roles[0].id) : ''),
        department_id: prev.department_id || (metaResponse?.departments?.length ? String(metaResponse.departments[0].id) : ''),
        designation_id: prev.designation_id || (metaResponse?.designations?.length ? String(metaResponse.designations[0].id) : ''),
      }));
      queryClient.invalidateQueries({ queryKey: ['admin-users'] });
    },
    onError: (axiosError: AxiosError<{ errors?: Record<string, string[]>; message?: string }>) => {
      const validationErrors = axiosError.response?.data?.errors;
      if (validationErrors) {
        const parsed = Object.entries(validationErrors).reduce<UserFormErrors>((acc, [key, messages]) => {
          acc[key as keyof UserFormState] = messages[0] ?? 'Invalid value';
          return acc;
        }, {});
        setFormErrors(parsed);
      } else {
        setFeedback(axiosError.response?.data?.message ?? 'Unable to save user.');
      }
    },
  });

  const deleteUser = useMutation({
    mutationFn: (userId: number) => adminUsersApi.deleteUser(userId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-users'] });
      if (selectedUser) {
        setSelectedUser(null);
        setShowForm(false);
      }
    },
  });

  const summary = useMemo(() => {
    const total = users.length;
    const active = users.filter((user: AdminUser) => user.status_label === 'active').length;
    const onboarded = users.filter((user: AdminUser) => Boolean(user.joining_date)).length;
    return { total, active, onboarded };
  }, [users]);

  const handleFilterChange = (field: keyof AdminUserFilters, value: string) => {
    setFilters((prev) => ({
      ...prev,
      [field]: value ? Number(value) : undefined,
      page: 1,
    }));
  };

  const handleSearchChange = (value: string) => {
    setFilters((prev) => ({
      ...prev,
      search: value || undefined,
      page: 1,
    }));
  };

  const handlePageChange = (direction: 'next' | 'prev') => {
    if (!pagination) return;
    const current = filters.page ?? 1;
    const nextPage = direction === 'next'
      ? Math.min(current + 1, pagination.last_page)
      : Math.max(current - 1, 1);

    if (nextPage !== current) {
      setFilters((prev) => ({ ...prev, page: nextPage }));
    }
  };

  const populateFormFromUser = (user: AdminUser) => {
    setFormState({
      name: user.name ?? '',
      email: user.email ?? '',
      mobile: user.mobile ?? '',
      password: '',
      nid_number: user.nid_number ?? '',
      role_id: user.role ? String(user.role.id) : '',
      status: String(user.status),
      hub_id: user.hub ? String(user.hub.id) : '',
      department_id: user.department ? String(user.department.id) : '',
      designation_id: user.designation ? String(user.designation.id) : '',
      joining_date: user.joining_date ?? '',
      salary: user.salary != null ? String(user.salary) : '',
      address: user.address ?? '',
      image: null,
    });
  };

  const handleEditUser = async (user: AdminUser) => {
    try {
      const response = await adminUsersApi.getUser(user.id);
      setSelectedUser(response.data);
      populateFormFromUser(response.data);
      setShowForm(true);
      setFeedback(null);
      setFormErrors({});
    } catch (fetchError) {
      console.error(fetchError);
      alert('Unable to load user details.');
    }
  };

  const handleDeleteUser = (user: AdminUser) => {
    if (window.confirm(`Delete “${user.name}”? This action cannot be undone.`)) {
      deleteUser.mutate(user.id);
    }
  };

  const handleFormChange = (field: keyof UserFormState, value: string | File | null) => {
    setFormState((prev) => ({
      ...prev,
      [field]: value as never,
    }));
    setFormErrors((prev) => ({ ...prev, [field]: undefined }));
    setFeedback(null);
  };

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setFormErrors({});
    setFeedback(null);

    if (!formState.name.trim()) {
      setFormErrors({ name: 'Name is required.' });
      return;
    }

    if (!formState.email.trim()) {
      setFormErrors({ email: 'Email is required.' });
      return;
    }

    if (!formState.mobile.trim()) {
      setFormErrors({ mobile: 'Mobile number is required.' });
      return;
    }

    if (!selectedUser && !formState.password.trim()) {
      setFormErrors({ password: 'Password is required for new users.' });
      return;
    }

    if (!formState.joining_date) {
      setFormErrors({ joining_date: 'Joining date is required.' });
      return;
    }

    if (!formState.address.trim()) {
      setFormErrors({ address: 'Address is required.' });
      return;
    }

    const payload: AdminUserPayload = {
      name: formState.name.trim(),
      email: formState.email.trim(),
      password: formState.password ? formState.password : undefined,
      mobile: formState.mobile.trim(),
      nid_number: formState.nid_number.trim() || undefined,
      role_id: Number(formState.role_id),
      status: Number(formState.status),
      hub_id: formState.hub_id ? Number(formState.hub_id) : undefined,
      department_id: Number(formState.department_id),
      designation_id: Number(formState.designation_id),
      joining_date: formState.joining_date,
      salary: formState.salary ? Number(formState.salary) : undefined,
      address: formState.address.trim(),
      image: formState.image,
    };

    createOrUpdateUser.mutate(payload);
  };

  const resetForm = () => {
    setSelectedUser(null);
    setShowForm(false);
    setFeedback(null);
    setFormErrors({});
    setFormState((prev) => ({
      ...defaultUserForm,
      status: prev.status || (metaResponse?.statuses?.length ? String(metaResponse.statuses[0].value) : ''),
      role_id: prev.role_id || (metaResponse?.roles?.length ? String(metaResponse.roles[0].id) : ''),
      department_id: prev.department_id || (metaResponse?.departments?.length ? String(metaResponse.departments[0].id) : ''),
      designation_id: prev.designation_id || (metaResponse?.designations?.length ? String(metaResponse.designations[0].id) : ''),
    }));
  };

  if (isLoading && !usersResponse) {
    return <LoadingSpinner message="Loading users" />;
  }

  if (isError) {
    const message = error instanceof Error ? error.message : 'Unable to load users.';
    return (
      <div className="flex min-h-[300px] items-center justify-center">
        <Card className="max-w-md p-8 text-center">
          <div className="space-y-3">
            <div className="inline-flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-red-600">
              <i className="fas fa-triangle-exclamation text-xl" aria-hidden="true" />
            </div>
            <h2 className="text-2xl	font-semibold text-mono-black">Failed to load users</h2>
            <p className="text-sm text-mono-gray-600">{message}</p>
          </div>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-8">
      <header className="space-y-3">
        <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
          Access Control
        </p>
        <div className="space-y-3">
          <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
            Users & Roles — Users
          </h1>
          <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
            Govern access to the platform by inviting team members, assigning the right roles, and managing operational oversight.
          </p>
        </div>
      </header>

      <div className="grid gap-4 md:grid-cols-3">
        <Card className="p-5">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Team Members</p>
          <p className="mt-2 text-3xl font-semibold text-mono-black">{summary.total}</p>
        </Card>
        <Card className="p-5">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Active</p>
          <p className="mt-2 text-3xl font-semibold text-green-600">{summary.active}</p>
        </Card>
        <Card className="p-5">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Onboarded</p>
          <p className="mt-2 text-3xl font-semibold text-mono-black">{summary.onboarded}</p>
        </Card>
      </div>

      <div className="space-y-6 rounded-3xl border border-mono-gray-200 bg-mono-white p-8 shadow-sm">
        <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div className="flex flex-wrap gap-3">
            <div className="min-w-[240px] flex-1">
              <Input
                placeholder="Search team members…"
                onChange={(event) => handleSearchChange(event.target.value)}
                defaultValue={filters.search ?? ''}
              />
            </div>
            <div className="min-w-[180px]">
              <Select
                value={filters.role_id ? String(filters.role_id) : ''}
                onChange={(event) => handleFilterChange('role_id', event.target.value)}
                options={[
                  { value: '', label: 'All roles' },
                  ...(metaResponse?.roles ?? []).map((role) => ({
                    value: String(role.id),
                    label: role.name,
                  })),
                ]}
              />
            </div>
            <div className="min-w-[160px]">
              <Select
                value={filters.status ? String(filters.status) : ''}
                onChange={(event) => handleFilterChange('status', event.target.value)}
                options={[
                  { value: '', label: 'All statuses' },
                  ...(metaResponse?.statuses ?? []).map((status) => ({
                    value: String(status.value),
                    label: status.label,
                  })),
                ]}
              />
            </div>
          </div>
          <div className="flex items-center gap-3">
            <Button
              variant="secondary"
              onClick={() => {
                setFilters(initialFilters);
                queryClient.invalidateQueries({ queryKey: ['admin-users'] });
              }}
            >
              Reset Filters
            </Button>
            <Button
              variant="primary"
              onClick={() => {
                resetForm();
                setShowForm(true);
              }}
            >
              <i className="fas fa-plus mr-2" aria-hidden="true" />
              Add Team Member
            </Button>
          </div>
        </div>

        {feedback && !showForm && (
          <div className="rounded-lg bg-mono-gray-100 p-3 text-sm text-mono-gray-700">
            {feedback}
          </div>
        )}

        {showForm && (
          <Card className="border border-mono-gray-200 p-6">
            <form className="space-y-6" onSubmit={handleSubmit}>
              <div className="grid gap-4 md:grid-cols-2">
                <Input
                  label="Full Name"
                  value={formState.name}
                  onChange={(event) => handleFormChange('name', event.target.value)}
                  required
                  error={formErrors.name}
                />
                <Input
                  label="Email"
                  type="email"
                  value={formState.email}
                  onChange={(event) => handleFormChange('email', event.target.value)}
                  required
                  error={formErrors.email}
                />
                <Input
                  label="Mobile"
                  value={formState.mobile}
                  onChange={(event) => handleFormChange('mobile', event.target.value)}
                  required
                  error={formErrors.mobile}
                />
                <Input
                  label="National ID"
                  value={formState.nid_number}
                  onChange={(event) => handleFormChange('nid_number', event.target.value)}
                  error={formErrors.nid_number}
                />
                <Input
                  label="Password"
                  type="password"
                  value={formState.password}
                  onChange={(event) => handleFormChange('password', event.target.value)}
                  placeholder={selectedUser ? 'Leave blank to keep existing password' : ''}
                  error={formErrors.password}
                />
                <Input
                  label="Joining Date"
                  type="date"
                  value={formState.joining_date}
                  onChange={(event) => handleFormChange('joining_date', event.target.value)}
                  required
                  error={formErrors.joining_date}
                />
                <Input
                  label="Salary"
                  type="number"
                  value={formState.salary}
                  onChange={(event) => handleFormChange('salary', event.target.value)}
                  error={formErrors.salary}
                />
                <Select
                  label="Role"
                  value={formState.role_id}
                  onChange={(event) => handleFormChange('role_id', event.target.value)}
                  options={(metaResponse?.roles ?? []).map((role) => ({
                    value: String(role.id),
                    label: role.name,
                  }))}
                  error={formErrors.role_id}
                />
                <Select
                  label="Status"
                  value={formState.status}
                  onChange={(event) => handleFormChange('status', event.target.value)}
                  options={(metaResponse?.statuses ?? []).map((status) => ({
                    value: String(status.value),
                    label: status.label,
                  }))}
                  error={formErrors.status}
                />
                <Select
                  label="Hub"
                  value={formState.hub_id}
                  onChange={(event) => handleFormChange('hub_id', event.target.value)}
                  options={[
                    { value: '', label: 'Unassigned' },
                    ...(metaResponse?.hubs ?? []).map((hub) => ({
                      value: String(hub.id),
                      label: hub.name,
                    })),
                  ]}
                  error={formErrors.hub_id}
                />
                <Select
                  label="Department"
                  value={formState.department_id}
                  onChange={(event) => handleFormChange('department_id', event.target.value)}
                  options={(metaResponse?.departments ?? []).map((department) => ({
                    value: String(department.id),
                    label: department.title,
                  }))}
                  error={formErrors.department_id}
                />
                <Select
                  label="Designation"
                  value={formState.designation_id}
                  onChange={(event) => handleFormChange('designation_id', event.target.value)}
                  options={(metaResponse?.designations ?? []).map((designation) => ({
                    value: String(designation.id),
                    label: designation.title,
                  }))}
                  error={formErrors.designation_id}
                />
              </div>

              <div className="space-y-2">
                <label className="block text-sm font-medium text-mono-gray-900">
                  Address
                </label>
                <textarea
                  className={`w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-mono-black ${
                    formErrors.address ? 'border-red-500' : 'border-mono-gray-300'
                  }`}
                  rows={3}
                  value={formState.address}
                  onChange={(event) => handleFormChange('address', event.target.value)}
                />
                {formErrors.address && (
                  <p className="text-sm text-red-600">{formErrors.address}</p>
                )}
              </div>

              <div className="space-y-2">
                <label className="block text-sm font-medium text-mono-gray-900">
                  Profile Image
                </label>
                <input
                  type="file"
                  accept="image/*"
                  onChange={(event) => handleFormChange('image', event.target.files?.[0] ?? null)}
                />
                {formErrors.image && (
                  <p className="text-sm text-red-600">{formErrors.image}</p>
                )}
              </div>

              <div className="flex items-center gap-3">
                <Button
                  type="submit"
                  variant="primary"
                  disabled={createOrUpdateUser.isPending}
                >
                  {selectedUser ? 'Update User' : 'Create User'}
                </Button>
                <Button variant="ghost" type="button" onClick={resetForm}>
                  Cancel
                </Button>
              </div>
            </form>
          </Card>
        )}

        <div className="overflow-x-auto rounded-3xl border border-mono-gray-200">
          <table className="w-full divide-y divide-mono-gray-200">
            <thead>
              <tr className="text-left text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                <th className="px-6 py-3">Member</th>
                <th className="px-6 py-3">Role</th>
                <th className="px-6 py-3">Hub</th>
                <th className="px-6 py-3">Status</th>
                <th className="px-6 py-3">Joined</th>
                <th className="px-6 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-mono-gray-200">
              {users.map((user) => (
                <tr key={user.id} className="text-sm text-mono-gray-800">
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-3">
                      <Avatar
                        src={user.avatar ?? undefined}
                        fallback={user.name ? user.name[0] : '?'}
                        size="sm"
                      />
                      <div>
                        <p className="font-medium text-mono-black">{user.name}</p>
                        <p className="text-xs text-mono-gray-500">{user.email}</p>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4">{user.role?.name ?? '—'}</td>
                  <td className="px-6 py-4">{user.hub?.name ?? '—'}</td>
                  <td className="px-6 py-4">
                    <Badge variant={user.status_label === 'active' ? 'solid' : 'outline'} size="sm">
                      {user.status_label === 'active' ? 'Active' : 'Inactive'}
                    </Badge>
                  </td>
                  <td className="px-6 py-4">
                    {user.joining_date ? new Date(user.joining_date).toLocaleDateString() : '—'}
                  </td>
                  <td className="px-6 py-4 text-right">
                    <div className="flex justify-end gap-2">
                      <Button variant="ghost" size="sm" onClick={() => handleEditUser(user)}>
                        Edit
                      </Button>
                      <Button variant="ghost" size="sm" onClick={() => handleDeleteUser(user)}>
                        Delete
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {users.length === 0 && (
          <div className="rounded-xl border border-dashed border-mono-gray-300 p-8 text-center text-sm text-mono-gray-600">
            No team members found. Invite your first teammate.
          </div>
        )}

        {pagination && (
          <div className="flex items-center justify-between border-t border-mono-gray-200 pt-4 text-sm text-mono-gray-600">
            <span>
              Page {pagination.current_page} of {pagination.last_page}
            </span>
            <div className="flex gap-2">
              <Button
                variant="ghost"
                size="sm"
                disabled={(pagination?.current_page ?? 1) <= 1}
                onClick={() => handlePageChange('prev')}
              >
                Previous
              </Button>
              <Button
                variant="ghost"
                size="sm"
                disabled={(pagination?.current_page ?? 1) >= (pagination?.last_page ?? 1)}
                onClick={() => handlePageChange('next')}
              >
                Next
              </Button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default UsersManagement;
