import React, { useMemo, useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import Button from '../ui/Button';
import Input from '../ui/Input';
import Select from '../ui/Select';
import Spinner from '../ui/Spinner';
import type {
  SalesCustomer,
  SalesCustomerDetail,
  SalesSelectOption,
} from '../../types/sales';
import { salesApi } from '../../services/api';

export type ActionView =
  | 'overview'
  | 'shipments'
  | 'billing'
  | 'payments'
  | 'preferences'
  | 'feedback'
  | 'history'
  | 'edit'
  | 'quotation'
  | 'contract'
  | 'address';

interface CustomerActionModalProps {
  customer: SalesCustomer;
  hubOptions: SalesSelectOption[];
  isOpen: boolean;
  onClose: () => void;
  onUpdated?: (customer: SalesCustomer) => void;
  initialView?: ActionView;
}

type FeedbackState = {
  status: 'idle' | 'success' | 'error';
  message?: string;
};

const actionTabs: Array<{ id: ActionView; label: string }> = [
  { id: 'overview', label: 'Profile' },
  { id: 'shipments', label: 'Shipments' },
  { id: 'billing', label: 'Billing' },
  { id: 'payments', label: 'Payments' },
  { id: 'preferences', label: 'Preferences' },
  { id: 'feedback', label: 'Feedback' },
  { id: 'history', label: 'History' },
  { id: 'edit', label: 'Edit Details' },
  { id: 'quotation', label: 'Create Quotation' },
  { id: 'contract', label: 'Create Contract' },
  { id: 'address', label: 'Add Address' },
];

const contractStatusOptions: SalesSelectOption[] = [
  { value: 'active', label: 'Active' },
  { value: 'suspended', label: 'Suspended' },
  { value: 'ended', label: 'Ended' },
];

const addressTypeOptions: SalesSelectOption[] = [
  { value: 'shipper', label: 'Shipper' },
  { value: 'consignee', label: 'Consignee' },
  { value: 'payer', label: 'Payer' },
];

const CustomerActionModal: React.FC<CustomerActionModalProps> = ({
  customer,
  hubOptions,
  isOpen,
  onClose,
  onUpdated,
  initialView = 'overview',
}) => {
  const queryClient = useQueryClient();
  const { data: detailResponse, isLoading: isDetailLoading, isError: isDetailError } = useQuery(
    {
      queryKey: ['sales', 'customers', 'detail', customer.id],
      queryFn: () => salesApi.getCustomerDetail(customer.id),
      enabled: isOpen,
      staleTime: 60_000,
    }
  );
  const detail: SalesCustomerDetail | undefined = detailResponse?.data;
  const [activeView, setActiveView] = useState<ActionView>(initialView);
  const [feedback, setFeedback] = useState<FeedbackState>({ status: 'idle' });

  const defaultHubOptions = useMemo(() => {
    if (!hubOptions || hubOptions.length === 0) {
      return [{ value: '', label: 'Unassigned' }];
    }
    const hasDefault = hubOptions.some((option) => option.value === '');
    return hasDefault ? hubOptions : [{ value: '', label: 'Unassigned' }, ...hubOptions];
  }, [hubOptions]);

  const [editForm, setEditForm] = useState({
    name: customer.name,
    email: customer.email ?? '',
    phone: customer.phone ?? '',
    hub_id: customer.hub?.id ? String(customer.hub.id) : '',
    address: customer.address ?? '',
    status: customer.status.account,
  });

  const [quotationForm, setQuotationForm] = useState({
    destination_country: '',
    service_type: '',
    pieces: 1,
    weight_kg: 0,
    volume_cm3: '',
    dim_factor: '',
    base_charge: 0,
    currency: 'USD',
    valid_until: '',
  });

  const [contractForm, setContractForm] = useState({
    name: '',
    start_date: '',
    end_date: '',
    status: 'active',
    notes: '',
  });

  const [addressForm, setAddressForm] = useState({
    type: 'shipper',
    name: '',
    phone_e164: '',
    email: '',
    country: '',
    city: '',
    address_line: '',
    tax_id: '',
  });

  useEffect(() => {
    if (!isOpen) {
      return;
    }
    setActiveView(initialView);
    setFeedback({ status: 'idle' });
    setEditForm({
      name: customer.name,
      email: customer.email ?? '',
      phone: customer.phone ?? '',
      hub_id: customer.hub?.id ? String(customer.hub.id) : '',
      address: customer.address ?? '',
      status: customer.status.account,
    });
  }, [customer, isOpen, initialView]);

  const formatCurrency = (value: number, currency: string) => {
    try {
      return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(value);
    } catch (error) {
      return `${currency} ${value.toFixed(2)}`;
    }
  };

  const addresses = detail?.addresses ?? [];
  const shipmentsInsight = detail?.shipments;
  const billingInsight = detail?.billing;
  const paymentsInsight = detail?.payments;
  const preferencesInsight = detail?.preferences;
  const feedbackInsight = detail?.feedback ?? {};
  const historyInsight = detail?.history ?? [];

  const detailDependentViews: ActionView[] = [
    'overview',
    'shipments',
    'billing',
    'payments',
    'preferences',
    'feedback',
    'history',
  ];

  const resetFeedback = () => setFeedback({ status: 'idle' });

  const updateMutation = useMutation({
    mutationFn: async () => {
      const payload: Record<string, unknown> = {
        name: editForm.name.trim(),
        email: editForm.email.trim(),
        mobile: editForm.phone.trim() || undefined,
        hub_id: editForm.hub_id ? Number(editForm.hub_id) : undefined,
        address: editForm.address.trim() || undefined,
        status: editForm.status,
      };
      return salesApi.updateCustomer(customer.id, payload);
    },
    onSuccess: (response) => {
      setFeedback({ status: 'success', message: response.message ?? 'Customer updated successfully.' });
      if (response.data?.customer) {
        onUpdated?.(response.data.customer);
        queryClient.invalidateQueries({ queryKey: ['sales', 'customers'] });
        queryClient.invalidateQueries({ queryKey: ['sales', 'customers', 'detail', customer.id] });
      }
    },
    onError: (error: any) => {
      const message = error?.response?.data?.message ?? 'Failed to update customer.';
      setFeedback({ status: 'error', message });
    },
  });

  const quotationMutation = useMutation({
    mutationFn: async () => {
      const payload = {
        customer_id: customer.id,
        destination_country: quotationForm.destination_country.trim().toUpperCase(),
        service_type: quotationForm.service_type.trim(),
        pieces: Number(quotationForm.pieces),
        weight_kg: Number(quotationForm.weight_kg),
        volume_cm3: quotationForm.volume_cm3 ? Number(quotationForm.volume_cm3) : undefined,
        dim_factor: quotationForm.dim_factor ? Number(quotationForm.dim_factor) : undefined,
        base_charge: Number(quotationForm.base_charge),
        currency: quotationForm.currency.trim().toUpperCase(),
        valid_until: quotationForm.valid_until || undefined,
      };
      return salesApi.createQuotation(payload);
    },
    onSuccess: (response) => {
      setFeedback({ status: 'success', message: response.message ?? 'Quotation created successfully.' });
      setQuotationForm({
        destination_country: '',
        service_type: '',
        pieces: 1,
        weight_kg: 0,
        volume_cm3: '',
        dim_factor: '',
        base_charge: 0,
        currency: 'USD',
        valid_until: '',
      });
      queryClient.invalidateQueries({ queryKey: ['sales', 'quotations'] });
      queryClient.invalidateQueries({ queryKey: ['sales', 'customers', 'detail', customer.id] });
    },
    onError: (error: any) => {
      const message = error?.response?.data?.message ?? 'Failed to create quotation.';
      setFeedback({ status: 'error', message });
    },
  });

  const contractMutation = useMutation({
    mutationFn: async () => {
      const payload = {
        customer_id: customer.id,
        name: contractForm.name.trim(),
        start_date: contractForm.start_date,
        end_date: contractForm.end_date,
        status: contractForm.status,
        notes: contractForm.notes.trim() || undefined,
      };
      return salesApi.createContract(payload);
    },
    onSuccess: (response) => {
      setFeedback({ status: 'success', message: response.message ?? 'Contract created successfully.' });
      setContractForm({ name: '', start_date: '', end_date: '', status: 'active', notes: '' });
      queryClient.invalidateQueries({ queryKey: ['sales', 'contracts'] });
      queryClient.invalidateQueries({ queryKey: ['sales', 'customers', 'detail', customer.id] });
    },
    onError: (error: any) => {
      const message = error?.response?.data?.message ?? 'Failed to create contract.';
      setFeedback({ status: 'error', message });
    },
  });

  const addressMutation = useMutation({
    mutationFn: async () => {
      const payload = {
        customer_id: customer.id,
        type: addressForm.type,
        name: addressForm.name.trim(),
        phone_e164: addressForm.phone_e164.trim(),
        email: addressForm.email.trim() || undefined,
        country: addressForm.country.trim().toUpperCase(),
        city: addressForm.city.trim(),
        address_line: addressForm.address_line.trim(),
        tax_id: addressForm.tax_id.trim() || undefined,
      };
      return salesApi.createAddressBookEntry(payload);
    },
    onSuccess: (response) => {
      setFeedback({ status: 'success', message: response.message ?? 'Address saved successfully.' });
      setAddressForm({ type: 'shipper', name: '', phone_e164: '', email: '', country: '', city: '', address_line: '', tax_id: '' });
      queryClient.invalidateQueries({ queryKey: ['sales', 'address-book'] });
      queryClient.invalidateQueries({ queryKey: ['sales', 'customers', 'detail', customer.id] });
    },
    onError: (error: any) => {
      const message = error?.response?.data?.message ?? 'Failed to save address.';
      setFeedback({ status: 'error', message });
    },
  });

  const renderFeedback = () => {
    if (feedback.status === 'idle') {
      return null;
    }
    const classes = feedback.status === 'success'
      ? 'bg-green-50 border-green-400 text-green-700'
      : 'bg-red-50 border-red-400 text-red-700';

    return (
      <div className={`border rounded-xl px-4 py-3 text-sm ${classes}`}>
        {feedback.message}
      </div>
    );
  };

  const overviewView = detail ? (
    <div className="space-y-6">
      <div className="grid gap-4 md:grid-cols-3">
        <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Customer</p>
          <p className="text-lg font-semibold text-mono-black">{detail.customer.name}</p>
          <p className="text-sm text-mono-gray-600">ID: {detail.customer.id}</p>
        </div>
        <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Engagement</p>
          <p className="text-sm text-mono-gray-700 capitalize">{detail.customer.status.engagement.replace('_', ' ')}</p>
          <p className="text-xs text-mono-gray-500 mt-1">Account: {detail.customer.status.account.toUpperCase()}</p>
        </div>
        <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Hub &amp; Contact</p>
          <p className="text-sm text-mono-gray-700">Hub: {detail.customer.hub?.name ?? 'Unassigned'}</p>
          <p className="text-sm text-mono-gray-700">Email: {detail.customer.email ?? '—'}</p>
          <p className="text-sm text-mono-gray-700">Phone: {detail.customer.phone ?? '—'}</p>
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-3">
        <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Recent Shipments</p>
          <p className="text-2xl font-semibold text-mono-black">{detail.shipments.summary.total}</p>
          <p className="text-xs text-mono-gray-500 mt-1">Value: {formatCurrency(detail.shipments.summary.total_value ?? 0, detail.shipments.summary.currency)}</p>
        </div>
        <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Outstanding Balance</p>
          <p className="text-2xl font-semibold text-mono-black">{formatCurrency(detail.billing.open_amount, detail.billing.currency)}</p>
          <p className="text-xs text-mono-gray-500 mt-1">Paid: {formatCurrency(detail.billing.paid_amount, detail.billing.currency)}</p>
        </div>
        <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Commercial Activity</p>
          <p className="text-sm text-mono-gray-700">Quotations: {detail.quotations.total}</p>
          <p className="text-sm text-mono-gray-700">Contracts: {detail.contracts.total}</p>
        </div>
      </div>

      <div>
        <h3 className="text-sm font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Contact Addresses</h3>
        <div className="mt-2 grid gap-3 md:grid-cols-2">
          {addresses.length > 0 ? (
            addresses.map((address) => (
              <div key={address.id} className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
                <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">{address.type}</p>
                <p className="text-sm font-semibold text-mono-black">{address.name}</p>
                <p className="text-xs text-mono-gray-500">{address.email ?? 'No email on file'}</p>
                <p className="text-xs text-mono-gray-500">{address.phone ?? 'No phone on file'}</p>
                <p className="text-xs text-mono-gray-500 mt-2">{address.address_line}</p>
                <p className="text-xs text-mono-gray-500">{address.city}, {address.country}</p>
              </div>
            ))
          ) : (
            <div className="rounded-2xl border border-dashed border-mono-gray-300 p-6 text-sm text-mono-gray-500">
              No saved addresses yet.
            </div>
          )}
        </div>
      </div>
    </div>
  ) : (
    <div className="py-10 text-center text-sm text-mono-gray-600">
      {isDetailLoading ? 'Loading client profile…' : 'Client profile data is unavailable.'}
    </div>
  );

  const shipmentsView = detail ? (
    <div className="space-y-6">
      <div className="grid gap-4 md:grid-cols-3">
        <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Total Shipments</p>
          <p className="text-2xl font-semibold text-mono-black">{shipmentsInsight?.summary.total ?? 0}</p>
        </div>
        <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Value Delivered</p>
          <p className="text-2xl font-semibold text-mono-black">
            {formatCurrency(shipmentsInsight?.summary.total_value ?? 0, shipmentsInsight?.summary.currency ?? 'USD')}
          </p>
        </div>
        <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Status Breakdown</p>
          <p className="text-sm text-mono-gray-700">
            {Object.entries(shipmentsInsight?.summary.by_status ?? {}).map(([status, count]) => (
              <span key={status} className="mr-2">
                {status}: {count}
              </span>
            ))}
          </p>
        </div>
      </div>

      <div>
        <h3 className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Recent Shipments</h3>
        <div className="mt-2 overflow-x-auto rounded-2xl border border-mono-gray-200">
          <table className="min-w-full divide-y divide-mono-gray-200 text-sm">
            <thead className="bg-mono-gray-50">
              <tr>
                <th className="px-4 py-2 text-left">ID</th>
                <th className="px-4 py-2 text-left">Service Level</th>
                <th className="px-4 py-2 text-left">Status</th>
                <th className="px-4 py-2 text-right">Value</th>
                <th className="px-4 py-2 text-left">Created</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-mono-gray-200">
              {(shipmentsInsight?.recent ?? []).map((shipment) => (
                <tr key={shipment.id}>
                  <td className="px-4 py-2">{shipment.id}</td>
                  <td className="px-4 py-2">{shipment.service_level ?? '—'}</td>
                  <td className="px-4 py-2 capitalize">{shipment.status.toLowerCase()}</td>
                  <td className="px-4 py-2 text-right">
                    {shipment.value !== null
                      ? formatCurrency(shipment.value, shipment.currency ?? shipmentsInsight?.summary.currency ?? 'USD')
                      : '—'}
                  </td>
                  <td className="px-4 py-2">{shipment.created_at ? new Date(shipment.created_at).toLocaleString() : '—'}</td>
                </tr>
              ))}
              {shipmentsInsight?.recent?.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-4 py-6 text-center text-mono-gray-500">
                    No shipment history recorded.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  ) : (
    <div className="py-10 text-center text-sm text-mono-gray-600">
      {isDetailLoading ? 'Loading shipment history…' : 'Shipment data unavailable.'}
    </div>
  );

  const billingView = detail ? (
    <div className="space-y-6">
      <div className="grid gap-4 md:grid-cols-3">
        <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Outstanding</p>
          <p className="text-2xl font-semibold text-mono-black">
            {formatCurrency(billingInsight?.open_amount ?? 0, billingInsight?.currency ?? 'USD')}
          </p>
        </div>
        <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Paid</p>
          <p className="text-2xl font-semibold text-mono-black">
            {formatCurrency(billingInsight?.paid_amount ?? 0, billingInsight?.currency ?? 'USD')}
          </p>
        </div>
        <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Overdue Invoices</p>
          <p className="text-2xl font-semibold text-mono-black">{billingInsight?.overdue_count ?? 0}</p>
        </div>
      </div>

      <div>
        <h3 className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Invoices</h3>
        <div className="mt-2 overflow-x-auto rounded-2xl border border-mono-gray-200">
          <table className="min-w-full divide-y divide-mono-gray-200 text-sm">
            <thead className="bg-mono-gray-50">
              <tr>
                <th className="px-4 py-2 text-left">Number</th>
                <th className="px-4 py-2 text-left">Status</th>
                <th className="px-4 py-2 text-right">Amount</th>
                <th className="px-4 py-2 text-left">Due</th>
                <th className="px-4 py-2 text-left">Created</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-mono-gray-200">
              {(billingInsight?.recent ?? []).map((invoice) => (
                <tr key={invoice.id}>
                  <td className="px-4 py-2">{invoice.number ?? invoice.id}</td>
                  <td className="px-4 py-2 uppercase text-xs font-semibold">{invoice.status}</td>
                  <td className="px-4 py-2 text-right">{formatCurrency(invoice.total_amount, invoice.currency)}</td>
                  <td className="px-4 py-2">{invoice.due_date ?? '—'}</td>
                  <td className="px-4 py-2">{invoice.created_at ? new Date(invoice.created_at).toLocaleString() : '—'}</td>
                </tr>
              ))}
              {billingInsight?.recent?.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-4 py-6 text-center text-mono-gray-500">
                    No billing records available.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  ) : (
    <div className="py-10 text-center text-sm text-mono-gray-600">
      {isDetailLoading ? 'Loading billing records…' : 'Billing data unavailable.'}
    </div>
  );

  const paymentsView = detail ? (
    <div className="space-y-6">
      <div className="grid gap-4 md:grid-cols-2">
        <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Paid Invoices</p>
          <p className="text-2xl font-semibold text-mono-black">
            {formatCurrency(paymentsInsight?.paid_invoices_total ?? 0, paymentsInsight?.currency ?? 'USD')}
          </p>
        </div>
        <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Open Invoices</p>
          <p className="text-2xl font-semibold text-mono-black">
            {formatCurrency(paymentsInsight?.open_invoices_total ?? 0, paymentsInsight?.currency ?? 'USD')}
          </p>
        </div>
      </div>

      <div>
        <h3 className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Recent Payments</h3>
        <div className="mt-2 overflow-x-auto rounded-2xl border border-mono-gray-200">
          <table className="min-w-full divide-y divide-mono-gray-200 text-sm">
            <thead className="bg-mono-gray-50">
              <tr>
                <th className="px-4 py-2 text-left">Invoice</th>
                <th className="px-4 py-2 text-left">Status</th>
                <th className="px-4 py-2 text-right">Amount</th>
                <th className="px-4 py-2 text-left">Paid</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-mono-gray-200">
              {(paymentsInsight?.recent ?? []).map((invoice) => (
                <tr key={invoice.id}>
                  <td className="px-4 py-2">{invoice.number ?? invoice.id}</td>
                  <td className="px-4 py-2 uppercase text-xs font-semibold">{invoice.status}</td>
                  <td className="px-4 py-2 text-right">{formatCurrency(invoice.total_amount, invoice.currency)}</td>
                  <td className="px-4 py-2">{invoice.paid_at ? new Date(invoice.paid_at).toLocaleString() : '—'}</td>
                </tr>
              ))}
              {paymentsInsight?.recent?.length === 0 && (
                <tr>
                  <td colSpan={4} className="px-4 py-6 text-center text-mono-gray-500">
                    No payment activity yet.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  ) : (
    <div className="py-10 text-center text-sm text-mono-gray-600">
      {isDetailLoading ? 'Loading payment history…' : 'Payment data unavailable.'}
    </div>
  );

  const defaultAddress = (preferencesInsight?.default_address ?? null) as Record<string, unknown> | null;

  const preferencesView = detail ? (
    <div className="space-y-4">
      <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
        <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Preferred Hub</p>
        <p className="text-sm text-mono-gray-700">{preferencesInsight?.preferred_hub ?? 'Not specified'}</p>
      </div>
      <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
        <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Primary Contact</p>
        <p className="text-sm text-mono-gray-700">{preferencesInsight?.primary_contact ?? detail.customer.name}</p>
      </div>
      <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
        <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Communication Channels</p>
        <p className="text-sm text-mono-gray-700">
          {(preferencesInsight?.communication_channels ?? ['—']).join(', ')}
        </p>
      </div>
      {defaultAddress && (
        <div className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
          <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Default Address</p>
          <p className="text-sm text-mono-gray-700">
            {typeof defaultAddress['address_line'] === 'string' ? (defaultAddress['address_line'] as string) : '—'}
          </p>
          {typeof defaultAddress['city'] === 'string' && typeof defaultAddress['country'] === 'string' && (
            <p className="text-xs text-mono-gray-500">
              {defaultAddress['city'] as string}, {defaultAddress['country'] as string}
            </p>
          )}
        </div>
      )}
    </div>
  ) : (
    <div className="py-10 text-center text-sm text-mono-gray-600">
      {isDetailLoading ? 'Loading delivery preferences…' : 'Preference data unavailable.'}
    </div>
  );

  const feedbackView = detail ? (
    <div className="space-y-4">
      <div className="grid gap-4 md:grid-cols-3">
        {Object.entries(feedbackInsight).map(([metric, value]) => (
          <div key={metric} className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
            <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">{metric.replace(/_/g, ' ')}</p>
            <p className="text-2xl font-semibold text-mono-black">{value}</p>
          </div>
        ))}
      </div>
      {Object.keys(feedbackInsight).length === 0 && (
        <div className="rounded-2xl border border-dashed border-mono-gray-300 p-6 text-sm text-mono-gray-500">
          No feedback metrics available yet.
        </div>
      )}
    </div>
  ) : (
    <div className="py-10 text-center text-sm text-mono-gray-600">
      {isDetailLoading ? 'Loading feedback KPIs…' : 'Feedback data unavailable.'}
    </div>
  );

  const historyView = detail ? (
    <div className="space-y-4">
      <ul className="space-y-3">
        {historyInsight.map((event, index) => (
          <li key={`${event.type}-${event.reference}-${index}`} className="rounded-2xl border border-mono-gray-200 bg-mono-white p-4 shadow-sm">
            <div className="flex items-center justify-between">
              <span className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">{event.type}</span>
              <span className="text-xs text-mono-gray-500">{event.timestamp ? new Date(event.timestamp).toLocaleString() : '—'}</span>
            </div>
            <div className="mt-1 text-sm text-mono-gray-700">
              Reference: {event.reference} • Status: {event.status}
            </div>
          </li>
        ))}
        {historyInsight.length === 0 && (
          <li className="rounded-2xl border border-dashed border-mono-gray-300 p-6 text-center text-sm text-mono-gray-500">
            No archival history captured yet.
          </li>
        )}
      </ul>
    </div>
  ) : (
    <div className="py-10 text-center text-sm text-mono-gray-600">
      {isDetailLoading ? 'Loading historical events…' : 'History data unavailable.'}
    </div>
  );

  const editView = (
    <form
      className="space-y-4"
      onSubmit={(event) => {
        event.preventDefault();
        resetFeedback();
        updateMutation.mutate();
      }}
    >
      <div className="grid gap-4 md:grid-cols-2">
        <Input
          label="Name"
          value={editForm.name}
          onChange={(event) => setEditForm((prev) => ({ ...prev, name: event.target.value }))}
          required
        />
        <Input
          label="Email"
          type="email"
          value={editForm.email}
          onChange={(event) => setEditForm((prev) => ({ ...prev, email: event.target.value }))}
          required
        />
        <Input
          label="Phone"
          value={editForm.phone}
          onChange={(event) => setEditForm((prev) => ({ ...prev, phone: event.target.value }))}
        />
        <Select
          label="Account Status"
          value={editForm.status}
          onChange={(event) => setEditForm((prev) => ({ ...prev, status: event.target.value as 'active' | 'inactive' }))}
          options={[
            { value: 'active', label: 'Active' },
            { value: 'inactive', label: 'Inactive' },
          ]}
        />
        <Select
          label="Hub"
          value={editForm.hub_id}
          onChange={(event) => setEditForm((prev) => ({ ...prev, hub_id: event.target.value }))}
          options={defaultHubOptions}
        />
        <Input
          label="Address"
          value={editForm.address}
          onChange={(event) => setEditForm((prev) => ({ ...prev, address: event.target.value }))}
        />
      </div>
      {renderFeedback()}
      <div className="flex items-center justify-end gap-2">
        <Button type="submit" variant="primary" disabled={updateMutation.isPending}>
          {updateMutation.isPending ? 'Saving…' : 'Save Changes'}
        </Button>
      </div>
    </form>
  );

  const quotationView = (
    <form
      className="space-y-4"
      onSubmit={(event) => {
        event.preventDefault();
        resetFeedback();
        quotationMutation.mutate();
      }}
    >
      <div className="grid gap-4 md:grid-cols-2">
        <Input
          label="Destination Country"
          placeholder="UG"
          value={quotationForm.destination_country}
          onChange={(event) => setQuotationForm((prev) => ({ ...prev, destination_country: event.target.value }))}
          required
        />
        <Input
          label="Service Type"
          value={quotationForm.service_type}
          onChange={(event) => setQuotationForm((prev) => ({ ...prev, service_type: event.target.value }))}
          required
        />
        <Input
          label="Pieces"
          type="number"
          min={1}
          value={quotationForm.pieces}
          onChange={(event) => setQuotationForm((prev) => ({ ...prev, pieces: Number(event.target.value) }))}
          required
        />
        <Input
          label="Weight (kg)"
          type="number"
          step="0.01"
          min={0}
          value={quotationForm.weight_kg}
          onChange={(event) => setQuotationForm((prev) => ({ ...prev, weight_kg: Number(event.target.value) }))}
          required
        />
        <Input
          label="Volume (cm³)"
          type="number"
          min={0}
          value={quotationForm.volume_cm3}
          onChange={(event) => setQuotationForm((prev) => ({ ...prev, volume_cm3: event.target.value }))}
        />
        <Input
          label="Dim Factor"
          type="number"
          min={1000}
          value={quotationForm.dim_factor}
          onChange={(event) => setQuotationForm((prev) => ({ ...prev, dim_factor: event.target.value }))}
        />
        <Input
          label="Base Charge"
          type="number"
          step="0.01"
          min={0}
          value={quotationForm.base_charge}
          onChange={(event) => setQuotationForm((prev) => ({ ...prev, base_charge: Number(event.target.value) }))}
          required
        />
        <Input
          label="Currency"
          value={quotationForm.currency}
          onChange={(event) => setQuotationForm((prev) => ({ ...prev, currency: event.target.value }))}
          required
        />
        <Input
          label="Valid Until"
          type="date"
          value={quotationForm.valid_until}
          onChange={(event) => setQuotationForm((prev) => ({ ...prev, valid_until: event.target.value }))}
        />
      </div>
      {renderFeedback()}
      <div className="flex items-center justify-end gap-2">
        <Button type="submit" variant="primary" disabled={quotationMutation.isPending}>
          {quotationMutation.isPending ? 'Creating…' : 'Create Quotation'}
        </Button>
      </div>
    </form>
  );

  const contractView = (
    <form
      className="space-y-4"
      onSubmit={(event) => {
        event.preventDefault();
        resetFeedback();
        contractMutation.mutate();
      }}
    >
      <div className="grid gap-4 md:grid-cols-2">
        <Input
          label="Contract Name"
          value={contractForm.name}
          onChange={(event) => setContractForm((prev) => ({ ...prev, name: event.target.value }))}
          required
        />
        <Select
          label="Status"
          value={contractForm.status}
          onChange={(event) => setContractForm((prev) => ({ ...prev, status: event.target.value }))}
          options={contractStatusOptions}
        />
        <Input
          label="Start Date"
          type="date"
          value={contractForm.start_date}
          onChange={(event) => setContractForm((prev) => ({ ...prev, start_date: event.target.value }))}
          required
        />
        <Input
          label="End Date"
          type="date"
          value={contractForm.end_date}
          onChange={(event) => setContractForm((prev) => ({ ...prev, end_date: event.target.value }))}
          required
        />
        <div className="md:col-span-2">
          <label className="block text-sm font-medium text-mono-gray-900 mb-1">Notes</label>
          <textarea
            className="w-full rounded-md border border-mono-gray-300 bg-mono-white px-3 py-2 text-sm text-mono-gray-800 focus:border-mono-black focus:outline-none focus:ring-2 focus:ring-mono-black/20"
            rows={3}
            value={contractForm.notes}
            onChange={(event) => setContractForm((prev) => ({ ...prev, notes: event.target.value }))}
          />
        </div>
      </div>
      {renderFeedback()}
      <div className="flex items-center justify-end gap-2">
        <Button type="submit" variant="primary" disabled={contractMutation.isPending}>
          {contractMutation.isPending ? 'Creating…' : 'Create Contract'}
        </Button>
      </div>
    </form>
  );

  const addressView = (
    <form
      className="space-y-4"
      onSubmit={(event) => {
        event.preventDefault();
        resetFeedback();
        addressMutation.mutate();
      }}
    >
      <div className="grid gap-4 md:grid-cols-2">
        <Select
          label="Type"
          value={addressForm.type}
          onChange={(event) => setAddressForm((prev) => ({ ...prev, type: event.target.value as 'shipper' | 'consignee' | 'payer' }))}
          options={addressTypeOptions}
        />
        <Input
          label="Contact Name"
          value={addressForm.name}
          onChange={(event) => setAddressForm((prev) => ({ ...prev, name: event.target.value }))}
          required
        />
        <Input
          label="Phone"
          value={addressForm.phone_e164}
          onChange={(event) => setAddressForm((prev) => ({ ...prev, phone_e164: event.target.value }))}
          required
        />
        <Input
          label="Email"
          type="email"
          value={addressForm.email}
          onChange={(event) => setAddressForm((prev) => ({ ...prev, email: event.target.value }))}
        />
        <Input
          label="Country"
          value={addressForm.country}
          onChange={(event) => setAddressForm((prev) => ({ ...prev, country: event.target.value }))}
          placeholder="UG"
          required
        />
        <Input
          label="City"
          value={addressForm.city}
          onChange={(event) => setAddressForm((prev) => ({ ...prev, city: event.target.value }))}
          required
        />
        <div className="md:col-span-2">
          <Input
            label="Address Line"
            value={addressForm.address_line}
            onChange={(event) => setAddressForm((prev) => ({ ...prev, address_line: event.target.value }))}
            required
          />
        </div>
        <Input
          label="Tax ID"
          value={addressForm.tax_id}
          onChange={(event) => setAddressForm((prev) => ({ ...prev, tax_id: event.target.value }))}
        />
      </div>
      {renderFeedback()}
      <div className="flex items-center justify-end gap-2">
        <Button type="submit" variant="primary" disabled={addressMutation.isPending}>
          {addressMutation.isPending ? 'Saving…' : 'Save Address'}
        </Button>
      </div>
    </form>
  );

  const renderActiveView = () => {
    if (detailDependentViews.includes(activeView)) {
      if (isDetailLoading) {
        return (
          <div className="flex justify-center py-10">
            <Spinner size="md" />
          </div>
        );
      }

      if (isDetailError) {
        return (
          <div className="py-10 text-center text-sm text-red-600">
            Unable to load client insights. Please try again.
          </div>
        );
      }
    }

    switch (activeView) {
      case 'overview':
        return overviewView;
      case 'shipments':
        return shipmentsView;
      case 'billing':
        return billingView;
      case 'payments':
        return paymentsView;
      case 'preferences':
        return preferencesView;
      case 'feedback':
        return feedbackView;
      case 'history':
        return historyView;
      case 'edit':
        return editView;
      case 'quotation':
        return quotationView;
      case 'contract':
        return contractView;
      case 'address':
        return addressView;
      default:
        return overviewView;
    }
  };

  if (!isOpen) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4">
      <div className="relative w-full max-w-4xl rounded-3xl bg-mono-white shadow-2xl">
        <div className="flex items-center justify-between border-b border-mono-gray-200 px-6 py-4">
          <div>
            <h2 className="text-xl font-semibold text-mono-black">Manage Customer</h2>
            <p className="text-sm text-mono-gray-500">{customer.name}</p>
          </div>
          <button
            type="button"
            onClick={() => {
              setFeedback({ status: 'idle' });
              onClose();
            }}
            className="rounded-full bg-mono-gray-100 p-2 text-mono-gray-700 hover:bg-mono-gray-200 hover:text-mono-black"
            aria-label="Close"
          >
            ×
          </button>
        </div>

        <div className="px-6 pt-4">
          <div className="flex flex-wrap gap-2">
            {actionTabs.map((tab) => (
              <button
                key={tab.id}
                type="button"
                onClick={() => {
                  resetFeedback();
                  setActiveView(tab.id);
                }}
                className={`rounded-full px-4 py-2 text-sm font-semibold transition-colors ${
                  activeView === tab.id
                    ? 'bg-mono-black text-mono-white'
                    : 'border border-mono-gray-300 text-mono-gray-700 hover:border-mono-black hover:text-mono-black'
                }`}
              >
                {tab.label}
              </button>
            ))}
          </div>
        </div>

        <div className="px-6 py-6">
          <div className="space-y-6">
            {renderActiveView()}
          </div>
        </div>
      </div>
    </div>
  );
};

export default CustomerActionModal;
