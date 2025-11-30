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
    <h3>Branch Handoff Manifest ({{ $branch->code ?? $branch->name }})</h3>
    <p class="muted">Includes handoffs involving this branch (outbound + inbound).</p>
    <table>
        <thead>
            <tr>
                <th>Handoff</th>
                <th>Tracking</th>
                <th>Origin → Destination</th>
                <th>Status</th>
                <th>Expected Handoff</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($handoffs as $handoff)
                <tr>
                    <td>#{{ $handoff->id }}</td>
                    <td>{{ $handoff->shipment?->tracking_number }}</td>
                    <td>{{ $handoff->originBranch?->code }} → {{ $handoff->destBranch?->code }}</td>
                    <td>{{ $handoff->status }}</td>
                    <td>{{ optional($handoff->expected_hand_off_at)->toDateTimeString() }}</td>
                    <td>{{ $handoff->notes }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted">No handoffs found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
