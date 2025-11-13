import React, { useCallback, useEffect, useMemo, useState } from 'react';
// @ts-nocheck
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
import type { AdminUser, AdminUserCollection, AdminUserFilters, AdminUserPayload, AdminUsersBulkAssignPayload } from '../../types/settings';

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
  preferred_language: string;
  primary_branch_id: string;
  image: File | null;
};

type UserFormErrors = Partial<Record<keyof UserFormState, string>>;

type PanelMode = 'form' | 'details' | null;

type FilterChip = {
  field: keyof AdminUserFilters;
  label: string;
};

type BulkAssignmentState = {
  role_id?: string;
  hub_id?: string;
  department_id?: string;
  designation_id?: string;
  status?: string;
};

type TeamOption = {
  value: string;
  label: string;
  departmentId?: number;
  hubId?: number;
};

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
  preferred_language: 'en',
  primary_branch_id: '',
  image: null,
};

const initialFilters: AdminUserFilters = {
  page: 1,
  per_page: 10,
};

const EMPTY_USERS: AdminUser[] = [];
const LANGUAGE_OPTIONS = [
  { value: 'en', label: 'English' },
  { value: 'fr', label: 'Français' },
  { value: 'sw', label: 'Kiswahili' },
];

const UsersManagement: React.FC = () => {
  const queryClient = useQueryClient();
  const [filters, setFilters] = useState<AdminUserFilters>(initialFilters);
  const [formState, setFormState] = useState<UserFormState>(defaultUserForm);
  const [formErrors, setFormErrors] = useState<UserFormErrors>({});
  const [selectedUser, setSelectedUser] = useState<AdminUser | null>(null);
  const [panelMode, setPanelMode] = useState<PanelMode>(null);
  const [detailUser, setDetailUser] = useState<AdminUser | null>(null);
  const [isDetailLoading, setIsDetailLoading] = useState(false);
  const [feedback, setFeedback] = useState<string | null>(null);
  const [selectedUserIds, setSelectedUserIds] = useState<number[]>([]);
  const [bulkAssignments, setBulkAssignments] = useState<BulkAssignmentState>({});
  const [bulkFeedback, setBulkFeedback] = useState<string | null>(null);

  const { data: metaResponse } = useQuery({
    queryKey: ['admin-users', 'meta'],
    queryFn: async () => {
      const response = await adminUsersApi.getMeta();
      return response.data;
    },
    staleTime: 1000 * 60 * 10,
  });

  const defaultSelections = useMemo(() => ({
    status: metaResponse?.statuses?.length ? String(metaResponse.statuses[0].value) : '',
    role_id: metaResponse?.roles?.length ? String(metaResponse.roles[0].id) : '',
    department_id: metaResponse?.departments?.length ? String(metaResponse.departments[0].id) : '',
    designation_id: metaResponse?.designations?.length ? String(metaResponse.designations[0].id) : '',
  }), [metaResponse]);

  const { data: usersResponse, isLoading, isError, error } = useQuery<AdminUserCollection & { success?: boolean; message?: string }, Error>({
    queryKey: ['admin-users', filters],
    queryFn: () => adminUsersApi.getUsers(filters),
    placeholderData: (previous) => previous,
  });

  const users = usersResponse?.data ?? EMPTY_USERS;
  const pagination = usersResponse?.meta;
  const summary = useMemo(() => {
    const total = pagination?.total ?? users.length;
    const activeOnPage = users.filter((user: AdminUser) => user.status_label === 'active').length;
    const inactiveOnPage = users.filter((user: AdminUser) => user.status_label !== 'active').length;
    const onboardedOnPage = users.filter((user: AdminUser) => Boolean(user.joining_date)).length;
    return { total, activeOnPage, inactiveOnPage, onboardedOnPage };
  }, [users, pagination]);
  const metaTotals = metaResponse?.totals;
  const teamSummary = metaResponse?.team_summary ?? [];
  const recentHires = metaResponse?.people_pulse?.recent_hires ?? [];

  const teamOptions = useMemo<TeamOption[]>(() => {
    if (!metaResponse?.team_summary) {
      return [];
    }

    return metaResponse.team_summary.map((team) => ({
      value: team.id,
      label: `${team.label} (${team.total})`,
      departmentId: team.department?.id,
      hubId: team.hub?.id,
    }));
  }, [metaResponse]);

  const selectedTeamOption = useMemo(() => {
    if (!teamOptions.length) {
      return '';
    }

    const match = teamOptions.find((option) => {
      const departmentId = option.departmentId ?? undefined;
      const hubId = option.hubId ?? undefined;
      return departmentId === (filters.department_id ?? undefined)
        && hubId === (filters.hub_id ?? undefined);
    });

    return match?.value ?? '';
  }, [teamOptions, filters.department_id, filters.hub_id]);

  const selectionCount = selectedUserIds.length;
  const isAllSelected = users.length > 0 && users.every((user) => selectedUserIds.includes(user.id));
  const hasBulkAssignments = Object.values(bulkAssignments).some((value) => value !== undefined && value !== '');
  const totalHeadcount = metaTotals?.total ?? summary.total;
  const activeHeadcount = metaTotals?.active ?? summary.activeOnPage;
  const inactiveHeadcount = metaTotals?.inactive ?? summary.inactiveOnPage;
  const activeRatioDisplay = metaTotals
    ? `${metaTotals.active_ratio}%`
    : summary.total
      ? `${Math.round((summary.activeOnPage / summary.total) * 100)}%`
      : '0%';
  const recentHireCount = metaTotals?.recent_hires ?? summary.onboardedOnPage;
  const teamCount = teamSummary.length;
  const topTeamLabel = teamSummary.length ? teamSummary[0].label : 'No assigned teams';
  const highlightRecentHire = recentHires.length ? recentHires[0].name : null;

  useEffect(() => {
    if (!defaultSelections.status && !defaultSelections.role_id && !defaultSelections.department_id && !defaultSelections.designation_id) {
      return;
    }

    setFormState((prev) => ({
      ...prev,
      status: prev.status || defaultSelections.status,
      role_id: prev.role_id || defaultSelections.role_id,
      department_id: prev.department_id || defaultSelections.department_id,
      designation_id: prev.designation_id || defaultSelections.designation_id,
    }));
  }, [defaultSelections]);

  const createOrUpdateUser = useMutation({
    mutationFn: async (payload: AdminUserPayload) => {
      if (selectedUser) {
        return adminUsersApi.updateUser(selectedUser.id, payload);
      }
      return adminUsersApi.createUser(payload);
    },
    onSuccess: (response) => {
      setFeedback(response.message ?? 'User saved successfully.');
      resetForm({ preserveFeedback: true });
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
    onSuccess: (_, userId) => {
      queryClient.invalidateQueries({ queryKey: ['admin-users'] });
      if (selectedUser?.id === userId) {
        setSelectedUser(null);
        setPanelMode(null);
      }
      if (detailUser?.id === userId) {
        setDetailUser(null);
        setPanelMode(null);
      }
    },
  });

  const bulkAssignMutation = useMutation({
    mutationFn: (payload: AdminUsersBulkAssignPayload) => adminUsersApi.bulkAssign(payload),
    onSuccess: (response) => {
      setBulkFeedback(response.message ?? 'Assignments updated successfully.');
      setBulkAssignments({});
      setSelectedUserIds([]);
      queryClient.invalidateQueries({ queryKey: ['admin-users'] });
      queryClient.invalidateQueries({ queryKey: ['admin-users', 'meta'] });
    },
    onError: (axiosError: AxiosError<{ message?: string }>) => {
      setBulkFeedback(axiosError.response?.data?.message ?? 'Unable to update assignments.');
    },
  });

  const filterChips = useMemo<FilterChip[]>(() => {
    const chips: FilterChip[] = [];

    if (filters.search) {
      chips.push({ field: 'search', label: `Search: “${filters.search}”` });
    }
    if (filters.role_id) {
      const roleLabel = metaResponse?.roles?.find((role) => role.id === filters.role_id)?.name ?? `Role #${filters.role_id}`;
      chips.push({ field: 'role_id', label: `Role: ${roleLabel}` });
    }
    if (filters.status) {
      const statusLabel = metaResponse?.statuses?.find((status) => status.value === filters.status)?.label ?? `Status #${filters.status}`;
      chips.push({ field: 'status', label: `Status: ${statusLabel}` });
    }
    if (filters.hub_id) {
      const hubLabel = metaResponse?.hubs?.find((hub) => hub.id === filters.hub_id)?.name ?? `Hub #${filters.hub_id}`;
      chips.push({ field: 'hub_id', label: `Hub: ${hubLabel}` });
    }
    if (filters.department_id) {
      const departmentLabel = metaResponse?.departments?.find((department) => department.id === filters.department_id)?.title ?? `Department #${filters.department_id}`;
      chips.push({ field: 'department_id', label: `Department: ${departmentLabel}` });
    }
    if (filters.designation_id) {
      const designationLabel = metaResponse?.designations?.find((designation) => designation.id === filters.designation_id)?.title ?? `Designation #${filters.designation_id}`;
      chips.push({ field: 'designation_id', label: `Designation: ${designationLabel}` });
    }

    return chips;
  }, [filters, metaResponse]);

  const isFiltersDirty = useMemo(() => filterChips.length > 0, [filterChips]);

  const canResetFilters = useMemo(() => {
    const perPageChanged = (filters.per_page ?? initialFilters.per_page) !== initialFilters.per_page;
    const pageChanged = (filters.page ?? initialFilters.page) !== initialFilters.page;
    return isFiltersDirty || perPageChanged || pageChanged;
  }, [filters, isFiltersDirty]);

  const handleFilterChange = (field: keyof AdminUserFilters, value: string) => {
    setFilters((prev) => ({
      ...prev,
      [field]: value ? Number(value) : undefined,
      page: 1,
    }));
  };

  const handleTeamFilterChange = (value: string) => {
    if (!value) {
      setFilters((prev) => ({
        ...prev,
        department_id: undefined,
        hub_id: undefined,
        page: 1,
      }));
      return;
    }

    const option = teamOptions.find((team) => team.value === value);
    setFilters((prev) => ({
      ...prev,
      department_id: option?.departmentId,
      hub_id: option?.hubId,
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

  const handleRemoveFilter = useCallback((field: keyof AdminUserFilters) => {
    setFilters((prev) => {
      const next: AdminUserFilters = {
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

  const toggleUserSelection = (userId: number) => {
    setSelectedUserIds((previous) =>
      previous.includes(userId)
        ? previous.filter((id) => id !== userId)
        : [...previous, userId]
    );
    setBulkFeedback(null);
  };

  const toggleSelectAll = () => {
    if (isAllSelected) {
      setSelectedUserIds([]);
    } else {
      setSelectedUserIds(users.map((user) => user.id));
    }
    setBulkFeedback(null);
  };

  const clearSelection = () => {
    setSelectedUserIds([]);
    setBulkFeedback(null);
  };

  const handleBulkAssignmentChange = (field: keyof BulkAssignmentState, value: string) => {
    setBulkAssignments((prev) => ({
      ...prev,
      [field]: value,
    }));
    setBulkFeedback(null);
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

  const formatDate = useCallback((value: string | null | undefined) => {
    if (!value) {
      return '—';
    }

    try {
      return new Intl.DateTimeFormat(undefined, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
      }).format(new Date(value));
    } catch (error) {
      console.error('Failed to format date', error);
      return value;
    }
  }, []);

  const formatCurrency = useCallback((value: number | null | undefined) => {
    if (value == null) {
      return '—';
    }

    try {
      return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'UGX',
        maximumFractionDigits: 0,
      }).format(value);
    } catch (error) {
      console.error('Failed to format currency', error);
      return String(value);
    }
  }, []);

  const handleBulkApply = () => {
    if (selectionCount === 0) {
      setBulkFeedback('Select at least one team member.');
      return;
    }

    if (!hasBulkAssignments) {
      setBulkFeedback('Choose at least one field to update.');
      return;
    }

    const parseField = (value?: string): number | null | undefined => {
      if (value === undefined || value === '') {
        return undefined;
      }

      if (value === '__null__') {
        return null;
      }

      const parsed = Number(value);

      return Number.isNaN(parsed) ? undefined : parsed;
    };

    const payload: AdminUsersBulkAssignPayload = {
      user_ids: selectedUserIds,
    };

    const roleValue = parseField(bulkAssignments.role_id);
    if (roleValue !== undefined) {
      payload.role_id = roleValue;
    }

    const hubValue = parseField(bulkAssignments.hub_id);
    if (hubValue !== undefined) {
      payload.hub_id = hubValue;
    }

    const departmentValue = parseField(bulkAssignments.department_id);
    if (departmentValue !== undefined) {
      payload.department_id = departmentValue;
    }

    const designationValue = parseField(bulkAssignments.designation_id);
    if (designationValue !== undefined) {
      payload.designation_id = designationValue;
    }

    const statusValue = parseField(bulkAssignments.status);
    if (statusValue !== undefined) {
      payload.status = statusValue;
    }

    setBulkFeedback(null);
    bulkAssignMutation.mutate(payload);
  };

  const handleBulkReset = () => {
    setBulkAssignments({});
    setBulkFeedback(null);
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
      preferred_language: user.preferred_language ?? 'en',
      primary_branch_id: user.primary_branch_id ? String(user.primary_branch_id) : '',
      image: null,
    });
  };

  const handleEditUser = async (user: AdminUser) => {
    try {
      const response = await adminUsersApi.getUser(user.id);
      setSelectedUser(response.data);
      populateFormFromUser(response.data);
      setPanelMode('form');
      setDetailUser(null);
      setFeedback(null);
      setFormErrors({});
    } catch (fetchError) {
      console.error(fetchError);
      alert('Unable to load user details.');
    }
  };

  const handleViewUser = async (user: AdminUser) => {
    setPanelMode('details');
    setDetailUser(null);
    setIsDetailLoading(true);
    setFeedback(null);

    try {
      const response = await adminUsersApi.getUser(user.id);
      setDetailUser(response.data);
    } catch (fetchError) {
      console.error(fetchError);
      alert('Unable to load user profile.');
      setPanelMode(null);
    } finally {
      setIsDetailLoading(false);
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
      preferred_language: (formState.preferred_language || 'en') as 'en' | 'fr' | 'sw',
      primary_branch_id: formState.primary_branch_id ? Number(formState.primary_branch_id) : undefined,
      image: formState.image,
    };

    createOrUpdateUser.mutate(payload);
  };

  const resetForm = useCallback((options?: { keepPanel?: boolean; preserveFeedback?: boolean }) => {
    setSelectedUser(null);
    setFormErrors({});
    setDetailUser(null);
    if (!options?.preserveFeedback) {
      setFeedback(null);
    }
    if (!options?.keepPanel) {
      setPanelMode(null);
    }
    setFormState(() => ({
      ...defaultUserForm,
      status: defaultSelections.status || '',
      role_id: defaultSelections.role_id || '',
      department_id: defaultSelections.department_id || '',
      designation_id: defaultSelections.designation_id || '',
    }));
  }, [defaultSelections]);

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

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Card className="p-5">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Headcount</p>
          <p className="mt-2 text-3xl font-semibold text-mono-black">{totalHeadcount}</p>
          <p className="text-xs text-mono-gray-500">{activeHeadcount} active • {inactiveHeadcount} inactive</p>
        </Card>
        <Card className="p-5">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Active Coverage</p>
          <p className="mt-2 text-3xl font-semibold text-mono-black">{activeRatioDisplay}</p>
          <p className="text-xs text-mono-gray-500">Status health across all admins</p>
        </Card>
        <Card className="p-5">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Teams Engaged</p>
          <p className="mt-2 text-3xl font-semibold text-mono-black">{teamCount}</p>
          <p className="text-xs text-mono-gray-500">{teamCount > 0 ? `Lead: ${topTeamLabel}` : 'Assign departments & hubs'}</p>
        </Card>
        <Card className="p-5">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Recent Hires (30d)</p>
          <p className="mt-2 text-3xl font-semibold text-mono-black">{recentHireCount}</p>
          <p className="text-xs text-mono-gray-500">
            {highlightRecentHire ? `Latest: ${highlightRecentHire}` : 'No onboarding in window'}
          </p>
        </Card>
      </div>

      <div className="grid gap-8 lg:grid-cols-[2fr_1fr]">
        <Card className="space-y-6 border border-mono-gray-200 p-8">
          <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div className="flex flex-wrap items-center gap-3">
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
              <div className="min-w-[180px]">
                <Select
                  value={filters.hub_id ? String(filters.hub_id) : ''}
                  onChange={(event) => handleFilterChange('hub_id', event.target.value)}
                  options={[
                    { value: '', label: 'All hubs' },
                    ...(metaResponse?.hubs ?? []).map((hub) => ({
                      value: String(hub.id),
                      label: hub.name,
                    })),
                  ]}
                />
              </div>
              <div className="min-w-[220px]">
                <Select
                  value={selectedTeamOption}
                  onChange={(event) => handleTeamFilterChange(event.target.value)}
                  options={[
                    { value: '', label: 'All squads' },
                    ...teamOptions.map((team) => ({
                      value: team.value,
                      label: team.label,
                    })),
                  ]}
                />
              </div>
              <div className="min-w-[180px]">
                <Select
                  value={filters.department_id ? String(filters.department_id) : ''}
                  onChange={(event) => handleFilterChange('department_id', event.target.value)}
                  options={[
                    { value: '', label: 'All departments' },
                    ...(metaResponse?.departments ?? []).map((department) => ({
                      value: String(department.id),
                      label: department.title,
                    })),
                  ]}
                />
              </div>
              <div className="min-w-[180px]">
                <Select
                  value={filters.designation_id ? String(filters.designation_id) : ''}
                  onChange={(event) => handleFilterChange('designation_id', event.target.value)}
                  options={[
                    { value: '', label: 'All designations' },
                    ...(metaResponse?.designations ?? []).map((designation) => ({
                      value: String(designation.id),
                      label: designation.title,
                    })),
                  ]}
                />
              </div>
            </div>

            <div className="flex flex-wrap items-center gap-3">
              <div className="min-w-[160px]">
                <Select
                  value={String(filters.per_page ?? initialFilters.per_page)}
                  onChange={(event) => handleFilterChange('per_page', event.target.value)}
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
                  queryClient.invalidateQueries({ queryKey: ['admin-users'] });
                }}
              >
                Reset Filters
              </Button>
              <Button
                variant="primary"
                size="sm"
                onClick={() => {
                  resetForm({ keepPanel: true });
                  setPanelMode('form');
                }}
              >
                <i className="fas fa-plus mr-2" aria-hidden="true" />
                Add Team Member
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
                  <th className="px-6 py-3">Member</th>
                  <th className="px-6 py-3">Role</th>
                  <th className="px-6 py-3">Hub</th>
                  <th className="px-6 py-3">Department</th>
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
                          {user.mobile && (
                            <p className="text-xs text-mono-gray-400">{user.mobile}</p>
                          )}
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4">{user.role?.name ?? '—'}</td>
                    <td className="px-6 py-4">{user.hub?.name ?? '—'}</td>
                    <td className="px-6 py-4">{user.department?.title ?? '—'}</td>
                    <td className="px-6 py-4">
                      <Badge variant={user.status_label === 'active' ? 'solid' : 'outline'} size="sm">
                        {user.status_label === 'active' ? 'Active' : 'Inactive'}
                      </Badge>
                    </td>
                    <td className="px-6 py-4">{formatDate(user.joining_date)}</td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex justify-end gap-2">
                        <Button variant="ghost" size="sm" onClick={() => handleViewUser(user)}>
                          View
                        </Button>
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
        </Card>

        <div className="space-y-4">
          {panelMode === null && (
            <Card className="flex h-full flex-col justify-center gap-3 border border-dashed border-mono-gray-300 p-6 text-sm text-mono-gray-600">
              <h2 className="text-lg font-semibold text-mono-black">Team member actions</h2>
              <p>
                Select a colleague from the table to preview their access footprint, or start a new invitation to grow the team.
              </p>
              <div className="flex flex-wrap gap-2">
                <Button
                  variant="primary"
                  size="sm"
                  onClick={() => {
                    resetForm({ keepPanel: true });
                    setPanelMode('form');
                  }}
                >
                  Invite teammate
                </Button>
                <Button
                  variant="secondary"
                  size="sm"
                  disabled={users.length === 0}
                  onClick={() => {
                    if (users[0]) {
                      handleViewUser(users[0]);
                    }
                  }}
                >
                  Preview first result
                </Button>
              </div>
            </Card>
          )}

          {panelMode === 'form' && (
            <Card className="space-y-6 border border-mono-gray-200 p-6">
              <div className="flex items-start justify-between gap-4">
                <div className="space-y-1">
                  <h2 className="text-xl font-semibold text-mono-black">
                    {selectedUser ? `Edit ${selectedUser.name}` : 'Invite Team Member'}
                  </h2>
                  <p className="text-sm text-mono-gray-600">
                    {selectedUser
                      ? 'Refresh personal details, reassess access, and keep audit trails tight.'
                      : 'Create a secure account, assign organisational context, and capture onboarding essentials.'}
                  </p>
                </div>
                <Button variant="ghost" size="sm" type="button" onClick={() => resetForm()}>
                  Close
                </Button>
              </div>

              <form className="space-y-6" onSubmit={handleSubmit}>
                <section className="space-y-4">
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-mono-gray-500">
                    Profile
                  </p>
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
                      placeholder="+256 700 000000"
                      error={formErrors.mobile}
                    />
                    <Select
                      label="Preferred Language"
                      value={formState.preferred_language}
                      onChange={(event) => handleFormChange('preferred_language', event.target.value)}
                      options={LANGUAGE_OPTIONS}
                      error={formErrors.preferred_language}
                    />
                    <Input
                      label="National ID"
                      value={formState.nid_number}
                      onChange={(event) => handleFormChange('nid_number', event.target.value)}
                      error={formErrors.nid_number}
                    />
                  </div>
                </section>

                <section className="space-y-4">
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-mono-gray-500">
                    Access & employment
                  </p>
                  <div className="grid gap-4 md:grid-cols-2">
                    <Input
                      label="Password"
                      type="password"
                      value={formState.password}
                      onChange={(event) => handleFormChange('password', event.target.value)}
                      placeholder={selectedUser ? 'Leave blank to keep existing password' : 'Set an initial password'}
                      error={formErrors.password}
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
                      label="Role"
                      value={formState.role_id}
                      onChange={(event) => handleFormChange('role_id', event.target.value)}
                      options={(metaResponse?.roles ?? []).map((role) => ({
                        value: String(role.id),
                        label: role.name,
                      }))}
                      error={formErrors.role_id}
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
                      min="0"
                      value={formState.salary}
                      onChange={(event) => handleFormChange('salary', event.target.value)}
                      placeholder="0"
                      error={formErrors.salary}
                    />
                  </div>
                </section>

                <section className="space-y-4">
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-mono-gray-500">
                    Organisation context
                  </p>
                  <div className="grid gap-4 md:grid-cols-2">
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
                      label="Primary Branch"
                      value={formState.primary_branch_id}
                      onChange={(event) => handleFormChange('primary_branch_id', event.target.value)}
                      options={[
                        { value: '', label: 'Unassigned' },
                        ...(metaResponse?.branches ?? []).map((branch) => ({
                          value: String(branch.id),
                          label: branch.code ? `${branch.name} (${branch.code})` : branch.name,
                        })),
                      ]}
                      error={formErrors.primary_branch_id}
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
                </section>

                <section className="space-y-4">
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-mono-gray-500">
                    Additional details
                  </p>
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
                </section>

                <div className="flex flex-wrap items-center gap-3 border-t border-mono-gray-200 pt-4">
                  <Button
                    type="submit"
                    variant="primary"
                    loading={createOrUpdateUser.isPending}
                    disabled={createOrUpdateUser.isPending}
                  >
                    {selectedUser ? 'Update User' : 'Create User'}
                  </Button>
                  <Button variant="ghost" type="button" onClick={() => resetForm()}>
                    Cancel
                  </Button>
                </div>
              </form>
            </Card>
          )}

          {panelMode === 'details' && (
            <Card className="space-y-6 border border-mono-gray-200 p-6">
              <div className="flex items-start justify-between gap-4">
                <div className="space-y-1">
                  <h2 className="text-xl font-semibold text-mono-black">Team member profile</h2>
                  <p className="text-sm text-mono-gray-600">
                    Review access context, onboarding metadata, and permissions at a glance.
                  </p>
                </div>
                <Button variant="ghost" size="sm" type="button" onClick={() => setPanelMode(null)}>
                  Close
                </Button>
              </div>

              {isDetailLoading ? (
                <LoadingSpinner message="Loading profile" />
              ) : detailUser ? (
                <div className="space-y-6 text-sm">
                  <div className="flex items-center gap-3">
                    <Avatar
                      src={detailUser.avatar ?? undefined}
                      fallback={detailUser.name ? detailUser.name[0] : '?'}
                      size="lg"
                    />
                    <div>
                      <p className="text-lg font-semibold text-mono-black">{detailUser.name}</p>
                      <p className="text-sm text-mono-gray-600">{detailUser.email}</p>
                      {detailUser.mobile && (
                        <p className="text-xs text-mono-gray-500">{detailUser.mobile}</p>
                      )}
                    </div>
                  </div>

                  <div className="flex flex-wrap gap-2">
                    <Badge variant={detailUser.status_label === 'active' ? 'solid' : 'outline'} size="sm">
                      {detailUser.status_label === 'active' ? 'Active' : 'Inactive'}
                    </Badge>
                    {detailUser.role?.name && (
                      <Badge variant="outline" size="sm">
                        Role: {detailUser.role.name}
                      </Badge>
                    )}
                  </div>

                  <div className="space-y-4 rounded-xl border border-mono-gray-100 bg-mono-gray-50 p-4">
                    <div className="grid gap-3 sm:grid-cols-2">
                      <div>
                        <p className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">Hub</p>
                        <p className="mt-1 font-medium text-mono-black">{detailUser.hub?.name ?? '—'}</p>
                      </div>
                      <div>
                        <p className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">Department</p>
                        <p className="mt-1 font-medium text-mono-black">{detailUser.department?.title ?? '—'}</p>
                      </div>
                      <div>
                        <p className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">Designation</p>
                        <p className="mt-1 font-medium text-mono-black">{detailUser.designation?.title ?? '—'}</p>
                      </div>
                      <div>
                        <p className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">National ID</p>
                        <p className="mt-1 font-medium text-mono-black">{detailUser.nid_number ?? '—'}</p>
                      </div>
                      <div>
                        <p className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">Joined</p>
                        <p className="mt-1 font-medium text-mono-black">{formatDate(detailUser.joining_date)}</p>
                      </div>
                      <div>
                        <p className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">Salary</p>
                        <p className="mt-1 font-medium text-mono-black">{formatCurrency(detailUser.salary)}</p>
                      </div>
                    </div>
                    <div>
                      <p className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">Address</p>
                      <p className="mt-1 text-mono-gray-700">{detailUser.address ?? '—'}</p>
                    </div>
                  </div>

                  <div className="space-y-2">
                    <p className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">Direct permissions</p>
                    {detailUser.permissions?.length ? (
                      <div className="flex flex-wrap gap-2">
                        {detailUser.permissions.map((permission) => (
                          <span
                            key={permission}
                            className="inline-flex items-center rounded-full border border-mono-gray-200 bg-mono-white px-3 py-1 text-xs font-medium text-mono-gray-700"
                          >
                            {permission.replace(/_/g, ' ')}
                          </span>
                        ))}
                      </div>
                    ) : (
                      <p className="text-sm text-mono-gray-600">
                        Inherits permissions from the assigned role.
                      </p>
                    )}
                  </div>

                  <div className="flex flex-wrap gap-2">
                    <Button variant="primary" size="sm" onClick={() => handleEditUser(detailUser)}>
                      Edit Profile
                    </Button>
                    <Button variant="secondary" size="sm" onClick={() => setPanelMode(null)}>
                      Close
                    </Button>
                  </div>
                </div>
              ) : (
                <p className="text-sm text-mono-gray-600">
                  Select a team member from the table to preview their profile.
                </p>
              )}
            </Card>
          )}
        </div>
      </div>
    </div>
  );
};

export default UsersManagement;
