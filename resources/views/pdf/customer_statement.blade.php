<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Statement</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1a1a1a;
        }
        .statement-title {
            font-size: 16px;
            color: #666;
            margin-top: 5px;
        }
        .customer-info {
            margin-bottom: 20px;
        }
        .customer-info h3 {
            margin: 0 0 5px 0;
            font-size: 12px;
        }
        .period {
            background: #f5f5f5;
            padding: 10px;
            margin-bottom: 20px;
        }
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-item {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        .summary-label {
            font-size: 8px;
            color: #666;
            text-transform: uppercase;
        }
        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background: #333;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9px;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .amount {
            text-align: right;
            font-family: monospace;
        }
        .debit {
            color: #c00;
        }
        .credit {
            color: #060;
        }
        .balance {
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 8px;
            color: #666;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        .balance-due {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">BARAKA COURIER</div>
        <div class="statement-title">Account Statement</div>
    </div>

    <div class="customer-info">
        <h3>{{ $customer->company_name ?? $customer->contact_person }}</h3>
        <div>{{ $customer->email }}</div>
        <div>{{ $customer->phone }}</div>
        @if($customer->billing_address)
            <div>{{ $customer->billing_address }}</div>
        @endif
        <div>Customer Code: {{ $customer->customer_code }}</div>
    </div>

    <div class="period">
        <strong>Statement Period:</strong> 
        {{ $period['start']->format('F d, Y') }} - {{ $period['end']->format('F d, Y') }}
    </div>

    <div class="summary-grid">
        <div class="summary-item">
            <div class="summary-label">Opening Balance</div>
            <div class="summary-value">{{ number_format($summary['opening_balance'], 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Total Invoiced</div>
            <div class="summary-value">{{ number_format($summary['total_invoiced'], 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Total Paid</div>
            <div class="summary-value">{{ number_format($summary['total_paid'], 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Closing Balance</div>
            <div class="summary-value">{{ number_format($summary['closing_balance'], 2) }}</div>
        </div>
    </div>

    <div class="section-title">Transaction Ledger</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Description</th>
                <th style="text-align: right;">Debit</th>
                <th style="text-align: right;">Credit</th>
                <th style="text-align: right;">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $period['start']->format('Y-m-d') }}</td>
                <td>-</td>
                <td>Opening Balance</td>
                <td class="amount">-</td>
                <td class="amount">-</td>
                <td class="amount balance">{{ number_format($summary['opening_balance'], 2) }}</td>
            </tr>
            @foreach($transactions as $tx)
                <tr>
                    <td>{{ $tx['date']->format('Y-m-d') }}</td>
                    <td>{{ $tx['reference'] }}</td>
                    <td>{{ $tx['description'] }}</td>
                    <td class="amount debit">{{ $tx['debit'] > 0 ? number_format($tx['debit'], 2) : '-' }}</td>
                    <td class="amount credit">{{ $tx['credit'] > 0 ? number_format($tx['credit'], 2) : '-' }}</td>
                    <td class="amount balance">{{ number_format($tx['balance'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($shipments->count())
        <div class="section-title">Shipment Summary ({{ $shipments->count() }} shipments)</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>AWB/Tracking</th>
                    <th>Origin</th>
                    <th>Destination</th>
                    <th>Status</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shipments->take(20) as $shipment)
                    <tr>
                        <td>{{ $shipment->created_at->format('Y-m-d') }}</td>
                        <td>{{ $shipment->tracking_number ?? $shipment->waybill_number ?? "SHP-{$shipment->id}" }}</td>
                        <td>{{ $shipment->originBranch?->name ?? '-' }}</td>
                        <td>{{ $shipment->destBranch?->name ?? '-' }}</td>
                        <td>{{ $shipment->current_status?->value ?? $shipment->status }}</td>
                        <td class="amount">{{ number_format($shipment->price_amount ?? 0, 2) }}</td>
                    </tr>
                @endforeach
                @if($shipments->count() > 20)
                    <tr>
                        <td colspan="6" style="text-align: center; font-style: italic;">
                            ... and {{ $shipments->count() - 20 }} more shipments
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif

    <div class="balance-due">
        Balance Due: USD {{ number_format($summary['closing_balance'], 2) }}
    </div>

    <div class="footer">
        <div>Generated: {{ $generated_at->format('F d, Y H:i:s') }}</div>
        <div>This is a computer-generated statement. For queries, contact accounts@baraka.co</div>
    </div>
</body>
</html>
