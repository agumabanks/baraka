import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Input from '../components/ui/Input';
import LoadingSpinner from '../components/ui/LoadingSpinner';
import api from '../services/api';

interface TrackingEvent {
  id: number;
  status: string;
  location: string;
  timestamp: string;
  notes?: string;
  handled_by?: string;
}

interface ShipmentTracking {
  tracking_number: string;
  current_status: string;
  origin: string;
  destination: string;
  estimated_delivery?: string;
  actual_delivery?: string;
  events: TrackingEvent[];
  shipment_details?: {
    sender_name?: string;
    sender_phone?: string;
    recipient_name?: string;
    recipient_phone?: string;
    weight?: number;
    pieces?: number;
  };
}

const LiveTracking: React.FC = () => {
  const [trackingNumber, setTrackingNumber] = useState('');
  const [searchedNumber, setSearchedNumber] = useState('');
  const [isSearching, setIsSearching] = useState(false);

  const { data, isLoading, isError, error, refetch } = useQuery<{ success: boolean; data: ShipmentTracking }>({
    queryKey: ['tracking', searchedNumber],
    queryFn: async () => {
      const response = await api.get(`/v10/parcel/tracking/${searchedNumber}`);
      return response.data;
    },
    enabled: !!searchedNumber,
    retry: false,
  });

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    if (trackingNumber.trim()) {
      setSearchedNumber(trackingNumber.trim());
      setIsSearching(true);
    }
  };

  const handleReset = () => {
    setTrackingNumber('');
    setSearchedNumber('');
    setIsSearching(false);
  };

  const trackingData = data?.data;

  const getStatusColor = (status: string) => {
    const statusLower = status.toLowerCase();
    if (statusLower.includes('delivered')) return 'bg-green-100 text-green-800 border-green-200';
    if (statusLower.includes('transit') || statusLower.includes('picked')) return 'bg-blue-100 text-blue-800 border-blue-200';
    if (statusLower.includes('pending') || statusLower.includes('processing')) return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    if (statusLower.includes('exception') || statusLower.includes('failed')) return 'bg-red-100 text-red-800 border-red-200';
    return 'bg-mono-gray-100 text-mono-gray-800 border-mono-gray-200';
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <section className="rounded-3xl border border-mono-gray-200 bg-mono-white shadow-xl">
        <header className="border-b border-mono-gray-200 px-8 py-10">
          <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Real-Time Tracking
            </p>
            <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
              Live Shipment Tracking
            </h1>
            <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
              Track your shipments in real-time. Enter a tracking number to view the current status and complete shipment journey.
            </p>
          </div>
        </header>

        {/* Search Form */}
        <div className="px-8 py-6">
          <form onSubmit={handleSearch} className="flex gap-4">
            <div className="flex-1">
              <Input
                type="text"
                placeholder="Enter tracking number (e.g., BRK-20250110-001)"
                value={trackingNumber}
                onChange={(e) => setTrackingNumber(e.target.value)}
                className="w-full"
              />
            </div>
            <Button 
              type="submit" 
              variant="primary" 
              size="md"
              disabled={!trackingNumber.trim() || isLoading}
            >
              <i className="fas fa-search mr-2" aria-hidden="true" />
              Track Shipment
            </Button>
            {searchedNumber && (
              <Button 
                type="button" 
                variant="secondary" 
                size="md"
                onClick={handleReset}
              >
                <i className="fas fa-times mr-2" aria-hidden="true" />
                Clear
              </Button>
            )}
          </form>
        </div>
      </section>

      {/* Loading State */}
      {isLoading && (
        <LoadingSpinner message="Tracking shipment..." />
      )}

      {/* Error State */}
      {isError && searchedNumber && (
        <Card className="border-2 border-red-200 bg-red-50">
          <div className="flex items-start gap-4">
            <div className="flex-shrink-0">
              <i className="fas fa-exclamation-circle text-2xl text-red-600" aria-hidden="true" />
            </div>
            <div className="flex-1">
              <h3 className="text-lg font-semibold text-red-900">Shipment Not Found</h3>
              <p className="mt-1 text-sm text-red-700">
                {error instanceof Error ? error.message : `No shipment found with tracking number: ${searchedNumber}`}
              </p>
              <p className="mt-2 text-xs text-red-600">
                Please check the tracking number and try again.
              </p>
            </div>
          </div>
        </Card>
      )}

      {/* Tracking Results */}
      {trackingData && !isLoading && (
        <div className="space-y-6">
          {/* Status Overview */}
          <Card className="border border-mono-gray-200">
            <div className="grid gap-6 lg:grid-cols-2">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                  Tracking Number
                </p>
                <p className="mt-2 text-2xl font-semibold text-mono-black">
                  {trackingData.tracking_number}
                </p>
                <div className={`mt-4 inline-block rounded-lg border px-4 py-2 text-sm font-semibold ${getStatusColor(trackingData.current_status)}`}>
                  {trackingData.current_status}
                </div>
              </div>
              <div className="space-y-4">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                    Origin
                  </p>
                  <p className="mt-1 text-sm text-mono-black">{trackingData.origin || 'N/A'}</p>
                </div>
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                    Destination
                  </p>
                  <p className="mt-1 text-sm text-mono-black">{trackingData.destination || 'N/A'}</p>
                </div>
                {trackingData.estimated_delivery && (
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                      Estimated Delivery
                    </p>
                    <p className="mt-1 text-sm text-mono-black">
                      {new Date(trackingData.estimated_delivery).toLocaleString()}
                    </p>
                  </div>
                )}
              </div>
            </div>
          </Card>

          {/* Shipment Details */}
          {trackingData.shipment_details && (
            <Card className="border border-mono-gray-200">
              <h3 className="text-lg font-semibold text-mono-black mb-4">Shipment Details</h3>
              <div className="grid gap-6 md:grid-cols-2">
                <div className="space-y-3">
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                      Sender
                    </p>
                    <p className="mt-1 text-sm text-mono-black">
                      {trackingData.shipment_details.sender_name || 'N/A'}
                    </p>
                    {trackingData.shipment_details.sender_phone && (
                      <p className="text-xs text-mono-gray-600">
                        {trackingData.shipment_details.sender_phone}
                      </p>
                    )}
                  </div>
                </div>
                <div className="space-y-3">
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                      Recipient
                    </p>
                    <p className="mt-1 text-sm text-mono-black">
                      {trackingData.shipment_details.recipient_name || 'N/A'}
                    </p>
                    {trackingData.shipment_details.recipient_phone && (
                      <p className="text-xs text-mono-gray-600">
                        {trackingData.shipment_details.recipient_phone}
                      </p>
                    )}
                  </div>
                </div>
                {(trackingData.shipment_details.weight || trackingData.shipment_details.pieces) && (
                  <div className="md:col-span-2 flex gap-6">
                    {trackingData.shipment_details.weight && (
                      <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                          Weight
                        </p>
                        <p className="mt-1 text-sm text-mono-black">
                          {trackingData.shipment_details.weight} kg
                        </p>
                      </div>
                    )}
                    {trackingData.shipment_details.pieces && (
                      <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                          Pieces
                        </p>
                        <p className="mt-1 text-sm text-mono-black">
                          {trackingData.shipment_details.pieces}
                        </p>
                      </div>
                    )}
                  </div>
                )}
              </div>
            </Card>
          )}

          {/* Tracking Timeline */}
          <Card className="border border-mono-gray-200">
            <h3 className="text-lg font-semibold text-mono-black mb-6">Shipment Journey</h3>
            <div className="space-y-6">
              {trackingData.events && trackingData.events.length > 0 ? (
                trackingData.events.map((event, index) => (
                  <div key={event.id || index} className="flex gap-4">
                    <div className="flex flex-col items-center">
                      <div className={`flex h-10 w-10 items-center justify-center rounded-full ${index === 0 ? 'bg-mono-black text-mono-white' : 'bg-mono-gray-200 text-mono-gray-600'}`}>
                        <i className={`fas ${index === 0 ? 'fa-circle' : 'fa-check'} text-sm`} aria-hidden="true" />
                      </div>
                      {index < trackingData.events.length - 1 && (
                        <div className="h-full w-0.5 bg-mono-gray-200 mt-2" style={{ minHeight: '40px' }} />
                      )}
                    </div>
                    <div className="flex-1 pb-6">
                      <div className="flex items-start justify-between">
                        <div>
                          <p className="font-semibold text-mono-black">{event.status}</p>
                          <p className="text-sm text-mono-gray-600">{event.location}</p>
                          {event.notes && (
                            <p className="text-xs text-mono-gray-500 mt-1">{event.notes}</p>
                          )}
                          {event.handled_by && (
                            <p className="text-xs text-mono-gray-500 mt-1">
                              Handled by: {event.handled_by}
                            </p>
                          )}
                        </div>
                        <div className="text-right">
                          <p className="text-xs text-mono-gray-500">
                            {new Date(event.timestamp).toLocaleDateString()}
                          </p>
                          <p className="text-xs text-mono-gray-500">
                            {new Date(event.timestamp).toLocaleTimeString()}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                ))
              ) : (
                <p className="text-center text-sm text-mono-gray-500 py-8">
                  No tracking events available yet.
                </p>
              )}
            </div>
          </Card>

          {/* Actions */}
          <Card className="border border-mono-gray-200">
            <div className="flex flex-wrap gap-4">
              <Button variant="secondary" size="sm" onClick={() => refetch()}>
                <i className="fas fa-sync-alt mr-2" aria-hidden="true" />
                Refresh Status
              </Button>
              <Button variant="secondary" size="sm" onClick={() => window.print()}>
                <i className="fas fa-print mr-2" aria-hidden="true" />
                Print Details
              </Button>
              <Button 
                variant="secondary" 
                size="sm"
                onClick={() => {
                  navigator.clipboard.writeText(trackingData.tracking_number);
                  alert('Tracking number copied to clipboard!');
                }}
              >
                <i className="fas fa-copy mr-2" aria-hidden="true" />
                Copy Tracking Number
              </Button>
            </div>
          </Card>
        </div>
      )}

      {/* Empty State */}
      {!searchedNumber && !isSearching && (
        <Card className="border border-mono-gray-200">
          <div className="py-12 text-center">
            <div className="inline-flex h-16 w-16 items-center justify-center rounded-full bg-mono-gray-100 text-mono-gray-600 mb-4">
              <i className="fas fa-search text-2xl" aria-hidden="true" />
            </div>
            <h3 className="text-lg font-semibold text-mono-black mb-2">
              Enter a Tracking Number
            </h3>
            <p className="text-sm text-mono-gray-600 max-w-md mx-auto">
              Enter your shipment tracking number in the search box above to view real-time tracking information.
            </p>
          </div>
        </Card>
      )}
    </div>
  );
};

export default LiveTracking;
