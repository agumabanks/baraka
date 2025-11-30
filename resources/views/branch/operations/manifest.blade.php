<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
    </style>
</head>
<body>
    <h3>Handoff Manifest #{{ $handoff->id }}</h3>
    <p>From {{ $handoff->originBranch?->code ?? $handoff->origin_branch_id }} to {{ $handoff->destBranch?->code ?? $handoff->dest_branch_id }}</p>
    <p>Status: {{ $handoff->status }} | Expected handoff: {{ $handoff->expected_hand_off_at ?? 'N/A' }}</p>
    @if($handoff->approved_at)
        <p>Approved at {{ $handoff->approved_at }} by {{ $handoff->approver?->name ?? 'N/A' }}</p>
    @endif
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
            <tr>
                <td>{{ $shipment->tracking_number }}</td>
                <td>{{ $shipment->originBranch?->code }}</td>
                <td>{{ $shipment->destBranch?->code }}</td>
                <td>{{ $shipment->current_status }}</td>
                <td>{{ $shipment->expected_delivery_date }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
