import React, { useEffect, useMemo, useState } from 'react';
import Button from '../ui/Button';
import type { DriverRosterFormPayload } from '../../types/drivers';
import { ROSTER_STATUS_LABELS, type RosterStatus } from '../../types/drivers';

interface BranchOption {
  id: number | string;
  name: string;
  code: string;
}

interface RosterFormModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (payload: DriverRosterFormPayload | Partial<DriverRosterFormPayload>) => void;
  driverId: number;
  branches?: BranchOption[];
  isSubmitting?: boolean;
  initialData?: Partial<DriverRosterFormPayload> & { id?: number };
}

type RosterFormState = Omit<DriverRosterFormPayload, 'branch_id'> & {
  branch_id: number | '';
  start_time: string;
  end_time: string;
};

const statusOptions = Object.entries(ROSTER_STATUS_LABELS).map(([value, label]) => ({
  value: value as RosterStatus,
  label,
}));

const formatDateTimeLocal = (value?: string) => {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '';
  }
  const pad = (num: number) => num.toString().padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

const toIso = (value: string) => {
  if (!value) return undefined;
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return undefined;
  }
  return date.toISOString();
};

const defaultState = (driverId: number): RosterFormState => ({
  driver_id: driverId,
  branch_id: '',
  shift_type: '',
  start_time: formatDateTimeLocal(new Date().toISOString()),
  end_time: formatDateTimeLocal(new Date(Date.now() + 4 * 60 * 60 * 1000).toISOString()),
  status: 'SCHEDULED',
  planned_hours: null,
  metadata: null,
});

const RosterFormModal: React.FC<RosterFormModalProps> = ({
  isOpen,
  onClose,
  onSubmit,
  driverId,
  branches = [],
  isSubmitting = false,
  initialData,
}) => {
  const [formState, setFormState] = useState<RosterFormState>(() => defaultState(driverId));

  useEffect(() => {
    if (isOpen) {
      if (initialData) {
        setFormState({
          driver_id: driverId,
          branch_id: initialData.branch_id ?? '',
          shift_type: initialData.shift_type ?? '',
          start_time: formatDateTimeLocal(initialData.start_time),
          end_time: formatDateTimeLocal(initialData.end_time),
          status: initialData.status ?? 'SCHEDULED',
          planned_hours: initialData.planned_hours ?? null,
          metadata: initialData.metadata ?? null,
        });
      } else {
        setFormState(defaultState(driverId));
      }
    }
  }, [driverId, initialData, isOpen]);

  const sortedBranches = useMemo(() => [...branches].sort((a, b) => a.name.localeCompare(b.name)), [branches]);

  if (!isOpen) {
    return null;
  }

  const handleChange = (field: keyof RosterFormState) => (event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const value = event.target.value;
    let resolved: RosterFormState[keyof RosterFormState];

    if (field === 'branch_id') {
      resolved = value ? Number(value) : '';
    } else if (field === 'status') {
      resolved = value as RosterStatus;
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

    if (!formState.start_time || !formState.end_time) {
      alert('Provide valid start and end times.');
      return;
    }

    const payload: DriverRosterFormPayload = {
      driver_id: driverId,
      branch_id: formState.branch_id ? Number(formState.branch_id) : null,
      shift_type: formState.shift_type || undefined,
      start_time: toIso(formState.start_time) ?? new Date(formState.start_time).toISOString(),
      end_time: toIso(formState.end_time) ?? new Date(formState.end_time).toISOString(),
      status: formState.status,
      planned_hours: formState.planned_hours ?? undefined,
      metadata: formState.metadata ?? undefined,
    };

    onSubmit(payload);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
      <div className="relative w-full max-w-2xl rounded-xl bg-white shadow-2xl">
        <header className="flex items-center justify-between border-b border-mono-gray-200 bg-white p-6">
          <div>
            <h2 className="text-2xl font-semibold text-mono-black">{initialData ? 'Update Shift' : 'Schedule Shift'}</h2>
            <p className="text-sm text-mono-gray-600">Plan roster coverage for this driver.</p>
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

        <form onSubmit={handleSubmit} className="space-y-5 p-6">
          <div className="grid gap-4 md:grid-cols-2">
            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Start Time</span>
              <input
                type="datetime-local"
                required
                value={formState.start_time}
                onChange={handleChange('start_time')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
              />
            </label>

            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">End Time</span>
              <input
                type="datetime-local"
                required
                value={formState.end_time}
                onChange={handleChange('end_time')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
              />
            </label>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <label className="space-y-2 text-sm text-mono-gray-600">
              <span className="font-medium text-mono-black">Branch</span>
              <select
                value={formState.branch_id}
                onChange={handleChange('branch_id')}
                className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
              >
                <option value="">Use driver default</option>
                {sortedBranches.map((branch) => (
                  <option key={branch.id} value={branch.id}>
                    {branch.name} ({branch.code})
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
          </div>

          <label className="space-y-2 text-sm text-mono-gray-600">
            <span className="font-medium text-mono-black">Shift Type</span>
            <input
              type="text"
              value={formState.shift_type ?? ''}
              onChange={handleChange('shift_type')}
              className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-mono-black"
              placeholder="Morning, Night, Split..."
            />
          </label>

          <footer className="flex justify-end gap-3 border-t border-mono-gray-200 pt-4">
            <Button type="button" variant="secondary" onClick={onClose} disabled={isSubmitting}>
              Cancel
            </Button>
            <Button type="submit" variant="primary" disabled={isSubmitting}>
              {isSubmitting ? 'Saving…' : initialData ? 'Save Changes' : 'Schedule Shift'}
            </Button>
          </footer>
        </form>
      </div>
    </div>
  );
};

export default RosterFormModal;
