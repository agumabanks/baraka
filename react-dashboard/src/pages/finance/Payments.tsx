import { useQuery } from '@tanstack/react-query';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { financeApi } from '../../services/api';

type FinanceCollection = Record<string, any>;

const PaymentsPage: React.FC = () => {
  const normaliseCollection = (collection: unknown): any[] => {
    if (Array.isArray(collection)) {
      return collection;
    }

    if (collection && typeof collection === 'object') {
      const record = collection as Record<string, unknown>;

      if (Array.isArray(record.data)) {
        return record.data;
      }

      if (Array.isArray(record.accounts)) {
        return record.accounts;
      }

      if (Array.isArray(record.requests)) {
        return record.requests;
      }

      if (Array.isArray(record.payments)) {
        return record.payments;
      }
    }

    return [];
  };

  const accountsQuery = useQuery<FinanceCollection>({
    queryKey: ['finance', 'payment-accounts'],
    queryFn: async () => {
      const response = await financeApi.getPaymentAccounts();
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to load payment accounts');
      }
      return response.data as FinanceCollection;
    },
  });

  const requestsQuery = useQuery<FinanceCollection>({
    queryKey: ['finance', 'payment-requests'],
    queryFn: async () => {
      const response = await financeApi.getPaymentRequests();
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to load payment requests');
      }
      return response.data as FinanceCollection;
    },
  });

  if (accountsQuery.isLoading && !accountsQuery.data) {
    return <LoadingSpinner message="Loading payments" />;
  }

  if (accountsQuery.isError) {
    const message = accountsQuery.error instanceof Error ? accountsQuery.error.message : 'Unable to load payment data';
    return (
      <Card className="p-8 text-center">
        <h2 className="text-2xl font-semibold text-mono-black">Payments unavailable</h2>
        <p className="text-sm text-mono-gray-600 mt-2">{message}</p>
        <Button className="mt-6" variant="primary" onClick={() => accountsQuery.refetch()}>
          Retry
        </Button>
      </Card>
    );
  }

  const accountEntries = Array.isArray(accountsQuery.data?.accounts)
    ? accountsQuery.data?.accounts
    : Array.isArray(accountsQuery.data?.data)
      ? accountsQuery.data?.data
      : normaliseCollection(accountsQuery.data);

  const requestEntries = Array.isArray(requestsQuery.data?.data)
    ? requestsQuery.data?.data
    : Array.isArray(requestsQuery.data?.requests)
      ? requestsQuery.data?.requests
      : normaliseCollection(requestsQuery.data);

  return (
    <div className="space-y-8">
      <header className="space-y-2">
        <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Finance</p>
        <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">Payments</h1>
        <p className="text-sm text-mono-gray-600 max-w-2xl">
          Track merchant payout accounts and payment requests in real time.
        </p>
      </header>

      <Card className="border border-mono-gray-200 p-6">
        <h2 className="text-lg font-semibold text-mono-black">Payment Accounts</h2>
        <div className="mt-4 overflow-x-auto">
          <table className="min-w-full divide-y divide-mono-gray-200 text-left text-sm">
            <thead>
              <tr className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">
                <th className="px-4 py-3">Account</th>
                <th className="px-4 py-3">Type</th>
                <th className="px-4 py-3">Balance</th>
                <th className="px-4 py-3">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-mono-gray-100">
              {accountEntries.map((account: any, index: number) => (
                <tr key={account.id ?? index} className="hover:bg-mono-gray-50">
                  <td className="px-4 py-3 font-medium text-mono-black">{account.account_name ?? account.account ?? 'Account'}</td>
                  <td className="px-4 py-3">{account.account_type ?? account.type ?? '—'}</td>
                  <td className="px-4 py-3">{account.balance ? Number(account.balance).toLocaleString() : '—'}</td>
                  <td className="px-4 py-3 text-xs uppercase tracking-[0.2em] text-mono-gray-600">{account.status ?? 'active'}</td>
                </tr>
              ))}
              {accountEntries.length === 0 && (
                <tr>
                  <td colSpan={4} className="px-4 py-6 text-center text-mono-gray-500">
                    No payment accounts configured.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </Card>

      <Card className="border border-mono-gray-200 p-6">
        <h2 className="text-lg font-semibold text-mono-black">Payment Requests</h2>
        {requestsQuery.isLoading && <LoadingSpinner message="Loading payment requests" />}
        {requestsQuery.isError && (
          <p className="mt-4 text-sm text-red-600">
            {requestsQuery.error instanceof Error ? requestsQuery.error.message : 'Unable to load payment requests'}
          </p>
        )}
        {!requestsQuery.isLoading && !requestsQuery.isError && (
          <div className="mt-4 overflow-x-auto">
            <table className="min-w-full divide-y divide-mono-gray-200 text-left text-sm">
              <thead>
                <tr className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">
                  <th className="px-4 py-3">Reference</th>
                  <th className="px-4 py-3">Amount</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Created</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-mono-gray-100">
                {requestEntries.map((request: any, index: number) => (
                  <tr key={request.id ?? index} className="hover:bg-mono-gray-50">
                    <td className="px-4 py-3 font-medium text-mono-black">{request.reference ?? request.id}</td>
                    <td className="px-4 py-3">{request.amount ? Number(request.amount).toLocaleString() : '—'}</td>
                    <td className="px-4 py-3 text-xs uppercase tracking-[0.2em] text-mono-gray-600">{request.status ?? 'pending'}</td>
                    <td className="px-4 py-3">{request.created_at ?? '—'}</td>
                  </tr>
                ))}
                {requestEntries.length === 0 && (
                  <tr>
                    <td colSpan={4} className="px-4 py-6 text-center text-mono-gray-500">
                      No payment requests found.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        )}
      </Card>
    </div>
  );
};

export default PaymentsPage;
