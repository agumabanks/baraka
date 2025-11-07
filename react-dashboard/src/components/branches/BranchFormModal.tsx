import React, { useEffect, useMemo, useState } from 'react';
import Button from '../ui/Button';
import type { BranchFormPayload } from '../../types/branches';
import {
  BRANCH_STATUS_LABELS,
  BRANCH_TYPE_LABELS,
  type BranchStatusValue,
  type BranchTypeValue,
} from '../../types/branches';

interface BranchOption {
  id: number | string;
  name: string;
  code: string;
}

interface BranchFormModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (payload: BranchFormPayload) => void;
  isSubmitting?: boolean;
  initialData?: Partial<BranchFormPayload> & { id?: number; parent_branch_id?: number | null };
  parentOptions?: BranchOption[];
}

type BranchFormState = BranchFormPayload & { parent_branch_id?: number | null };

const typeOptions = Object.entries(BRANCH_TYPE_LABELS).map(([value, label]) => ({
  value: value as BranchTypeValue,
  label,
}));

const statusOptions = Object.entries(BRANCH_STATUS_LABELS).map(([value, label]) => ({
  value: value as BranchStatusValue,
  label,
}));

const defaultState: BranchFormState = {
  name: '',
  code: '',
  type: 'DESTINATION_BRANCH',
  address: '',
  country: '',
  city: '',
  phone: '',
  email: '',
  time_zone: 'Africa/Nairobi',
  capacity_parcels_per_day: null,
  geo_lat: null,
  geo_lng: null,
  operating_hours: null,
  capabilities: null,
  metadata: null,
  status: 'ACTIVE',
  parent_branch_id: null,
};

