import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { financeApi } from '../../services/api';

type InvoiceSummary = Record<string, any>;

const InvoicesPage: React.FC = () => {
  const [selectedInvoice, setSelectedInvoice] = useState<number | string | null>(null);

  const normaliseInvoices = (payload: unknown): InvoiceSummary[] => {
    if (Array.isArray(payload)) {
      return payload as InvoiceSummary[];
    }

    if (payload && typeof payload === 'object') {
      const record = payload as Record<string, any>;

      if (Array.isArray(record.data)) {
        return record.data as InvoiceSummary[];
      }

      const invoices = record.invoices as Record<string, any> | undefined;
      if (invoices) {
        if (Array.isArray(invoices)) {
          return invoices as InvoiceSummary[];
        }

        if (Array.isArray(invoices.data)) {
          return invoices.data as InvoiceSummary[];
        }
      }
    }

    return [];
  };

  const normaliseInvoiceDetail = (payload: unknown): InvoiceSummary | null => {
    if (!payload || typeof payload !== 'object') {
      return null;
    }

    const record = payload as Record<string, any>;

    if (record.invoice && typeof record.invoice === 'object') {
      return record.invoice as InvoiceSummary;
    }

    return record as InvoiceSummary;
  };

  const invoicesQuery = useQuery<InvoiceSummary[]>({
    queryKey: ['finance', 'invoices'],
    queryFn: async () => {
      const response = await financeApi.getInvoices();
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to load invoices');
      }
      return normaliseInvoices(response.data);
    },
  });

  const detailQuery = useQuery<InvoiceSummary | null>({
    queryKey: ['finance', 'invoice-detail', selectedInvoice],
    queryFn: async ({ queryKey }) => {
      const invoiceId = queryKey[2] as number | string | undefined;
      if (!invoiceId) {
        return null;
      }
      const response = await financeApi.getInvoiceDetails(invoiceId);
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to load invoice details');
      }
      return normaliseInvoiceDetail(response.data) ?? null;
    },
    enabled: Boolean(selectedInvoice),
  });

  if (invoicesQuery.isLoading && !invoicesQuery.data) {
    return <LoadingSpinner message="Loading invoices" />;
  }

  if (invoicesQuery.isError) {
    const message = invoicesQuery.error instanceof Error ? invoicesQuery.error.message : 'Unable to load invoices';
    return (
      <Card className="p-8 text-center">
        <h2 className="text-2xl font-semibold text-mono-black">Invoices unavailable</h2>
        <p className="mt-2 text-sm text-mono-gray-600">{message}</p>
        <Button className="mt-6" variant="primary" onClick={() => invoicesQuery.refetch()}>
          Retry
        </Button>
      </Card>
    );
  }

  const invoices = invoicesQuery.data ?? [];

  return (
    <div className="space-y-8">
      <header className="space-y-2">
        <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Finance</p>
        <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">Invoices</h1>
        <p className="text-sm text-mono-gray-600 max-w-2xl">
          Review invoice lifecycle, status, and payable amounts. Select an invoice to view a detailed breakdown.
        </p>
      </header>

      <Card className="border border-mono-gray-200">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-mono-gray-200 text-left text-sm">
            <thead>
              <tr className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">
                <th className="px-4 py-3">Invoice</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3">Amount</th>
                <th className="px-4 py-3">Date</th>
                <th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody className="divide-y divide-mono-gray-100">
              {invoices.map((invoice) => (
                <tr key={invoice.id ?? invoice.invoice_id} className="hover:bg-mono-gray-50">
                  <td className="px-4 py-3 font-medium text-mono-black">{invoice.invoice_id ?? invoice.id}</td>
                  <td className="px-4 py-3 text-xs uppercase tracking-[0.2em] text-mono-gray-600">{invoice.status ?? 'N/A'}</td>
                  <td className="px-4 py-3">{invoice.amount ? Number(invoice.amount).toLocaleString() : '—'}</td>
                  <td className="px-4 py-3">{invoice.invoice_date ?? invoice.created_at ?? '—'}</td>
                  <td className="px-4 py-3 text-right">
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => setSelectedInvoice(invoice.id ?? invoice.invoice_id)}
                      className="uppercase tracking-[0.2em]"
                    >
                      View
                    </Button>
                  </td>
                </tr>
              ))}
              {invoices.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-4 py-6 text-center text-mono-gray-500">
                    No invoices found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </Card>

      {selectedInvoice && (
        <Card className="border border-mono-gray-200 p-6">
          <div className="flex items-center justify-between">
            <h2 className="text-lg font-semibold text-mono-black">Invoice Detail</h2>
            <Button variant="ghost" size="sm" onClick={() => setSelectedInvoice(null)}>
              Close
            </Button>
          </div>
          {detailQuery.isLoading && <LoadingSpinner message="Loading invoice detail" />}
          {detailQuery.isError && (
            <p className="mt-4 text-sm text-red-600">
              {detailQuery.error instanceof Error ? detailQuery.error.message : 'Unable to load invoice detail'}
            </p>
          )}
          {detailQuery.data && (
            <pre className="mt-4 max-h-72 overflow-y-auto rounded-xl bg-mono-gray-50 p-4 text-xs text-mono-gray-700">
              {JSON.stringify(detailQuery.data, null, 2)}
            </pre>
          )}
        </Card>
      )}
    </div>
  );
};

export default InvoicesPage;
