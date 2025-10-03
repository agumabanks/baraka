import React, { useCallback, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import type { AxiosError } from 'axios';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Select from '../../components/ui/Select';
import Input from '../../components/ui/Input';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { salesApi } from '../../services/api';
import type { ApiResponse } from '../../services/api';
import type { SalesCustomer, SalesSelectOption } from '../../types/sales';

type CustomerMeta = {
  hub_options: SalesSelectOption[];
  status_options: SalesSelectOption[];
  engagement_options: SalesSelectOption[];
};

interface FormState {
  name: string;
  email: string;
  phone: string;
  password: string;
  hub_id: string;
  address: string;
}

type FormErrors = Partial<Record<keyof FormState, string>>;

const initialFormState: FormState = {
  name: '',
  email: '',
  phone: '',
  password: '',
  hub_id: '',
  address: '',
};

const CreateCustomer: React.FC = () => {
  const queryClient = useQueryClient();
  const [form, setForm] = useState<FormState>(initialFormState);
  const [formErrors, setFormErrors] = useState<FormErrors>({});
  const [feedback, setFeedback] = useState<string | null>(null);

  const { data: metaResponse, isLoading: isMetaLoading } = useQuery<CustomerMeta, Error>({
    queryKey: ['sales', 'customers', 'meta'],
    queryFn: async () => {
      const response = await salesApi.getCustomerMeta();
      return response.data;
    },
    staleTime: 1000 * 60 * 15,
  });

  const hubOptions = useMemo(() => {
    const base: SalesSelectOption[] = [{ value: '', label: 'Unassigned' }];
    return metaResponse ? base.concat(metaResponse.hub_options) : base;
  }, [metaResponse]);

  const mutation = useMutation<ApiResponse<{ customer: SalesCustomer }>, AxiosError<{ errors?: Record<string, string[]>; message?: string }>, Record<string, unknown>>({
    mutationFn: async (payload) => {
      const response = await salesApi.createCustomer(payload);
      return response;
    },
    onSuccess: (response) => {
      setFeedback(`Customer “${response.data.customer.name}” was created successfully.`);
      setForm(initialFormState);
      setFormErrors({});
      queryClient.invalidateQueries({ queryKey: ['sales', 'customers'] });
    },
    onError: (axiosError) => {
      setFeedback(null);
      const validationErrors = axiosError.response?.data?.errors;
      if (validationErrors) {
        const parsedErrors = Object.entries(validationErrors).reduce<FormErrors>((acc, [key, messages]) => {
          acc[key as keyof FormState] = messages[0] ?? 'Invalid value';
          return acc;
        }, {});
        setFormErrors(parsedErrors);
        return;
      }
      setFormErrors({});
    },
  });

  const handleChange = useCallback((field: keyof FormState, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }));
    setFormErrors((prev) => ({ ...prev, [field]: undefined }));
  }, []);

  const handleSubmit = useCallback((event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setFormErrors({});
    setFeedback(null);

    const payload: Record<string, unknown> = {
      name: form.name,
      email: form.email,
      mobile: form.phone || undefined,
      password: form.password || undefined,
      address: form.address || undefined,
    };

    if (form.hub_id) {
      payload.hub_id = Number(form.hub_id);
    }

    mutation.mutate(payload);
  }, [form, mutation]);

  if (isMetaLoading && !metaResponse) {
    return (
      <div className="flex h-full items-center justify-center">
        <LoadingSpinner message="Loading customer form" />
      </div>
    );
  }

  return (
    <div className="space-y-10">
      <section className="space-y-6">
        <header className="space-y-3">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
            Sales Enablement
          </p>
          <div className="space-y-3">
            <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
              Create Customer
            </h1>
            <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
              Onboard a new strategic account with verified contact details and branch allocation.
            </p>
          </div>
        </header>

        <Card className="border border-mono-gray-200">
          <form className="space-y-6" onSubmit={handleSubmit}>
            <div className="grid gap-4 md:grid-cols-2">
              <Input
                label="Customer Name"
                value={form.name}
                onChange={(event) => handleChange('name', event.target.value)}
                placeholder="Acme Exports"
                required
                error={formErrors.name}
              />
              <Input
                label="Email"
                type="email"
                value={form.email}
                onChange={(event) => handleChange('email', event.target.value)}
                placeholder="customer@example.com"
                required
                error={formErrors.email}
              />
              <Input
                label="Phone"
                value={form.phone}
                onChange={(event) => handleChange('phone', event.target.value)}
                placeholder="+256 700 000000"
                error={formErrors.phone}
              />
              <Input
                label="Temporary Password"
                type="password"
                value={form.password}
                onChange={(event) => handleChange('password', event.target.value)}
                placeholder="Leave blank to auto-generate"
                helperText="Minimum 8 characters"
                error={formErrors.password}
              />
              <Select
                label="Assign Hub"
                value={form.hub_id}
                onChange={(event) => handleChange('hub_id', event.target.value)}
                options={hubOptions}
              />
              <Input
                label="Address"
                value={form.address}
                onChange={(event) => handleChange('address', event.target.value)}
                placeholder="Corporate address"
                error={formErrors.address}
              />
            </div>

            {feedback && (
              <div className="rounded-xl border border-mono-gray-300 bg-mono-gray-25 px-4 py-3 text-sm text-mono-gray-800">
                {feedback}
              </div>
            )}

            {mutation.isError && !feedback && (
              <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {mutation.error?.response?.data?.message ?? 'Unable to create customer. Please review the form.'}
              </div>
            )}

            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <span className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">
                New records are immediately available to the Sales workspace
              </span>
              <div className="flex items-center gap-3">
                <Button type="submit" variant="primary" disabled={mutation.isPending} className="uppercase tracking-[0.25em]">
                  {mutation.isPending ? 'Saving…' : 'Create Customer'}
                </Button>
                <Button
                  type="button"
                  variant="ghost"
                  className="uppercase tracking-[0.25em]"
                  onClick={() => {
                    setForm(initialFormState);
                    setFormErrors({});
                    setFeedback(null);
                  }}
                >
                  Reset
                </Button>
              </div>
            </div>
          </form>
        </Card>
      </section>
    </div>
  );
};

export default CreateCustomer;
