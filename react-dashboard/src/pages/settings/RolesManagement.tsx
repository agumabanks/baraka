import React, { useEffect, useMemo, useState } from 'react';
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

  const { data: metaResponse, isLoading: metaLoading } = useQuery({
    queryKey: ['admin-roles', 'meta'],
    queryFn: async () => {
      const response = await adminRolesApi.getMeta();
      return response.data;
    },
    staleTime: 1000 * 60 * 10,
  });

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
    if (!formState.status && metaResponse?.statuses?.length) {
      setFormState((prev) => ({
        ...prev,
        status: String(metaResponse.statuses[0].value),
      }));
    }
  }, [metaResponse, formState.status]);

  useEffect(() => {
    if (selectedRole) {
      setFormState({
        name: selectedRole.name,
        status: String(selectedRole.status),
        permissions: selectedRole.permissions ?? [],
      });
    } else {
      setFormState((prev) => ({
        ...defaultFormState,
        status: prev.status || (metaResponse?.statuses?.length ? String(metaResponse.statuses[0].value) : ''),
      }));
      setFormErrors({});
    }
  }, [selectedRole, metaResponse]);

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
      setFormState((prev) => ({
        ...defaultFormState,
        status: prev.status || (metaResponse?.statuses?.length ? String(metaResponse.statuses[0].value) : ''),
      }));
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
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-roles'] });
      if (selectedRole && selectedRole.id) {
        setSelectedRole(null);
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
    const total = roles.length;
    const active = roles.filter((role: AdminRole) => role.status_label === 'active').length;
    const assignedUsers = roles.reduce((accumulator: number, role: AdminRole) => accumulator + (role.users_count ?? 0), 0);
    return { total, active, assignedUsers };
  }, [roles]);

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

      <div className="grid gap-4 md:grid-cols-3">
        <Card className="p-5">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Total Roles</p>
          <p className="mt-2 text-3xl font-semibold text-mono-black">{summary.total}</p>
        </Card>
        <Card className="p-5">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Active</p>
          <p className="mt-2 text-3xl font-semibold text-green-600">{summary.active}</p>
        </Card>
        <Card className="p-5">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Assigned Users</p>
          <p className="mt-2 text-3xl font-semibold text-mono-black">{summary.assignedUsers}</p>
        </Card>
      </div>

      <div className="grid gap-8 lg:grid-cols-[2fr_1fr]">
        <Card className="space-y-6 border border-mono-gray-200 p-8">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex flex-wrap gap-3 sm:flex-row sm:items-center">
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
            <div className="flex gap-3">
              <Button
                variant="secondary"
                size="sm"
                onClick={() => {
                  setFilters(initialFilters);
                  queryClient.invalidateQueries({ queryKey: ['admin-roles'] });
                }}
              >
                Reset Filters
              </Button>
            </div>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full divide-y divide-mono-gray-200">
              <thead>
                <tr className="text-left text-xs font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                  <th className="py-3">Role</th>
                  <th className="py-3">Status</th>
                  <th className="py-3">Users</th>
                  <th className="py-3">Permissions</th>
                  <th className="py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-mono-gray-200">
                {roles.map((role) => (
                  <tr key={role.id} className="text-sm text-mono-gray-800">
                    <td className="py-4">
                      <div className="flex flex-col">
                        <span className="font-medium text-mono-black">{role.name}</span>
                        <span className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">
                          {role.slug}
                        </span>
                      </div>
                    </td>
                    <td className="py-4">
                      <Badge variant={role.status_label === 'active' ? 'solid' : 'outline'} size="sm">
                        {role.status_label === 'active' ? 'Active' : 'Inactive'}
                      </Badge>
                    </td>
                    <td className="py-4">{role.users_count}</td>
                    <td className="py-4">{role.permissions_count}</td>
                    <td className="py-4 text-right">
                      <div className="flex justify-end gap-2">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleEdit(role)}
                        >
                          Edit
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleToggleStatus(role)}
                        >
                          {role.status_label === 'active' ? 'Deactivate' : 'Activate'}
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleDelete(role)}
                        >
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

        <Card className="space-y-6 border border-mono-gray-200 p-6">
          <div className="space-y-2">
            <h2 className="text-xl font-semibold text-mono-black">
              {selectedRole ? `Edit Role` : 'Create Role'}
            </h2>
            <p className="text-sm text-mono-gray-600">
              {selectedRole
                ? `Updating “${selectedRole.name}”.`
                : 'Define a new access role with curated permissions.'}
            </p>
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

              <div className="flex items-center gap-3">
                <Button
                  type="submit"
                  variant="primary"
                  disabled={createOrUpdateRole.isPending}
                >
                  {selectedRole ? 'Update Role' : 'Create Role'}
                </Button>
                {selectedRole && (
                  <Button variant="ghost" type="button" onClick={handleResetForm}>
                    Cancel
                  </Button>
                )}
              </div>
            </form>
          )}
        </Card>
      </div>
    </div>
  );
};

export default RolesManagement;
