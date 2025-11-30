@extends('branch.layout')

@section('content')
<div class="p-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-100">Shipments</h1>
            <p class="text-sm text-slate-400">Branch-scoped list of outbound/inbound pieces.</p>
        </div>
        <div>
            <a href="{{ route('branch.pos.index') }}" class="px-3 py-2 text-sm font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-500">Shipment POS</a>
        </div>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-lg shadow-sm">
        <div class="p-4 flex justify-between items-center text-sm flex-wrap gap-2">
            <div class="text-slate-300">Batch actions</div>
            <form method="get" action="{{ route('branch.shipments.labels') }}" class="flex flex-wrap items-center gap-2 text-xs">
                <input type="text" name="ids" placeholder="IDs comma separated (blank = recent 100)" class="bg-slate-800 border border-slate-700 rounded px-2 py-1">
                <select name="format" class="bg-slate-800 border border-slate-700 rounded px-2 py-1">
                    <option value="html">HTML</option>
                    <option value="pdf">PDF</option>
                    <option value="zpl">ZPL</option>
                </select>
                <button class="chip text-xs">Batch labels</button>
            </form>
        </div>
        <table class="min-w-full divide-y divide-slate-800">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-400 bg-slate-800/50">
                    <th class="px-4 py-3">Tracking</th>
                    <th class="px-4 py-3">Origin → Destination</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Expected</th>
                    <th class="px-4 py-3">Updated</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800 text-sm text-slate-200">
                @forelse ($shipments as $shipment)
                    <tr class="hover:bg-slate-800/50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $shipment->tracking_number }}</td>
                        <td class="px-4 py-3">
                            <div class="text-slate-100">{{ $shipment->originBranch?->code ?? 'N/A' }} → {{ $shipment->destBranch?->code ?? 'N/A' }}</div>
                            <div class="text-xs text-slate-400">{{ $shipment->client?->name ?? 'Walk-in' }}</div>
                        </td>
                        <td class="px-4 py-3 text-xs">
                            <span class="px-2 py-1 rounded bg-slate-800 border border-slate-700">{{ $shipment->current_status }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-300">
                            {{ optional($shipment->expected_delivery_date)->format('Y-m-d H:i') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-400">
                            {{ optional($shipment->updated_at)->diffForHumans() }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('branch.shipments.show', $shipment) }}" class="text-indigo-400 hover:text-indigo-200 text-xs">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-400">No shipments for this branch yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3">
            {{ $shipments->links() }}
        </div>
    </div>
</div>
@endsection
