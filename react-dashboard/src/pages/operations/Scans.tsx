import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { parcelsApi } from '../../services/api';

type TrackingEvent = {
  status?: string;
  location?: string;
  timestamp?: string;
  notes?: string;
  handled_by?: string;
};

type TrackingPayload = {
  tracking_number?: string;
  current_status?: string;
  events?: TrackingEvent[];
  shipment_details?: Record<string, unknown>;
};

const ScansPage: React.FC = () => {
  const [trackingNumber, setTrackingNumber] = useState('');
  const [result, setResult] = useState<TrackingPayload | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const traceMutation = useMutation({
    mutationFn: async (tracking: string) => {
      const response = await parcelsApi.trackByNumber(tracking);
      if (!response.success) {
        throw new Error(response.message ?? 'Tracking lookup failed');
      }
      return response.data as TrackingPayload;
    },
    onSuccess: (data) => {
      setResult(data);
      setErrorMessage(null);
    },
    onError: (mutationError: unknown) => {
      setResult(null);
      setErrorMessage(mutationError instanceof Error ? mutationError.message : 'Unable to retrieve tracking data');
    },
  });

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault();
    if (!trackingNumber.trim()) {
      setErrorMessage('Enter a tracking number to continue.');
      return;
    }
    traceMutation.mutate(trackingNumber.trim());
  };

  const events = result?.events ?? [];

  return (
    <div className="space-y-8">
      <header className="space-y-2">
        <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Operations</p>
        <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">Scan Logs</h1>
        <p className="text-sm text-mono-gray-600 max-w-2xl">
          Investigate checkpoint history for any parcel. Enter a tracking number to review scan events end-to-end.
        </p>
      </header>

      <Card className="border border-mono-gray-200 p-6">
        <form className="flex flex-col gap-4 md:flex-row" onSubmit={handleSubmit}>
          <Input
            type="text"
            placeholder="e.g. BRK-20250110-001"
            value={trackingNumber}
            onChange={(event) => setTrackingNumber(event.target.value)}
            className="flex-1"
          />
          <div className="flex gap-3">
            <Button type="submit" variant="primary" size="md" disabled={traceMutation.isPending}>
              <i className="fas fa-search mr-2" aria-hidden="true" />
              Lookup
            </Button>
            {result && (
              <Button
                type="button"
                variant="ghost"
                size="md"
                onClick={() => {
                  setTrackingNumber('');
                  setResult(null);
                  setErrorMessage(null);
                }}
              >
                Clear
              </Button>
            )}
          </div>
        </form>
        {traceMutation.isPending && <LoadingSpinner message="Fetching scan logs" />}
        {errorMessage && !traceMutation.isPending && (
          <p className="mt-4 text-sm text-red-600">{errorMessage}</p>
        )}
      </Card>

      {result && (
        <section className="space-y-6">
          <Card className="border border-mono-gray-200 p-6">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Tracking Number</p>
                <h2 className="text-2xl font-semibold text-mono-black">{result.tracking_number}</h2>
              </div>
              <div className="rounded-xl border border-mono-gray-200 bg-mono-gray-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-mono-black">
                {result.current_status ?? 'Unknown'}
              </div>
            </div>
          </Card>

          <Card className="border border-mono-gray-200 p-6">
            <h3 className="text-lg font-semibold text-mono-black">Scan Timeline</h3>
            <div className="mt-4 space-y-4">
              {events.length === 0 && <p className="text-sm text-mono-gray-500">No scan events recorded for this shipment.</p>}
              {events.map((event, index) => (
                <div key={`${event.timestamp}-${index}`} className="rounded-2xl border border-mono-gray-200 p-4 shadow-sm">
                  <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                      <p className="text-sm font-semibold text-mono-black">{event.status ?? 'Status update'}</p>
                      <p className="text-xs text-mono-gray-500">{event.location ?? 'Unknown location'}</p>
                    </div>
                    <div className="text-xs text-mono-gray-500">
                      {event.timestamp ? new Date(event.timestamp).toLocaleString() : '—'}
                    </div>
                  </div>
                  {event.notes && <p className="mt-2 text-xs text-mono-gray-600">Notes: {event.notes}</p>}
                  {event.handled_by && <p className="mt-1 text-xs text-mono-gray-600">Handled by {event.handled_by}</p>}
                </div>
              ))}
            </div>
          </Card>

          <Card className="border border-mono-gray-200 p-6">
            <h3 className="text-lg font-semibold text-mono-black">Raw Payload</h3>
            <pre className="mt-4 max-h-72 overflow-y-auto rounded-xl bg-mono-gray-50 p-4 text-xs text-mono-gray-700">
              {JSON.stringify(result, null, 2)}
            </pre>
          </Card>
        </section>
      )}
    </div>
  );
};

export default ScansPage;
