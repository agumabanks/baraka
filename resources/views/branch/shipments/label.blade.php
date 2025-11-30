<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shipment Label - {{ $shipment->tracking_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 11px; padding: 10px; background: #fff; }
        .label { 
            border: 2px solid #000; 
            width: 4in; 
            padding: 8px;
            page-break-inside: avoid;
        }
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start;
            border-bottom: 2px solid #000; 
            padding-bottom: 6px; 
            margin-bottom: 6px; 
        }
        .logo-section { display: flex; align-items: center; gap: 8px; }
        .logo { font-size: 18px; font-weight: bold; color: #c41230; }
        .tracking-section { text-align: right; }
        .tracking-number { font-size: 14px; font-weight: bold; font-family: monospace; }
        .waybill { font-size: 9px; color: #666; }
        
        .route-section {
            display: flex;
            justify-content: space-between;
            background: #f5f5f5;
            padding: 8px;
            margin-bottom: 6px;
            border-radius: 4px;
        }
        .origin, .destination { text-align: center; flex: 1; }
        .branch-code { font-size: 20px; font-weight: bold; }
        .branch-name { font-size: 8px; color: #666; }
        .arrow { 
            display: flex; 
            align-items: center; 
            font-size: 20px; 
            color: #c41230;
            padding: 0 10px;
        }
        
        .barcode-section { 
            text-align: center; 
            padding: 10px 0;
            border-top: 1px dashed #ccc;
            border-bottom: 1px dashed #ccc;
            margin: 6px 0;
        }
        .barcode-text { font-family: monospace; font-size: 12px; margin-top: 4px; letter-spacing: 2px; }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-bottom: 6px;
        }
        .detail-item { }
        .detail-label { font-size: 8px; color: #666; text-transform: uppercase; }
        .detail-value { font-size: 11px; font-weight: 600; }
        
        .service-badge {
            display: inline-block;
            background: #c41230;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .service-badge.express { background: #f59e0b; }
        .service-badge.priority { background: #ef4444; }
        .service-badge.economy { background: #6b7280; }
        
        .sender-receiver {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            border-top: 1px solid #ddd;
            padding-top: 6px;
            margin-top: 6px;
            font-size: 9px;
        }
        .person-label { font-size: 7px; color: #666; text-transform: uppercase; margin-bottom: 2px; }
        .person-name { font-weight: 600; }
        .person-phone { color: #666; }
        
        .footer { 
            border-top: 1px solid #ddd; 
            padding-top: 4px; 
            margin-top: 6px;
            font-size: 8px; 
            color: #666; 
            display: flex;
            justify-content: space-between;
        }
        .track-url { font-family: monospace; font-size: 7px; }
        
        @media print {
            body { padding: 0; }
            .label { border: 2px solid #000; }
        }
    </style>
</head>
<body>
    <div class="label">
        {{-- Header --}}
        <div class="header">
            <div class="logo-section">
                <div class="logo">BARAKA</div>
                <span class="service-badge {{ strtolower($shipment->service_level ?? 'standard') }}">
                    {{ strtoupper($shipment->service_level ?? 'STANDARD') }}
                </span>
            </div>
            <div class="tracking-section">
                <div class="tracking-number">{{ $shipment->tracking_number }}</div>
                @if($shipment->waybill_number)
                    <div class="waybill">WB: {{ $shipment->waybill_number }}</div>
                @endif
            </div>
        </div>

        {{-- Route Section --}}
        <div class="route-section">
            <div class="origin">
                <div class="branch-code">{{ $shipment->originBranch?->code ?? 'N/A' }}</div>
                <div class="branch-name">{{ $shipment->originBranch?->name ?? 'Origin' }}</div>
            </div>
            <div class="arrow">→</div>
            <div class="destination">
                <div class="branch-code">{{ $shipment->destBranch?->code ?? 'N/A' }}</div>
                <div class="branch-name">{{ $shipment->destBranch?->name ?? 'Destination' }}</div>
            </div>
        </div>

        {{-- Barcode --}}
        <div class="barcode-section">
            @if(class_exists('DNS1D'))
                {!! DNS1D::getBarcodeHTML($shipment->tracking_number, 'C128', 2, 50, 'black', true) !!}
            @else
                <div style="font-family: 'Libre Barcode 128', monospace; font-size: 48px;">{{ $shipment->tracking_number }}</div>
            @endif
            <div class="barcode-text">{{ $shipment->tracking_number }}</div>
        </div>

        {{-- Details Grid --}}
        <div class="details-grid">
            <div class="detail-item">
                <div class="detail-label">Weight</div>
                <div class="detail-value">{{ number_format($shipment->chargeable_weight_kg ?? $shipment->weight ?? $shipment->actual_weight ?? 0, 2) }} KG</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Pieces</div>
                <div class="detail-value">{{ $shipment->pieces ?? $shipment->parcel_count ?? 1 }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Value</div>
                <div class="detail-value">{{ $shipment->currency ?? 'UGX' }} {{ number_format($shipment->declared_value ?? $shipment->total_amount ?? $shipment->amount ?? 0) }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">COD</div>
                <div class="detail-value">{{ $shipment->cod_amount ? ($shipment->currency ?? 'UGX') . ' ' . number_format($shipment->cod_amount) : 'N/A' }}</div>
            </div>
        </div>

        {{-- Sender & Receiver --}}
        <div class="sender-receiver">
            <div class="sender">
                <div class="person-label">From / Sender</div>
                <div class="person-name">{{ $shipment->customer?->name ?? $shipment->sender_name ?? 'Walk-in' }}</div>
                <div class="person-phone">{{ $shipment->customer?->mobile ?? $shipment->sender_phone ?? '' }}</div>
            </div>
            <div class="receiver">
                <div class="person-label">To / Receiver</div>
                <div class="person-name">{{ $shipment->receiver_name ?? 'N/A' }}</div>
                <div class="person-phone">{{ $shipment->receiver_phone ?? '' }}</div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <div>
                <span>Track: </span>
                <span class="track-url">{{ url('/tracking/' . $shipment->tracking_number) }}</span>
            </div>
            <div>{{ now()->format('d M Y H:i') }}</div>
        </div>
    </div>

    <script>
        // Auto-print on load if ?print=1
        if (window.location.search.includes('print=1')) {
            window.onload = function() { window.print(); }
        }
    </script>
</body>
</html>
