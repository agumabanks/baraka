import React, { useEffect, useMemo, useState } from 'react';
import Button from '../ui/Button';
import type { DriverFormPayload } from '../../types/drivers';
import {
  DRIVER_STATUS_LABELS,
  EMPLOYMENT_STATUS_LABELS,
  type DriverStatus,
  type EmploymentStatus,
} from '../../types/drivers';

interface BranchOption {
  id: number | string;
  name: string;
  code: string;
}

interface DriverFormModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (payload: DriverFormPayload) => void;
  isSubmitting?: boolean;
  branches?: BranchOption[];
  initialData?: Partial<DriverFormPayload> & { id?: number; code?: string };
}

type DriverFormState = Omit<DriverFormPayload, 'branch_id'> & {
  branch_id: number | '';
  license_expiry?: string | null;
  password?: string;
  code?: string;
};

const statusOptions = Object.entries(DRIVER_STATUS_LABELS).map(([value, label]) => ({
  value: value as DriverStatus,
  label,
}));

const employmentOptions = Object.entries(EMPLOYMENT_STATUS_LABELS).map(([value, label]) => ({
  value: value as EmploymentStatus,
  label,
}));

const emptyState: DriverFormState = {
  branch_id: '',
  name: '',
  email: '',
  phone: '',
  status: 'ACTIVE',
  employment_status: 'ACTIVE',
  license_number: '',
  license_expiry: '',
  vehicle_id: null,
  documents: null,
  metadata: null,
  code: '',
  password: '',
};

const resolveDateInput = (value?: string | null) => {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '';
  }
  return date.toISOString().split('T')[0];
};

const DriverFormModal: React.FC<DriverFormModalProps> = ({
  isOpen,
  onClose,
  onSubmit,
  isSubmitting = false,
  branches = [],
  initialData,
}) => {
  const [formState, setFormState] = useState<DriverFormState>(emptyState);

  useEffect(() => {
    if (isOpen) {
      setFormState((prev) => ({ ...prev, ...emptyState }));

      if (initialData) {
        setFormState({
          branch_id: initialData.branch_id ?? '',
          name: initialData.name ?? '',
          email: initialData.email ?? '',
          phone: initialData.phone ?? '',
          status: initialData.status ?? 'ACTIVE',
          employment_status: initialData.employment_status ?? 'ACTIVE',
          license_number: initialData.license_number ?? '',
          license_expiry: resolveDateInput(initialData.license_expiry),
          vehicle_id: initialData.vehicle_id ?? null,
          documents: initialData.documents ?? null,
          metadata: initialData.metadata ?? null,
          code: initialData.code ?? '',
          password: '',
        });
      } else {
        setFormState(emptyState);
      }
    }
  }, [initialData, isOpen]);

  const sortedBranches = useMemo(() => {
    return [...branches].sort((a, b) => a.name.localeCompare(b.name));
  }, [branches]);

  if (!isOpen) {
    return null;
  }

  const handleChange = (field: keyof DriverFormState) => (event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const value = event.target.value;
    let resolved: DriverFormState[keyof DriverFormState];

    if (field === 'branch_id') {
      resolved = value ? Number(value) : '';
    } else if (field === 'status') {
      resolved = value as DriverStatus;
    } else if (field === 'employment_status') {
      resolved = value as EmploymentStatus;
    } else if (field === 'vehicle_id') {
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

    if (!formState.branch_id) {
      alert('Select a branch for the driver.');
      return;
    }

    const payload: DriverFormPayload = {
      branch_id: formState.branch_id,
      name: formState.name.trim(),
      email: formState.email?.trim() || undefined,
      phone: formState.phone?.trim() || undefined,
      status: formState.status,
      employment_status: formState.employment_status,
      license_number: formState.license_number?.trim() || undefined,
      license_expiry: formState.license_expiry || undefined,
      vehicle_id: formState.vehicle_id ?? undefined,
      documents: formState.documents ?? undefined,
      metadata: formState.metadata ?? undefined,
      code: formState.code?.trim() || undefined,
      password: formState.password?.trim() || undefined,
    };

    onSubmit(payload);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
      <div className="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl bg-white shadow-2xl">
        <header className="sticky top-0 flex items-center justify-between border-b border-mono-gray-200 bg-white p-6">
          <div>
            <h2 className="text-2xl font-semibold text-mono-black">{initialData ? 'Update Driver' : 'Add Driver'}</h2>
            <p className="text-sm text-mono-gray-600">Provide workforce details to onboard a field driver.</p>
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
              <span className="font-medium text-mono-black">Full Name</span>
              <input
                type="text"
                required
                value={formState.name}
                onChange={handleChange('name')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
                placeholder="Jane Doe"
              />
            </label>

            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Branch <span className="text-red-500">*</span></span>
              <select
                required
                value={formState.branch_id}
                onChange={handleChange('branch_id')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
              >
                <option value="">Select branch</option>
                {sortedBranches.map((branch) => (
                  <option key={branch.id} value={branch.id}>
                    {branch.name} ({branch.code})
                  </option>
                ))}
              </select>
            </label>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Email</span>
              <input
                type="email"
                value={formState.email ?? ''}
                onChange={handleChange('email')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
                placeholder="driver@example.com"
              />
            </label>

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
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Driver Status</span>
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
              <span className="font-medium text-mono-black">Employment Status</span>
              <select
                value={formState.employment_status}
                onChange={handleChange('employment_status')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
              >
                {employmentOptions.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </label>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">License Number</span>
              <input
                type="text"
                value={formState.license_number ?? ''}
                onChange={handleChange('license_number')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
                placeholder="DL-12345"
              />
            </label>

            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">License Expiry</span>
              <input
                type="date"
                value={formState.license_expiry ?? ''}
                onChange={handleChange('license_expiry')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
              />
            </label>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Driver Code</span>
              <input
                type="text"
                value={formState.code ?? ''}
                onChange={handleChange('code')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
                placeholder="DRV-001"
              />
            </label>

            {!initialData && (
              <label className="space-y-2 text-sm text-mono-gray-600">
                <span className="font-medium text-mono-black">Temporary Password</span>
                <input
                  type="text"
                  value={formState.password ?? ''}
                  onChange={handleChange('password')}
                  className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
                  placeholder="Optional password override"
                />
              </label>
            )}
          </div>

          <footer className="flex justify-end gap-3 border-t border-mono-gray-200 pt-4">
            <Button type="button" variant="secondary" onClick={onClose} disabled={isSubmitting}>
              Cancel
            </Button>
            <Button type="submit" variant="primary" disabled={isSubmitting}>
              {isSubmitting ? 'Saving…' : initialData ? 'Save Changes' : 'Create Driver'}
            </Button>
          </footer>
        </form>
      </div>
    </div>
  );
};

export default DriverFormModal;
