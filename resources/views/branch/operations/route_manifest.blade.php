<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
        .muted { color: #444; font-size: 11px; }
    </style>
</head>
<body>
    <h3>Route Manifest ({{ $branch->code ?? $branch->name }})</h3>
    <p class="muted">Statuses: {{ implode(', ', $statuses) }}</p>
    <table>
        <thead>
            <tr>
                <th>Tracking</th>
                <th>Origin</th>
                <th>Destination</th>
                <th>Status</th>
                <th>Expected Delivery</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shipments as $shipment)
                <tr>
                    <td>{{ $shipment->tracking_number }}</td>
                    <td>{{ $shipment->originBranch?->code }}</td>
                    <td>{{ $shipment->destBranch?->code }}</td>
                    <td>{{ $shipment->current_status }}</td>
                    <td>{{ optional($shipment->expected_delivery_date)->toDateTimeString() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="muted">No shipments matched the route filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
