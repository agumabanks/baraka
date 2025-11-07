import React, { useEffect, useMemo, useState, useCallback } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import type { AxiosError } from 'axios';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Badge from '../../components/ui/Badge';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { adminRolesApi } from '../../services/api';
import type { AdminRole, AdminRolePayload, AdminRoleCollection } from '../../types/settings';

type RoleFilters = {
  search?: string;
  status?: string;
  page: number;
  per_page: number;
};

type RoleFormState = {
  name: string;
  status: string;
  permissions: string[];
};

type RoleFormErrors = Partial<Record<keyof RoleFormState, string>>;

type RolePanelMode = 'form' | 'details' | null;

type RoleFilterChip = {
  field: keyof RoleFilters;
  label: string;
};

const initialFilters: RoleFilters = {
  page: 1,
  per_page: 10,
};

const defaultFormState: RoleFormState = {
  name: '',
  status: '',
  permissions: [],
};

const EMPTY_ROLES: AdminRole[] = [];

const RolesManagement: React.FC = () => {
  const queryClient = useQueryClient();
  const [filters, setFilters] = useState<RoleFilters>(initialFilters);
  const [formState, setFormState] = useState<RoleFormState>(defaultFormState);
  const [formErrors, setFormErrors] = useState<RoleFormErrors>({});
  const [selectedRole, setSelectedRole] = useState<AdminRole | null>(null);
  const [feedback, setFeedback] = useState<string | null>(null);
  const [panelMode, setPanelMode] = useState<RolePanelMode>('form');
  const [detailRole, setDetailRole] = useState<AdminRole | null>(null);

  const { data: metaResponse, isLoading: metaLoading } = useQuery({
    queryKey: ['admin-roles', 'meta'],
    queryFn: async () => {
      const response = await adminRolesApi.getMeta();
      return response.data;
    },
    staleTime: 1000 * 60 * 10,
  });

  const defaultStatusOption = useMemo(() => (
    metaResponse?.statuses?.length ? String(metaResponse.statuses[0].value) : ''
  ), [metaResponse]);

  const { data: roleResponse, isLoading, isError, error } = useQuery<AdminRoleCollection & { success?: boolean; message?: string }, Error>({
    queryKey: ['admin-roles', filters],
    queryFn: () => adminRolesApi.getRoles({
      page: filters.page,
      per_page: filters.per_page,
      search: filters.search,
      status: filters.status ? Number(filters.status) : undefined,
    }),
    placeholderData: (previous) => previous,
  });

  const roles = roleResponse?.data ?? EMPTY_ROLES;
  const pagination = roleResponse?.meta;

  useEffect(() => {
    if (!formState.status && defaultStatusOption) {
      setFormState((prev) => ({
        ...prev,
        status: defaultStatusOption,
      }));
    }
  }, [defaultStatusOption, formState.status]);

  useEffect(() => {
    if (selectedRole) {
      setFormState({
        name: selectedRole.name,
        status: String(selectedRole.status),
        permissions: selectedRole.permissions ?? [],
      });
    } else {
      setFormState((prev) => {
        if (prev.name === '' && prev.permissions.length === 0 && prev.status === (defaultStatusOption || '')) {
          return prev;
        }

        return {
          ...defaultFormState,
          status: defaultStatusOption || '',
        };
      });
      setFormErrors({});
    }
  }, [selectedRole, defaultStatusOption]);

  const createOrUpdateRole = useMutation({
    mutationFn: async (payload: AdminRolePayload) => {
      if (selectedRole) {
        return adminRolesApi.updateRole(selectedRole.id, payload);
      }
      return adminRolesApi.createRole(payload);
    },
    onSuccess: (response) => {
      setFeedback(response.message ?? 'Role saved successfully.');
      setFormErrors({});
      setSelectedRole(null);
      setFormState({
        ...defaultFormState,
        status: defaultStatusOption || '',
      });
      if (response.data) {
        setDetailRole(response.data);
        setPanelMode('details');
      } else {
        setPanelMode('form');
      }
      queryClient.invalidateQueries({ queryKey: ['admin-roles'] });
    },
    onError: (axiosError: AxiosError<{ errors?: Record<string, string[]>; message?: string }>) => {
      const validationErrors = axiosError.response?.data?.errors;
      if (validationErrors) {
        const parsed = Object.entries(validationErrors).reduce<RoleFormErrors>((acc, [key, messages]) => {
          acc[key as keyof RoleFormState] = messages[0] ?? 'Invalid value';
          return acc;
        }, {});
        setFormErrors(parsed);
      } else {
        setFeedback(axiosError.response?.data?.message ?? 'Unable to save role.');
      }
    },
  });

  const deleteRole = useMutation({
    mutationFn: (roleId: number) => adminRolesApi.deleteRole(roleId),
    onSuccess: (_, roleId) => {
      queryClient.invalidateQueries({ queryKey: ['admin-roles'] });
      if (selectedRole?.id === roleId) {
        setSelectedRole(null);
        setPanelMode('form');
      }
      if (detailRole?.id === roleId) {
        setDetailRole(null);
        setPanelMode('form');
      }
    },
  });

  const toggleStatus = useMutation({
    mutationFn: (roleId: number) => adminRolesApi.toggleStatus(roleId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-roles'] });
    },
  });

  const summary = useMemo(() => {
    const total = roleResponse?.meta?.total ?? roles.length;
    const activeOnPage = roles.filter((role: AdminRole) => role.status_label === 'active').length;
    const inactiveOnPage = roles.filter((role: AdminRole) => role.status_label !== 'active').length;
    const assignedUsers = roles.reduce((accumulator: number, role: AdminRole) => accumulator + (role.users_count ?? 0), 0);
    const permissionsOnPage = roles.reduce((accumulator: number, role: AdminRole) => accumulator + (role.permissions_count ?? role.permissions?.length ?? 0), 0);
    return { total, activeOnPage, inactiveOnPage, assignedUsers, permissionsOnPage };
  }, [roles, roleResponse]);

  useEffect(() => {
    if (selectedRole) {
      const refreshed = roles.find((role) => role.id === selectedRole.id);
      if (refreshed && refreshed !== selectedRole) {
        setSelectedRole(refreshed);
      }
    }

    if (detailRole) {
      const refreshedDetail = roles.find((role) => role.id === detailRole.id);
      if (refreshedDetail && refreshedDetail !== detailRole) {
        setDetailRole(refreshedDetail);
      }
    }
  }, [roles, selectedRole, detailRole]);

  const filterChips = useMemo<RoleFilterChip[]>(() => {
    const chips: RoleFilterChip[] = [];

    if (filters.search) {
      chips.push({ field: 'search', label: `Search: “${filters.search}”` });
    }
    if (filters.status) {
      const numericStatus = Number(filters.status);
      const label = metaResponse?.statuses?.find((status) => status.value === numericStatus)?.label ?? `Status #${filters.status}`;
      chips.push({ field: 'status', label: `Status: ${label}` });
    }

    return chips;
  }, [filters, metaResponse]);

  const isFiltersDirty = useMemo(() => filterChips.length > 0, [filterChips]);

  const canResetFilters = useMemo(() => {
    const perPageChanged = filters.per_page !== initialFilters.per_page;
    const pageChanged = (filters.page ?? initialFilters.page) !== initialFilters.page;
    return isFiltersDirty || perPageChanged || pageChanged;
  }, [filters, isFiltersDirty]);

  const handleRemoveFilter = useCallback((field: keyof RoleFilters) => {
    setFilters((prev) => {
      const next: RoleFilters = {
        ...prev,
        page: 1,
      };

      if (field === 'per_page') {
        next.per_page = initialFilters.per_page;
      } else if (field === 'page') {
        next.page = 1;
      } else {
        next[field] = undefined as never;
      }

      return next;
    });
  }, []);

  const formatDateTime = useCallback((value: string | null | undefined) => {
    if (!value) {
      return '—';
    }

    try {
      return new Intl.DateTimeFormat(undefined, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      }).format(new Date(value));
    } catch (error) {
      console.error('Failed to format date/time', error);
      return value;
    }
  }, []);

  const formatPermissionLabel = (permission: { attribute: string | null; keywords: string[] | null }) => {
    if (!permission) {
      return '';
    }
    if (permission.keywords?.length) {
      return permission.keywords.join(' • ');
    }
    if (permission.attribute) {
      return permission.attribute.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    }
    return 'Unnamed Permission';
  };

  const handleFormChange = (field: keyof RoleFormState, value: string | string[]) => {
    setFormState((prev) => ({
      ...prev,
      [field]: value,
    }));
    setFormErrors((prev) => ({ ...prev, [field]: undefined }));
    setFeedback(null);
  };

  const handlePermissionToggle = (value: string) => {
    setFormState((prev) => {
      const exists = prev.permissions.includes(value);
      const permissions = exists
        ? prev.permissions.filter((item) => item !== value)
        : prev.permissions.concat(value);
      return { ...prev, permissions };
    });
  };

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setFormErrors({});
    setFeedback(null);

    if (!formState.name.trim()) {
      setFormErrors({ name: 'Name is required.' });
      return;
    }

    if (!formState.status) {
      setFormErrors({ status: 'Status is required.' });
      return;
    }

    const payload: AdminRolePayload = {
      name: formState.name.trim(),
      status: Number(formState.status),
      permissions: formState.permissions,
    };

    createOrUpdateRole.mutate(payload);
  };

  const handleEdit = (role: AdminRole) => {
    setSelectedRole(role);
    setPanelMode('form');
    setDetailRole(null);
    setFeedback(null);
  };

  const handleView = (role: AdminRole) => {
    setDetailRole(role);
    setPanelMode('details');
    setSelectedRole(null);
    setFeedback(null);
  };

  const handleDelete = (role: AdminRole) => {
    if (role.users_count > 0) {
      alert('Cannot delete a role that still has assigned users.');
      return;
    }

    if (window.confirm(`Delete role “${role.name}”? This cannot be undone.`)) {
      deleteRole.mutate(role.id);
    }
  };

  const handleToggleStatus = (role: AdminRole) => {
    toggleStatus.mutate(role.id);
  };

  const handleSearchChange = (value: string) => {
    setFilters((prev) => ({
      ...prev,
      search: value || undefined,
      page: 1,
    }));
  };

  const handleStatusFilterChange = (value: string) => {
    setFilters((prev) => ({
      ...prev,
      status: value || undefined,
      page: 1,
    }));
  };

  const handlePerPageChange = (value: string) => {
    setFilters((prev) => ({
      ...prev,
      per_page: value ? Number(value) : initialFilters.per_page,
      page: 1,
    }));
  };

  const handlePageChange = (direction: 'next' | 'prev') => {
    if (!pagination) return;
    const nextPage = direction === 'next'
      ? Math.min((filters.page ?? 1) + 1, pagination.last_page)
      : Math.max((filters.page ?? 1) - 1, 1);

    if (nextPage !== filters.page) {
      setFilters((prev) => ({ ...prev, page: nextPage }));
    }
  };

  const handleResetForm = () => {
    setSelectedRole(null);
    setFeedback(null);
    setDetailRole(null);
    setPanelMode('form');
  };

  const handleClosePanel = () => {
    setPanelMode(null);
    setSelectedRole(null);
    setDetailRole(null);
  };

  if (isLoading && !roleResponse) {
    return <LoadingSpinner message="Loading roles" />;
  }

  if (isError) {
    const message = error instanceof Error ? error.message : 'Unable to load roles.';
    return (
      <div className="flex min-h-[300px] items-center justify-center">
        <Card className="max-w-md p-8 text-center">
          <div className="space-y-3">
            <div className="inline-flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-red-600">
              <i className="fas fa-triangle-exclamation text-xl" aria-hidden="true" />
            </div>
            <h2 className="text-2xl font-semibold text-mono-black">Failed to load roles</h2>
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
            Users & Roles — Roles
          </h1>
          <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
            Define permission boundaries, manage access tiers, and keep your organisation aligned with least-privilege principles.
          </p>
        </div>
      </header>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Card className="p-5">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Total Roles</p>
          <p className="mt-2 text-3xl font-semibold text-mono-black">{summary.total}</p>
          <p className="text-xs text-mono-gray-500">Across the organisation</p>
        </Card>
        <Card className="p-5">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Active (page)</p>
          <p className="mt-2 text-3xl font-semibold text-green-600">{summary.activeOnPage}</p>
          <p className="text-xs text-mono-gray-500">Currently visible cohort</p>
        </Card>
        <Card className="p-5">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Assigned Users (page)</p>
          <p className="mt-2 text-3xl font-semibold text-mono-black">{summary.assignedUsers}</p>
          <p className="text-xs text-mono-gray-500">Mapped to listed roles</p>
        </Card>
        <Card className="p-5">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Permissions (page)</p>
          <p className="mt-2 text-3xl font-semibold text-mono-black">{summary.permissionsOnPage}</p>
          <p className="text-xs text-mono-gray-500">Direct capabilities in view</p>
        </Card>
      </div>

      <div className="grid gap-8 lg:grid-cols-[2fr_1fr]">
        <Card className="space-y-6 border border-mono-gray-200 p-8">
          <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div className="flex flex-wrap items-center gap-3">
              <div className="min-w-[220px] flex-1">
                <Input
                  placeholder="Search roles…"
                  onChange={(event) => handleSearchChange(event.target.value)}
                  defaultValue={filters.search ?? ''}
                />
              </div>
              <div className="min-w-[180px]">
                <Select
                  value={filters.status ?? ''}
                  onChange={(event) => handleStatusFilterChange(event.target.value)}
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
            <div className="flex flex-wrap items-center gap-3">
              <div className="min-w-[160px]">
                <Select
                  value={String(filters.per_page)}
                  onChange={(event) => handlePerPageChange(event.target.value)}
                  options={[
                    { value: '10', label: '10 per page' },
                    { value: '25', label: '25 per page' },
                    { value: '50', label: '50 per page' },
                  ]}
                />
              </div>
              <Button
                variant="secondary"
                size="sm"
                disabled={!canResetFilters}
                onClick={() => {
                  setFilters(() => ({ ...initialFilters }));
                  queryClient.invalidateQueries({ queryKey: ['admin-roles'] });
                }}
              >
                Reset Filters
              </Button>
              <Button
                variant="primary"
                size="sm"
                onClick={handleResetForm}
              >
                <i className="fas fa-plus mr-2" aria-hidden="true" />
                Add Role
              </Button>
            </div>
          </div>

          {filterChips.length > 0 && (
            <div className="flex flex-wrap gap-2">
              {filterChips.map((chip) => (
                <Button
                  key={chip.field}
                  type="button"
                  variant="ghost"
                  size="sm"
                  className="rounded-full border border-mono-gray-200 bg-mono-gray-100 text-xs text-mono-gray-700 hover:bg-mono-gray-200 hover:text-mono-black"
                  onClick={() => handleRemoveFilter(chip.field)}
                >
                  {chip.label}
                  <span className="ml-2 text-mono-gray-400">×</span>
                </Button>
              ))}
            </div>
          )}

          {feedback && panelMode !== 'form' && (
            <div className="rounded-lg bg-mono-gray-100 p-3 text-sm text-mono-gray-700">
              {feedback}
            </div>
          )}

          <div className="overflow-x-auto rounded-3xl border border-mono-gray-200">
            <table className="w-full divide-y divide-mono-gray-200">
              <thead>
                <tr className="text-left text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                  <th className="px-6 py-3">Role</th>
                  <th className="px-6 py-3">Status</th>
                  <th className="px-6 py-3">Users</th>
                  <th className="px-6 py-3">Permissions</th>
                  <th className="px-6 py-3">Updated</th>
                  <th className="px-6 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-mono-gray-200">
                {roles.map((role) => (
                  <tr key={role.id} className="text-sm text-mono-gray-800">
                    <td className="px-6 py-4">
                      <div className="flex flex-col">
                        <span className="font-medium text-mono-black">{role.name}</span>
                        <span className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">
                          {role.slug}
                        </span>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <Badge variant={role.status_label === 'active' ? 'solid' : 'outline'} size="sm">
                        {role.status_label === 'active' ? 'Active' : 'Inactive'}
                      </Badge>
                    </td>
                    <td className="px-6 py-4">{role.users_count}</td>
                    <td className="px-6 py-4">{role.permissions_count ?? role.permissions?.length ?? 0}</td>
                    <td className="px-6 py-4">{formatDateTime(role.updated_at)}</td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex justify-end gap-2">
                        <Button variant="ghost" size="sm" onClick={() => handleView(role)}>
                          View
                        </Button>
                        <Button variant="ghost" size="sm" onClick={() => handleEdit(role)}>
                          Edit
                        </Button>
                        <Button variant="ghost" size="sm" onClick={() => handleToggleStatus(role)}>
                          {role.status_label === 'active' ? 'Deactivate' : 'Activate'}
                        </Button>
                        <Button variant="ghost" size="sm" onClick={() => handleDelete(role)}>
                          Delete
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {roles.length === 0 && (
            <div className="rounded-xl border border-dashed border-mono-gray-300 p-8 text-center text-sm text-mono-gray-600">
              No roles found. Create a role to get started.
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
                  disabled={pagination.current_page <= 1}
                  onClick={() => handlePageChange('prev')}
                >
                  Previous
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  disabled={pagination.current_page >= pagination.last_page}
                  onClick={() => handlePageChange('next')}
                >
                  Next
                </Button>
              </div>
            </div>
          )}
        </Card>

        <div className="space-y-4">
          {panelMode === null && (
            <Card className="flex h-full flex-col justify-center gap-3 border border-dashed border-mono-gray-300 p-6 text-sm text-mono-gray-600">
              <h2 className="text-lg font-semibold text-mono-black">Role insights</h2>
              <p>
                Select a role from the table to inspect its permissions, or create a tailored access tier for a new operational need.
              </p>
              <div className="flex flex-wrap gap-2">
                <Button variant="primary" size="sm" onClick={handleResetForm}>
                  Create role
                </Button>
              </div>
            </Card>
          )}

          {panelMode === 'form' && (
            <Card className="space-y-6 border border-mono-gray-200 p-6">
              <div className="flex items-start justify-between gap-4">
                <div className="space-y-1">
                  <h2 className="text-xl font-semibold text-mono-black">
                    {selectedRole ? `Edit ${selectedRole.name}` : 'Create Role'}
                  </h2>
                  <p className="text-sm text-mono-gray-600">
                    {selectedRole
                      ? 'Adjust permissions, align status, and keep governance consistent.'
                      : 'Curate a new access tier with the precise permissions your teams require.'}
                  </p>
                </div>
                <Button variant="ghost" size="sm" type="button" onClick={handleClosePanel}>
                  Close
                </Button>
              </div>

              {feedback && (
                <div className="rounded-lg bg-mono-gray-100 p-3 text-sm text-mono-gray-700">
                  {feedback}
                </div>
              )}

              {metaLoading && !metaResponse ? (
                <LoadingSpinner message="Loading permissions" />
              ) : (
                <form className="space-y-5" onSubmit={handleSubmit}>
                  <Input
                    label="Role Name"
                    value={formState.name}
                    onChange={(event) => handleFormChange('name', event.target.value)}
                    placeholder="Operations Lead"
                    required
                    error={formErrors.name}
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

                  <div className="space-y-2">
                    <p className="text-sm font-medium text-mono-gray-900">Permissions</p>
                    <div className="max-h-64 overflow-y-auto rounded-lg border border-mono-gray-200 p-3">
                      <ul className="space-y-2 text-sm text-mono-gray-700">
                        {(metaResponse?.permissions ?? []).map((permission) => {
                          const attribute = permission.attribute ?? String(permission.id);
                          const label = formatPermissionLabel(permission);
                          const checked = formState.permissions.includes(attribute);
                          return (
                            <li key={permission.id}>
                              <label className="flex cursor-pointer items-center gap-3">
                                <input
                                  type="checkbox"
                                  className="h-4 w-4 rounded border-mono-gray-300 text-mono-black focus:ring-mono-black"
                                  checked={checked}
                                  onChange={() => handlePermissionToggle(attribute)}
                                />
                                <span>{label}</span>
                              </label>
                            </li>
                          );
                        })}
                      </ul>
                    </div>
                    {formErrors.permissions && (
                      <p className="text-sm text-red-600">{formErrors.permissions}</p>
                    )}
                  </div>

                  <div className="flex flex-wrap items-center gap-3 border-t border-mono-gray-200 pt-4">
                    <Button
                      type="submit"
                      variant="primary"
                      disabled={createOrUpdateRole.isPending}
                    >
                      {selectedRole ? 'Update Role' : 'Create Role'}
                    </Button>
                    <Button variant="ghost" type="button" onClick={handleResetForm}>
                      {selectedRole ? 'Cancel' : 'Clear'}
                    </Button>
                  </div>
                </form>
              )}
            </Card>
          )}

          {panelMode === 'details' && (
            <Card className="space-y-6 border border-mono-gray-200 p-6">
              <div className="flex items-start justify-between gap-4">
                <div className="space-y-1">
                  <h2 className="text-xl font-semibold text-mono-black">Role overview</h2>
                  <p className="text-sm text-mono-gray-600">
                    Examine permission coverage, usage, and lifecycle metadata for this access tier.
                  </p>
                </div>
                <Button variant="ghost" size="sm" type="button" onClick={handleClosePanel}>
                  Close
                </Button>
              </div>

              {detailRole ? (
                <div className="space-y-6 text-sm">
                  <div className="space-y-1">
                    <p className="text-lg font-semibold text-mono-black">{detailRole.name}</p>
                    <p className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">{detailRole.slug}</p>
                  </div>

                  <div className="flex flex-wrap gap-2">
                    <Badge variant={detailRole.status_label === 'active' ? 'solid' : 'outline'} size="sm">
                      {detailRole.status_label === 'active' ? 'Active' : 'Inactive'}
                    </Badge>
                    <Badge variant="outline" size="sm">
                      Users: {detailRole.users_count}
                    </Badge>
                    <Badge variant="outline" size="sm">
                      Permissions: {detailRole.permissions_count ?? detailRole.permissions?.length ?? 0}
                    </Badge>
                  </div>

                  <div className="space-y-3 rounded-xl border border-mono-gray-100 bg-mono-gray-50 p-4">
                    <div className="grid gap-3 sm:grid-cols-2">
                      <div>
                        <p className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">Created</p>
                        <p className="mt-1 font-medium text-mono-black">{formatDateTime(detailRole.created_at)}</p>
                      </div>
                      <div>
                        <p className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">Updated</p>
                        <p className="mt-1 font-medium text-mono-black">{formatDateTime(detailRole.updated_at)}</p>
                      </div>
                    </div>
                  </div>

                  <div className="space-y-2">
                    <p className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">Direct permissions</p>
                    {detailRole.permissions?.length ? (
                      <div className="flex flex-wrap gap-2">
                        {detailRole.permissions.map((permission) => (
                          <span
                            key={permission}
                            className="inline-flex items-center rounded-full border border-mono-gray-200 bg-mono-white px-3 py-1 text-xs font-medium text-mono-gray-700"
                          >
                            {permission.replace(/_/g, ' ')}
                          </span>
                        ))}
                      </div>
                    ) : detailRole.permissions_count ? (
                      <p className="text-sm text-mono-gray-600">
                        {detailRole.permissions_count} permissions assigned via backend configuration.
                      </p>
                    ) : (
                      <p className="text-sm text-mono-gray-600">
                        No direct permissions attached; inherits access from higher-level policies.
                      </p>
                    )}
                  </div>

                  <div className="flex flex-wrap gap-2">
                    <Button variant="primary" size="sm" onClick={() => handleEdit(detailRole)}>
                      Edit Role
                    </Button>
                    <Button variant="secondary" size="sm" onClick={() => handleToggleStatus(detailRole)}>
                      {detailRole.status_label === 'active' ? 'Deactivate' : 'Activate'}
                    </Button>
                    <Button variant="ghost" size="sm" onClick={() => handleDelete(detailRole)}>
                      Delete
                    </Button>
                  </div>
                </div>
              ) : (
                <p className="text-sm text-mono-gray-600">
                  Select a role from the table to view its definition.
                </p>
              )}
            </Card>
          )}
        </div>
      </div>
    </div>
  );
};

export default RolesManagement;
