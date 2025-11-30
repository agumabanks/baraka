@extends('branch.layout')

@section('content')
<div class="p-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-100">Shipment {{ $shipment->tracking_number }}</h1>
            <p class="text-sm text-slate-400">Origin {{ $shipment->originBranch?->code ?? 'N/A' }} → Destination {{ $shipment->destBranch?->code ?? 'N/A' }}</p>
        </div>
        <div class="text-xs text-slate-300 space-y-2 text-right">
            <div>Status: <span class="px-2 py-1 rounded bg-slate-800 border border-slate-700">{{ $shipment->current_status }}</span></div>
            <a href="{{ route('branch.shipments.label', $shipment) }}" class="chip text-2xs inline-flex">Print label</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-slate-900 border border-slate-800 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-slate-200 mb-3">Details</h3>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-slate-300">
                    <div>
                        <dt class="text-slate-400">Client</dt>
                        <dd>{{ $shipment->client?->name ?? 'Walk-in' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Service level</dt>
                        <dd>{{ $shipment->service_level ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Booked at</dt>
                        <dd>{{ optional($shipment->booked_at)->format('Y-m-d H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Expected delivery</dt>
                        <dd>{{ optional($shipment->expected_delivery_date)->format('Y-m-d H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-slate-200 mb-3">Parcels</h3>
                <div class="text-sm text-slate-300 space-y-2">
                    @forelse ($shipment->parcels as $parcel)
                        <div class="flex items-center justify-between rounded border border-slate-800 bg-slate-800/40 px-3 py-2">
                            <div>
                                <div class="text-slate-100">Parcel #{{ $parcel->id }}</div>
                                <div class="text-xs text-slate-400">{{ $parcel->weight_kg }} kg · {{ $parcel->length_cm }}x{{ $parcel->width_cm }}x{{ $parcel->height_cm }} cm</div>
                            </div>
                            <div class="text-xs text-slate-400">{{ $parcel->barcode }}</div>
                        </div>
                    @empty
                        <p class="text-slate-400 text-sm">No parcels recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-slate-900 border border-slate-800 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-slate-200 mb-3">Milestones</h3>
                <dl class="space-y-2 text-xs text-slate-300">
                    <div class="flex justify-between">
                        <dt>Booked</dt>
                        <dd>{{ optional($shipment->booked_at)->diffForHumans() ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Pickup scheduled</dt>
                        <dd>{{ optional($shipment->pickup_scheduled_at)->diffForHumans() ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Picked up</dt>
                        <dd>{{ optional($shipment->picked_up_at)->diffForHumans() ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Out for delivery</dt>
                        <dd>{{ optional($shipment->out_for_delivery_at)->diffForHumans() ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Delivered</dt>
                        <dd>{{ optional($shipment->delivered_at)->diffForHumans() ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-slate-200 mb-3">Events</h3>
                <div class="space-y-2 text-xs text-slate-300">
                    @forelse ($shipment->shipmentEvents as $event)
                        <div class="flex items-center justify-between border border-slate-800 rounded px-2 py-2 bg-slate-800/30">
                            <div>
                                <div class="text-slate-100">{{ $event->event_code }}</div>
                                <div class="text-slate-400">{{ $event->description }}</div>
                            </div>
                            <div class="text-slate-500">{{ optional($event->occurred_at)->diffForHumans() }}</div>
                        </div>
                    @empty
                        <p class="text-slate-400">No events recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
