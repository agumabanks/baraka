import { useEffect, useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import Card from '../ui/Card';
import Button from '../ui/Button';
import Input from '../ui/Input';
import LoadingSpinner from '../ui/LoadingSpinner';
import { branchesApi, bookingWizardApi } from '../../services/api';
import type { BranchListResponse } from '../../types/branches';
import { toast } from '../../stores/toastStore';

interface BookingWizardModalProps {
  isOpen: boolean;
  onClose: () => void;
  onCompleted?: (shipmentId: number | string) => void;
}

interface BranchOption {
  id: number | string;
  name: string;
  code?: string;
  status_label?: string;
  status_state?: string;
  metrics?: Record<string, any> | null;
  workforce?: Record<string, any> | null;
  address?: string | null;
}

interface ParcelDraft {
  weight_kg: number;
  length_cm?: number;
  width_cm?: number;
  height_cm?: number;
  contents?: string;
  declared_value?: number;
}

const DEFAULT_PARCEL: ParcelDraft = {
  weight_kg: 1,
  length_cm: 30,
  width_cm: 20,
  height_cm: 10,
  contents: 'General Goods',
  declared_value: 0,
};

const asRecord = (value: unknown): Record<string, any> | null => {
  if (value && typeof value === 'object' && !Array.isArray(value)) {
    return value as Record<string, any>;
  }
  return null;
};

const coalesceRecord = (...values: unknown[]): Record<string, any> | null => {
  for (const value of values) {
    const record = asRecord(value);
    if (record) {
      return record;
    }
  }
  return null;
};

const MAX_PARCELS = 20;

const computeVolumetricWeight = (parcel: ParcelDraft): number => {
  if (!parcel.length_cm || !parcel.width_cm || !parcel.height_cm) {
    return 0;
  }

  const volumetric = (parcel.length_cm * parcel.width_cm * parcel.height_cm) / 5000;
  return Number.isFinite(volumetric) ? Number(volumetric.toFixed(2)) : 0;
};

const resolveNumeric = (value: string): number | undefined => {
  if (value.trim() === '') {
    return undefined;
  }

  const numeric = Number(value);
  return Number.isFinite(numeric) ? numeric : undefined;
};

const BookingWizardModal: React.FC<BookingWizardModalProps> = ({ isOpen, onClose, onCompleted }) => {
  const [step, setStep] = useState(1);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const [customer, setCustomer] = useState<Record<string, any> | null>(null);

  const [shipmentDraft, setShipmentDraft] = useState({
    origin_branch_id: '',
    dest_branch_id: '',
    service_level: 'STANDARD',
    incoterm: 'DAP',
    declared_value: '',
  });

  const [parcels, setParcels] = useState<ParcelDraft[]>([{ ...DEFAULT_PARCEL }]);
  const [invalidParcels, setInvalidParcels] = useState<number[]>([]);
  const [pricing, setPricing] = useState<Record<string, any> | null>(null);
  const [createdShipment, setCreatedShipment] = useState<Record<string, any> | null>(null);
  const [handoverMessage, setHandoverMessage] = useState<string | null>(null);

  const branchesQuery = useQuery<BranchListResponse>({
    queryKey: ['wizard', 'branches'],
    queryFn: async () => {
      const response = await branchesApi.getBranches({ per_page: 100 });
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to load branches');
      }
      return response.data;
    },
    enabled: isOpen,
  });

  const branchOptions: BranchOption[] = useMemo(() => {
    const raw = branchesQuery.data;
    if (!raw) return [];

    const fallback = raw as unknown as { items?: Array<Record<string, any>>; data?: Array<Record<string, any>> };
    const list = Array.isArray(raw.items)
      ? raw.items
      : Array.isArray(fallback.data)
        ? fallback.data
        : fallback.items ?? [];

    return list
      .map((branch) => {
        const record = branch as Record<string, any>;
        const id = record.id ?? record.branch_id ?? record.code;
        const name = record.name ?? record.title ?? (id !== undefined ? `Branch ${id}` : 'Branch');

        if (id === undefined || id === null) {
          return null;
        }

        const metrics = asRecord(record.metrics);
        const workforce = asRecord(record.workforce);

        return {
          id,
          name,
          code: record.code ?? record.branch_code ?? record.identifier ?? undefined,
          status_label: record.status_label ?? record.status_text ?? undefined,
          status_state: record.status_state ?? undefined,
          metrics: metrics ?? null,
          workforce: workforce ?? null,
          address: record.address ?? null,
        } as BranchOption;
      })
      .filter((item): item is BranchOption => Boolean(item));
  }, [branchesQuery.data]);

  const branchLookup = useMemo(() => {
    const map = new Map<string, BranchOption>();
    branchOptions.forEach((option) => {
      map.set(String(option.id), option);
    });
    return map;
  }, [branchOptions]);

  const originBranch = useMemo(() => branchLookup.get(String(shipmentDraft.origin_branch_id)) ?? null, [branchLookup, shipmentDraft.origin_branch_id]);
  const destinationBranch = useMemo(() => branchLookup.get(String(shipmentDraft.dest_branch_id)) ?? null, [branchLookup, shipmentDraft.dest_branch_id]);

  const totalParcelWeight = useMemo(() => parcels.reduce((sum, parcel) => sum + (parcel.weight_kg ?? 0), 0), [parcels]);
  const totalDeclaredValue = useMemo(() => parcels.reduce((sum, parcel) => sum + (parcel.declared_value ?? 0), 0), [parcels]);
  const totalVolumetricWeight = useMemo(() => parcels.reduce((sum, parcel) => sum + computeVolumetricWeight(parcel), 0), [parcels]);

  const pricingCurrency = pricing?.currency ?? 'EUR';
  const currencyFormatter = useMemo(() => new Intl.NumberFormat('en-US', { style: 'currency', currency: pricingCurrency, maximumFractionDigits: 2 }), [pricingCurrency]);
  const canAddParcel = parcels.length < MAX_PARCELS;

  const handleOriginBranchChange = (value: string) => {
    setShipmentDraft((prev) => {
      const next = { ...prev, origin_branch_id: value };
      if (value && prev.dest_branch_id === value) {
        next.dest_branch_id = '';
      }
      return next;
    });
  };

  const handleDestinationBranchChange = (value: string) => {
    setShipmentDraft((prev) => ({ ...prev, dest_branch_id: value }));
  };

  const updateParcel = (index: number, patch: Partial<ParcelDraft>) => {
    setParcels((prev) => prev.map((item, idx) => (idx === index ? { ...item, ...patch } : item)));
  };

  const duplicateParcel = (index: number) => {
    if (!canAddParcel) {
      toast.warning({
        title: 'Parcel limit reached',
        description: `You can add up to ${MAX_PARCELS} parcels per booking.`,
      });
      return;
    }

    setParcels((prev) => {
      const source = prev[index];
      return [...prev, { ...source }];
    });
  };

  const branchFallbackActive = useMemo(
    () => branchOptions.some((branch) => typeof branch.id === 'string' && !/^\d+$/.test(String(branch.id))),
    [branchOptions],
  );

  const resetState = () => {
    setStep(1);
    setInvalidParcels([]);
    setErrorMessage(null);
    setIsSubmitting(false);
    setCustomer(null);
    setShipmentDraft({ origin_branch_id: '', dest_branch_id: '', service_level: 'STANDARD', incoterm: 'DAP', declared_value: '' });
    setParcels([{ ...DEFAULT_PARCEL }]);
    setPricing(null);
    setCreatedShipment(null);
    setHandoverMessage(null);
  };

  useEffect(() => {
    if (!isOpen) {
      resetState();
    }
  }, [isOpen]);

  if (!isOpen) {
    return null;
  }

  const handleStep1 = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    if (!payload.name || !payload.email || !payload.phone) {
      setErrorMessage('Name, email, and phone are required.');
      toast.warning({
        title: 'Missing customer details',
        description: 'Please provide name, email, and phone to continue.',
      });
      return;
    }

    setIsSubmitting(true);
    setErrorMessage(null);
    try {
      const response = await bookingWizardApi.step1(payload);
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to start booking');
      }
      const responseData = asRecord(response.data);
      const resolvedCustomer = coalesceRecord(responseData?.customer, responseData, response);
      setCustomer(resolvedCustomer);
      setStep(2);
      toast.success({
        title: 'Customer captured',
        description: resolvedCustomer?.name ? `Working with ${resolvedCustomer.name}` : 'Customer profile saved for booking.',
      });
    } catch (err) {
      setErrorMessage(err instanceof Error ? err.message : 'Failed to create customer');
      toast.error({
        title: 'Customer creation failed',
        description: err instanceof Error ? err.message : 'Unable to create or locate the customer record.',
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleStep2 = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!customer?.id) {
      setErrorMessage('Customer information missing.');
      return;
    }

    if (!shipmentDraft.origin_branch_id || !shipmentDraft.dest_branch_id) {
      setErrorMessage('Origin and destination branches are required.');
      return;
    }

    if (
      branchFallbackActive ||
      !/^\d+$/.test(String(shipmentDraft.origin_branch_id)) ||
      !/^\d+$/.test(String(shipmentDraft.dest_branch_id))
    ) {
      setErrorMessage('Branch selection is using demo identifiers. Register real branches to enable booking.');
      return;
    }

    setIsSubmitting(true);
    setErrorMessage(null);
    try {
      const payload = {
        customer_id: customer.id,
        origin_branch_id: shipmentDraft.origin_branch_id,
        dest_branch_id: shipmentDraft.dest_branch_id,
        service_level: shipmentDraft.service_level,
        incoterm: shipmentDraft.incoterm,
        declared_value: shipmentDraft.declared_value ? Number(shipmentDraft.declared_value) : null,
      };
      const response = await bookingWizardApi.step2(payload);
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to save shipment details');
      }
      setStep(3);
      const origin = branchLookup.get(String(payload.origin_branch_id));
      const destination = branchLookup.get(String(payload.dest_branch_id));
      toast.success({
        title: 'Shipment configured',
        description: `${origin?.name ?? 'Origin'} → ${destination?.name ?? 'Destination'} • ${payload.service_level}`,
      });
    } catch (err) {
      setErrorMessage(err instanceof Error ? err.message : 'Failed to save shipment details');
      toast.error({
        title: 'Shipment details failed',
        description: err instanceof Error ? err.message : 'Unable to save shipment parameters.',
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleStep3 = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (parcels.length === 0) {
      setErrorMessage('Add at least one parcel.');
      setInvalidParcels([]);
      toast.warning({
        title: 'No parcels provided',
        description: 'Add at least one parcel to continue.',
      });
      return;
    }

    const invalidIndices: number[] = [];

    parcels.forEach((parcel, index) => {
      if (!parcel.weight_kg || parcel.weight_kg <= 0) {
        invalidIndices.push(index);
        return;
      }

      const dimensions = [parcel.length_cm, parcel.width_cm, parcel.height_cm];
      const invalidDimension = dimensions.some((dimension) => dimension !== undefined && dimension !== null && dimension <= 0);

      if (invalidDimension) {
        invalidIndices.push(index);
      }
    });

    if (invalidIndices.length > 0) {
      setInvalidParcels(invalidIndices);
      setErrorMessage('Please review the highlighted parcels.');
      toast.error({
        title: 'Parcel details incomplete',
        description: 'Ensure weights are positive and dimensions are valid.',
      });
      return;
    }

    setInvalidParcels([]);

    setIsSubmitting(true);
    setErrorMessage(null);
    try {
      const response = await bookingWizardApi.step3({ parcels });
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to price parcels');
      }
      const responseData = asRecord(response.data);
      const resolvedPricing = coalesceRecord(responseData?.pricing, responseData, response);
      setPricing(resolvedPricing);
      setStep(4);
      const pricingTotal = resolvedPricing?.total ?? 0;
      const pricingCurrencyLocal = resolvedPricing?.currency ?? pricingCurrency;
      const formatter = new Intl.NumberFormat('en-US', { style: 'currency', currency: pricingCurrencyLocal, maximumFractionDigits: 2 });
      toast.success({
        title: 'Parcels priced',
        description: `Estimated total ${formatter.format(pricingTotal)}`,
      });
    } catch (err) {
      setErrorMessage(err instanceof Error ? err.message : 'Failed to price parcels');
      toast.error({
        title: 'Pricing failed',
        description: err instanceof Error ? err.message : 'Unable to compute pricing for these parcels.',
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleConfirm = async () => {
    setIsSubmitting(true);
    setErrorMessage(null);
    try {
      const response = await bookingWizardApi.step4();
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to finalise booking');
      }
      const responseData = asRecord(response.data);
      const shipmentRecord = coalesceRecord(responseData?.shipment, responseData, response);
      setCreatedShipment(shipmentRecord);

      const shipmentId = shipmentRecord?.id;
      if (onCompleted && (typeof shipmentId === 'string' || typeof shipmentId === 'number')) {
        onCompleted(shipmentId);
      }
      setStep(5);
      toast.success({
        title: 'Shipment created',
        description: shipmentRecord?.id ? `Shipment #${shipmentRecord.id} is ready.` : 'Shipment created successfully.',
      });
    } catch (err) {
      setErrorMessage(err instanceof Error ? err.message : 'Failed to finalise booking');
      toast.error({
        title: 'Shipment creation failed',
        description: err instanceof Error ? err.message : 'Unable to create the shipment.',
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleHandover = async () => {
    if (!createdShipment?.id) return;
    setIsSubmitting(true);
    setHandoverMessage(null);
    try {
      const response = await bookingWizardApi.step5({ shipment_id: createdShipment.id });
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to handover shipment');
      }
      setHandoverMessage(response.message ?? 'Shipment handed over successfully');
      toast.success({
        title: 'Handover complete',
        description: response.message ?? 'Shipment handed over successfully.',
      });
    } catch (err) {
      setHandoverMessage(err instanceof Error ? err.message : 'Failed to handover shipment');
      toast.error({
        title: 'Handover failed',
        description: err instanceof Error ? err.message : 'Unable to mark the shipment as handed over.',
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const actions = (
    <div className="flex flex-wrap justify-between gap-3">
      <div className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Step {step} of 5</div>
      <div className="flex gap-3">
        <Button variant="ghost" size="sm" onClick={onClose}>Cancel</Button>
      </div>
    </div>
  );

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 py-6">
      <div className="relative w-full max-w-5xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
        <div className="border-b border-mono-gray-200 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Booking Wizard</p>
              <h2 className="text-2xl font-semibold text-mono-black">Create Shipment</h2>
            </div>
            <Button variant="ghost" size="sm" onClick={onClose}>
              Close
            </Button>
          </div>
          <div className="mt-3 flex items-center gap-2 text-xs uppercase tracking-[0.3em] text-mono-gray-500">
            {[1, 2, 3, 4, 5].map((value) => (
              <span key={value} className={`inline-flex h-6 w-6 items-center justify-center rounded-full ${value === step ? 'bg-mono-black text-mono-white' : 'bg-mono-gray-200 text-mono-gray-600'}`}>
                {value}
              </span>
            ))}
          </div>
        </div>

        <div className="space-y-6 p-6">
          {errorMessage && (
            <Card className="border border-red-200 bg-red-50 p-4 text-sm text-red-700">
              {errorMessage}
            </Card>
          )}

          {customer && step >= 2 && (
            <Card className="border border-mono-gray-200 bg-mono-gray-50 p-6">
              <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="space-y-2 text-sm text-mono-gray-700">
                  <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Customer</p>
                  <p className="text-lg font-semibold text-mono-black">{customer.name ?? 'Unnamed customer'}</p>
                  <p>{customer.email ?? 'No email'}</p>
                  <p>{customer.phone ?? 'No phone'}</p>
                </div>
                <div className="grid gap-2 text-sm text-mono-gray-700 md:grid-cols-2">
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Route</p>
                    <p className="text-mono-black">{originBranch?.name ?? 'Origin pending'} → {destinationBranch?.name ?? 'Destination pending'}</p>
                    {originBranch?.code && destinationBranch?.code && (
                      <p className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">{originBranch.code} • {destinationBranch.code}</p>
                    )}
                  </div>
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Service</p>
                    <p className="text-mono-black">{shipmentDraft.service_level}</p>
                    <p className="text-xs uppercase tracking-[0.2em] text-mono-gray-500">Incoterm {shipmentDraft.incoterm}</p>
                  </div>
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Parcels</p>
                    <p className="text-mono-black">{parcels.length} • {totalParcelWeight.toFixed(2)} kg</p>
                  </div>
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Declared Value</p>
                    <p className="text-mono-black">{shipmentDraft.declared_value ? currencyFormatter.format(Number(shipmentDraft.declared_value)) : currencyFormatter.format(totalDeclaredValue)}</p>
                  </div>
                </div>
                <Button variant="ghost" size="sm" onClick={() => setStep(1)}>
                  Edit Customer
                </Button>
              </div>
            </Card>
          )}

          {step === 1 && (
            <form className="space-y-6" onSubmit={handleStep1}>
              <Card className="border border-mono-gray-200 p-6">
                <h3 className="text-lg font-semibold text-mono-black">Customer</h3>
                <div className="mt-4 grid gap-4 md:grid-cols-2">
                  <Input name="name" placeholder="Customer name" required disabled={isSubmitting} />
                  <Input name="email" type="email" placeholder="Email" required disabled={isSubmitting} />
                  <Input name="phone" placeholder="Phone" required disabled={isSubmitting} />
                  <Input name="pickup_address" placeholder="Pickup address" disabled={isSubmitting} />
                  <Input name="delivery_address" placeholder="Delivery address" disabled={isSubmitting} className="md:col-span-2" />
                </div>
              </Card>
              <div className="flex justify-end gap-3">
                <Button type="submit" variant="primary" size="md" disabled={isSubmitting}>
                  {isSubmitting ? 'Saving…' : 'Next: Shipment Details'}
                </Button>
              </div>
            </form>
          )}

          {step === 2 && (
            <form className="space-y-6" onSubmit={handleStep2}>
              <Card className="border border-mono-gray-200 p-6">
                <h3 className="text-lg font-semibold text-mono-black">Shipment Parameters</h3>
                <div className="mt-4 grid gap-4 md:grid-cols-2">
                  <div>
                    <label className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Origin Branch</label>
                    <select
                      className="mt-2 w-full rounded-lg border border-mono-gray-300 px-3 py-2"
                      value={shipmentDraft.origin_branch_id}
                      onChange={(event) => handleOriginBranchChange(event.target.value)}
                      required
                      disabled={isSubmitting || branchesQuery.isLoading}
                    >
                      <option value="" disabled>Select branch</option>
                      {branchOptions.map((branch) => (
                        <option key={branch.id} value={branch.id}>{branch.name}</option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Destination Branch</label>
                    <select
                      className="mt-2 w-full rounded-lg border border-mono-gray-300 px-3 py-2"
                      value={shipmentDraft.dest_branch_id}
                      onChange={(event) => handleDestinationBranchChange(event.target.value)}
                      required
                      disabled={isSubmitting || branchesQuery.isLoading}
                    >
                      <option value="" disabled>Select branch</option>
                      {branchOptions.map((branch) => (
                        <option key={branch.id} value={branch.id} disabled={String(branch.id) === String(shipmentDraft.origin_branch_id)}>
                          {branch.name}
                        </option>
                      ))}
                    </select>
                  </div>
                  {branchFallbackActive && (
                    <p className="text-xs text-amber-700 md:col-span-2">
                      Branch directory is populated with demo data; shipment creation will be enabled once live branches exist.
                    </p>
                  )}
                  <div>
                    <label className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Service Level</label>
                    <select
                      className="mt-2 w-full rounded-lg border border-mono-gray-300 px-3 py-2"
                      value={shipmentDraft.service_level}
                      onChange={(event) => setShipmentDraft((prev) => ({ ...prev, service_level: event.target.value }))}
                      disabled={isSubmitting}
                    >
                      <option value="STANDARD">Standard</option>
                      <option value="EXPRESS">Express</option>
                      <option value="ECONOMY">Economy</option>
                    </select>
                  </div>
                  <div>
                    <label className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Incoterm</label>
                    <select
                      className="mt-2 w-full rounded-lg border border-mono-gray-300 px-3 py-2"
                      value={shipmentDraft.incoterm}
                      onChange={(event) => setShipmentDraft((prev) => ({ ...prev, incoterm: event.target.value }))}
                      disabled={isSubmitting}
                    >
                      <option value="DAP">DAP</option>
                      <option value="DDP">DDP</option>
                    </select>
                  </div>
                  <div>
                    <label className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Declared Value</label>
                    <Input
                      type="number"
                      min={0}
                      value={shipmentDraft.declared_value}
                      onChange={(event) => setShipmentDraft((prev) => ({ ...prev, declared_value: event.target.value }))}
                      disabled={isSubmitting}
                    />
                  </div>
                </div>
              </Card>
              <div className="flex justify-between gap-3">
                <Button type="button" variant="ghost" size="sm" onClick={() => setStep(1)} disabled={isSubmitting}>
                  Back
                </Button>
                <Button
                  type="submit"
                  variant="primary"
                  size="md"
                  disabled={isSubmitting || branchFallbackActive}
                  title={branchFallbackActive ? 'Register real branches to continue with booking.' : undefined}
                >
                  {isSubmitting ? 'Saving…' : 'Next: Parcels'}
                </Button>
              </div>
            </form>
          )}

          {step === 3 && (
            <form className="space-y-6" onSubmit={handleStep3}>
              <Card className="border border-mono-gray-200 p-6">
                <div className="flex items-center justify-between">
                  <h3 className="text-lg font-semibold text-mono-black">Parcels</h3>
                  <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    onClick={() => {
                      if (!canAddParcel) {
                        toast.warning({
                          title: 'Parcel limit reached',
                          description: `You can add up to ${MAX_PARCELS} parcels per booking.`,
                        });
                        return;
                      }
                      setParcels((prev) => [...prev, { ...DEFAULT_PARCEL }]);
                    }}
                    disabled={isSubmitting || !canAddParcel}
                  >
                    Add Parcel
                  </Button>
                </div>
                <div className="mt-4 space-y-4">
                  {parcels.map((parcel, index) => (
                    <div
                      key={index}
                      className={`rounded-2xl border p-4 ${invalidParcels.includes(index) ? 'border-red-300 bg-red-50/40' : 'border-mono-gray-200'}`}
                    >
                      <div className="flex items-center justify-between">
                        <h4 className="text-sm font-semibold text-mono-black">Parcel #{index + 1}</h4>
                        {parcels.length > 1 && (
                          <button
                            type="button"
                            className="text-xs uppercase tracking-[0.2em] text-red-600"
                            onClick={() => setParcels((prev) => prev.filter((_, idx) => idx !== index))}
                            disabled={isSubmitting}
                          >
                            Remove
                          </button>
                        )}
                      </div>
                      <div className="mt-3 grid gap-3 md:grid-cols-3">
                        <Input
                          type="number"
                          min={0.1}
                          step="0.1"
                          value={parcel.weight_kg ?? ''}
                          onChange={(event) => updateParcel(index, { weight_kg: Number(event.target.value) || 0 })}
                          placeholder="Weight (kg)"
                          required
                          disabled={isSubmitting}
                          aria-invalid={invalidParcels.includes(index)}
                        />
                        <Input
                          type="number"
                          min={1}
                          value={parcel.length_cm ?? ''}
                          onChange={(event) => updateParcel(index, { length_cm: resolveNumeric(event.target.value) })}
                          placeholder="Length (cm)"
                          disabled={isSubmitting}
                        />
                        <Input
                          type="number"
                          min={1}
                          value={parcel.width_cm ?? ''}
                          onChange={(event) => updateParcel(index, { width_cm: resolveNumeric(event.target.value) })}
                          placeholder="Width (cm)"
                          disabled={isSubmitting}
                        />
                        <Input
                          type="number"
                          min={1}
                          value={parcel.height_cm ?? ''}
                          onChange={(event) => updateParcel(index, { height_cm: resolveNumeric(event.target.value) })}
                          placeholder="Height (cm)"
                          disabled={isSubmitting}
                        />
                        <Input
                          value={parcel.contents ?? ''}
                          onChange={(event) => updateParcel(index, { contents: event.target.value })}
                          placeholder="Contents"
                          disabled={isSubmitting}
                        />
                        <Input
                          type="number"
                          min={0}
                          value={parcel.declared_value ?? ''}
                          onChange={(event) => updateParcel(index, { declared_value: resolveNumeric(event.target.value) ?? 0 })}
                          placeholder="Declared value"
                          disabled={isSubmitting}
                        />
                      </div>
                      <div className="mt-3 flex flex-wrap items-center justify-between gap-3 text-xs text-mono-gray-600">
                        <span>
                          Volumetric weight:{' '}
                          <span className="font-semibold text-mono-black">{computeVolumetricWeight(parcel).toFixed(2)} kg</span>
                        </span>
                        <div className="flex gap-2">
                          <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="uppercase tracking-[0.25em]"
                            onClick={() => duplicateParcel(index)}
                            disabled={isSubmitting || !canAddParcel}
                          >
                            Duplicate
                          </Button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </Card>

              <Card className="border border-mono-gray-200 bg-mono-gray-50 p-4">
                <div className="grid gap-3 md:grid-cols-3 text-sm">
                  <div>
                    <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Parcels</p>
                    <p className="text-lg font-semibold text-mono-black">{parcels.length}</p>
                  </div>
                  <div>
                    <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Total Weight</p>
                    <p className="text-lg font-semibold text-mono-black">{totalParcelWeight.toFixed(2)} kg</p>
                  </div>
                  <div>
                    <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Volumetric Weight</p>
                    <p className="text-lg font-semibold text-mono-black">{totalVolumetricWeight.toFixed(2)} kg</p>
                  </div>
                  <div>
                    <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Declared Value</p>
                    <p className="text-lg font-semibold text-mono-black">{currencyFormatter.format(totalDeclaredValue)}</p>
                  </div>
                </div>
              </Card>

              <div className="flex justify-between gap-3">
                <Button type="button" variant="ghost" size="sm" onClick={() => setStep(2)} disabled={isSubmitting}>
                  Back
                </Button>
                <Button type="submit" variant="primary" size="md" disabled={isSubmitting}>
                  {isSubmitting ? 'Calculating…' : 'Next: Pricing'}
                </Button>
              </div>
            </form>
          )}

          {step === 4 && (
            <div className="space-y-6">
              <Card className="border border-mono-gray-200 p-6">
                <h3 className="text-lg font-semibold text-mono-black">Pricing Summary</h3>
                {pricing ? (
                  <div className="mt-4 space-y-4 text-sm text-mono-gray-700">
                    <div className="grid gap-3 md:grid-cols-3">
                      <div className="rounded-xl border border-mono-gray-200 bg-mono-gray-50 p-4">
                        <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Subtotal</p>
                        <p className="text-lg font-semibold text-mono-black">{currencyFormatter.format(pricing.subtotal ?? 0)}</p>
                      </div>
                      <div className="rounded-xl border border-mono-gray-200 bg-mono-gray-50 p-4">
                        <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Tax</p>
                        <p className="text-lg font-semibold text-mono-black">{currencyFormatter.format(pricing.tax ?? 0)}</p>
                      </div>
                      <div className="rounded-xl border border-mono-gray-200 bg-mono-black p-4 text-mono-white">
                        <p className="text-xs uppercase tracking-[0.3em]">Total Due</p>
                        <p className="text-lg font-semibold">{currencyFormatter.format(pricing.total ?? 0)}</p>
                      </div>
                    </div>

                    {Array.isArray(pricing.parcel_details) && pricing.parcel_details.length > 0 && (
                      <div className="overflow-hidden rounded-2xl border border-mono-gray-200">
                        <table className="min-w-full divide-y divide-mono-gray-200 text-left text-sm">
                          <thead className="bg-mono-gray-100 uppercase tracking-[0.2em] text-xs text-mono-gray-500">
                            <tr>
                              <th className="px-4 py-3">Parcel</th>
                              <th className="px-4 py-3">Weight (kg)</th>
                              <th className="px-4 py-3">Base Rate</th>
                              <th className="px-4 py-3">Fuel</th>
                              <th className="px-4 py-3">Total</th>
                            </tr>
                          </thead>
                          <tbody className="divide-y divide-mono-gray-100 bg-white">
                            {pricing.parcel_details.map((detail: Record<string, any>, index: number) => (
                              <tr key={index}>
                                <td className="px-4 py-3 text-mono-gray-600">#{index + 1}</td>
                                <td className="px-4 py-3 text-mono-gray-600">{detail.weight ?? '—'}</td>
                                <td className="px-4 py-3 text-mono-gray-600">{currencyFormatter.format(detail.base_rate ?? 0)}</td>
                                <td className="px-4 py-3 text-mono-gray-600">{currencyFormatter.format(detail.fuel_surcharge ?? 0)}</td>
                                <td className="px-4 py-3 text-mono-black font-semibold">{currencyFormatter.format(detail.total ?? 0)}</td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    )}
                  </div>
                ) : (
                  <p className="text-sm text-mono-gray-600">Pricing not available.</p>
                )}
              </Card>
              <div className="flex justify-between gap-3">
                <Button variant="ghost" size="sm" onClick={() => setStep(3)} disabled={isSubmitting}>
                  Back
                </Button>
                <Button variant="primary" size="md" onClick={handleConfirm} disabled={isSubmitting}>
                  {isSubmitting ? 'Creating…' : 'Confirm & Create Shipment'}
                </Button>
              </div>
            </div>
          )}

          {step === 5 && (
            <div className="space-y-6">
              <Card className="border border-mono-gray-200 p-6">
                <h3 className="text-lg font-semibold text-mono-black">Shipment Created</h3>
                {createdShipment ? (
                  <div className="mt-4 space-y-3 text-sm text-mono-gray-700">
                    <p>Shipment #{createdShipment.id}</p>
                    <p>Status: {createdShipment.current_status}</p>
                    <p>Customer: {createdShipment.customer?.name ?? '—'}</p>
                    <pre className="max-h-60 overflow-y-auto rounded-xl bg-mono-gray-50 p-4 text-xs text-mono-gray-700">
                      {JSON.stringify(createdShipment, null, 2)}
                    </pre>
                  </div>
                ) : (
                  <p className="text-sm text-mono-gray-600">Shipment details unavailable.</p>
                )}
              </Card>

              <div className="flex flex-wrap items-center gap-3">
                <Button variant="primary" size="sm" onClick={handleHandover} disabled={isSubmitting || !createdShipment?.id}>
                  {isSubmitting ? 'Processing…' : 'Mark as Handed Over'}
                </Button>
                {createdShipment?.id && (
                  <Button
                    variant="secondary"
                    size="sm"
                    onClick={async () => {
                      try {
                        const blob = await bookingWizardApi.downloadLabels(createdShipment.id);
                        const url = window.URL.createObjectURL(blob);
                        const anchor = document.createElement('a');
                        anchor.href = url;
                        anchor.download = `labels-${createdShipment.id}.pdf`;
                        anchor.click();
                        window.URL.revokeObjectURL(url);
                        toast.success({
                          title: 'Labels downloaded',
                          description: `Labels ready for shipment #${createdShipment.id}.`,
                        });
                      } catch (downloadError) {
                        toast.error({
                          title: 'Label download failed',
                          description: downloadError instanceof Error ? downloadError.message : 'Unable to download shipping labels.',
                        });
                      }
                    }}
                  >
                    Download Labels
                  </Button>
                )}
                {handoverMessage && <span className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">{handoverMessage}</span>}
              </div>

              <div className="flex justify-end gap-3">
                <Button variant="secondary" size="md" onClick={resetState}>
                  Create Another Shipment
                </Button>
                <Button variant="primary" size="md" onClick={onClose}>
                  Finish
                </Button>
              </div>
            </div>
          )}

          {isSubmitting && step !== 1 && step !== 4 && <LoadingSpinner message="Processing" />}
        </div>

        <div className="border-t border-mono-gray-200 p-6">
          {actions}
        </div>
      </div>
    </div>
  );
};

export default BookingWizardModal;