const BranchFormModal: React.FC<BranchFormModalProps> = ({
  isOpen,
  onClose,
  onSubmit,
  isSubmitting = false,
  initialData,
  parentOptions = [],
}) => {
  const [formState, setFormState] = useState<BranchFormState>(defaultState);

  useEffect(() => {
    if (isOpen) {
      if (initialData) {
        setFormState({
          ...defaultState,
          ...initialData,
          parent_branch_id: initialData.parent_branch_id ?? null,
          capacity_parcels_per_day: initialData.capacity_parcels_per_day ?? null,
          geo_lat: initialData.geo_lat ?? null,
          geo_lng: initialData.geo_lng ?? null,
          status: initialData.status ?? 'ACTIVE',
        });
      } else {
        setFormState(defaultState);
      }
    }
  }, [initialData, isOpen]);

  const sortedParentOptions = useMemo(
    () =>
      parentOptions
        .filter((option) => option.id !== (initialData?.id ?? null))
        .sort((a, b) => a.name.localeCompare(b.name)),
    [initialData?.id, parentOptions]
  );

  if (!isOpen) {
    return null;
  }

  const handleChange = (field: keyof BranchFormState) => (event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const value = event.target.value;
    let resolved: BranchFormState[keyof BranchFormState];

    if (field === 'type') {
      resolved = value as BranchTypeValue;
    } else if (field === 'status') {
      resolved = value as BranchStatusValue;
    } else if (field === 'capacity_parcels_per_day') {
      resolved = value ? Number(value) : null;
    } else if (field === 'geo_lat' || field === 'geo_lng') {
      resolved = value ? Number(value) : null;
    } else if (field === 'parent_branch_id') {
      resolved = value ? Number(value) : null;
    } else {
      resolved = value;
    }

    setFormState((prev) => ({
      ...prev,
      [field]: resolved,
    }));
  };

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault();

    if (!formState.name.trim() || !formState.code.trim() || !formState.country.trim() || !formState.time_zone.trim()) {
      alert('Name, code, country, and time zone are required.');
      return;
    }

    const payload: BranchFormPayload = {
      name: formState.name.trim(),
      code: formState.code.trim(),
      type: formState.type,
      parent_branch_id: formState.parent_branch_id ?? undefined,
      address: formState.address?.trim() || undefined,
      country: formState.country.trim(),
      city: formState.city?.trim() || undefined,
      phone: formState.phone?.trim() || undefined,
      email: formState.email?.trim() || undefined,
      time_zone: formState.time_zone.trim(),
      capacity_parcels_per_day: typeof formState.capacity_parcels_per_day === 'number' ? formState.capacity_parcels_per_day : undefined,
      geo_lat: typeof formState.geo_lat === 'number' ? formState.geo_lat : undefined,
      geo_lng: typeof formState.geo_lng === 'number' ? formState.geo_lng : undefined,
      operating_hours: formState.operating_hours ?? undefined,
      capabilities: formState.capabilities ?? undefined,
      metadata: formState.metadata ?? undefined,
      status: formState.status,
    };

    onSubmit(payload);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
      <div className="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl bg-white shadow-2xl">
        <header className="sticky top-0 flex items-center justify-between border-b border-mono-gray-200 bg-white p-6">
          <div>
            <h2 className="text-2xl font-semibold text-mono-black">{initialData ? 'Update Branch' : 'Create Branch'}</h2>
            <p className="text-sm text-mono-gray-600">Configure hub details for network operations.</p>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="text-mono-gray-500 transition-colors hover:text-mono-black"
            aria-label="Close modal"
          >
            <i className="fas fa-times text-xl" aria-hidden="true" />
          </button>
        </header>

        <form onSubmit={handleSubmit} className="space-y-6 p-6">
          <div className="grid gap-4 md:grid-cols-2">
            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Branch Name</span>
              <input
                type="text"
                required
                value={formState.name}
                onChange={handleChange('name')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
                placeholder="Central Hub"
              />
            </label>
            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Branch Code</span>
              <input
                type="text"
                required
                value={formState.code}
                onChange={handleChange('code')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
                placeholder="HUB-001"
              />
            </label>
          </div>

          <label className="block space-y-2 text-sm text-mono-gray-600">
            <span className="font-medium text-mono-black">Street Address</span>
            <textarea
              value={formState.address ?? ''}
              onChange={handleChange('address')}
              rows={3}
              className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
              placeholder="Plot 10, Logistics Park"
            />
          </label>

          <div className="grid gap-4 md:grid-cols-3">
            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Type</span>
              <select
                value={formState.type}
                onChange={handleChange('type')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
              >
                {typeOptions.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </label>

            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Status</span>
              <select
                value={formState.status}
                onChange={handleChange('status')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
              >
                {statusOptions.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </label>

            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Parent Branch</span>
              <select
                value={formState.parent_branch_id ?? ''}
                onChange={handleChange('parent_branch_id')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
              >
                <option value="">No parent (top-level)</option>
                {sortedParentOptions.map((branch) => (
                  <option key={branch.id} value={branch.id}>
                    {branch.name} ({branch.code})
                  </option>
                ))}
              </select>
            </label>
          </div>

          <div className="grid gap-4 md:grid-cols-3">
            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Country</span>
              <input
                type="text"
                required
                value={formState.country}
                onChange={handleChange('country')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
                placeholder="Uganda"
              />
            </label>
            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">City</span>
              <input
                type="text"
                value={formState.city ?? ''}
                onChange={handleChange('city')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
                placeholder="Kampala"
              />
            </label>
            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Time Zone</span>
              <input
                type="text"
                required
                value={formState.time_zone}
                onChange={handleChange('time_zone')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
                placeholder="Africa/Nairobi"
              />
            </label>
          </div>

          <div className="grid gap-4 md:grid-cols-3">
            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Phone</span>
              <input
                type="tel"
                value={formState.phone ?? ''}
                onChange={handleChange('phone')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
                placeholder="+256700000000"
              />
            </label>
            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Email</span>
              <input
                type="email"
                value={formState.email ?? ''}
                onChange={handleChange('email')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
                placeholder="operations@example.com"
              />
            </label>
            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Capacity (parcels / day)</span>
              <input
                type="number"
                min={0}
                value={formState.capacity_parcels_per_day ?? ''}
                onChange={handleChange('capacity_parcels_per_day')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
                placeholder="1000"
              />
            </label>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Latitude</span>
              <input
                type="number"
                step="0.000001"
                value={formState.geo_lat ?? ''}
                onChange={handleChange('geo_lat')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
                placeholder="0.3476"
              />
            </label>
            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Longitude</span>
              <input
                type="number"
                step="0.000001"
                value={formState.geo_lng ?? ''}
                onChange={handleChange('geo_lng')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
                placeholder="32.5825"
              />
            </label>
          </div>

          <footer className="flex justify-end gap-3 border-t border-mono-gray-200 pt-4">
            <Button type="button" variant="secondary" onClick={onClose} disabled={isSubmitting}>
              Cancel
            </Button>
            <Button type="submit" variant="primary" disabled={isSubmitting}>
              {isSubmitting ? 'Saving…' : initialData ? 'Save Changes' : 'Create Branch'}
            </Button>
          </footer>
        </form>
      </div>
    </div>
  );
};

export default BranchFormModal;
