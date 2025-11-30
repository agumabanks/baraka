<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt - {{ $shipment->tracking_number }}</title>
    <style>
        @page { margin: 5mm; size: 80mm auto; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', 'Consolas', monospace;
            font-size: 11px;
            width: 72mm;
            margin: 0 auto;
            padding: 5mm;
            color: #000;
            background: #fff;
            line-height: 1.4;
        }
        
        .receipt-header {
            text-align: center;
            padding-bottom: 8px;
            border-bottom: 2px solid #000;
            margin-bottom: 8px;
        }
        .company-logo {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 4px;
        }
        .company-tagline {
            font-size: 9px;
            color: #666;
            margin-bottom: 4px;
        }
        .branch-info {
            font-size: 9px;
            color: #333;
        }
        
        .receipt-type {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            padding: 8px 0;
            background: #000;
            color: #fff;
            margin: 8px -5mm;
            letter-spacing: 2px;
        }
        
        .tracking-section {
            text-align: center;
            padding: 12px 0;
            border-bottom: 1px dashed #000;
        }
        .tracking-label {
            font-size: 9px;
            color: #666;
            margin-bottom: 4px;
        }
        .tracking-number {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }
        .barcode-container {
            margin: 8px 0;
        }
        .datetime {
            font-size: 10px;
            color: #333;
        }
        
        .section {
            padding: 10px 0;
            border-bottom: 1px dashed #ccc;
        }
        .section:last-of-type {
            border-bottom: none;
        }
        .section-title {
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
            color: #333;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
            font-size: 10px;
        }
        .info-label {
            color: #666;
        }
        .info-value {
            font-weight: bold;
            text-align: right;
            max-width: 55%;
            word-wrap: break-word;
        }
        
        .address-block {
            font-size: 10px;
            line-height: 1.3;
            margin: 4px 0;
        }
        .address-name {
            font-weight: bold;
        }
        
        .charges-section {
            background: #f5f5f5;
            margin: 0 -5mm;
            padding: 10px 5mm;
        }
        .charge-row {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
            font-size: 10px;
        }
        .charge-total {
            border-top: 2px solid #000;
            padding-top: 8px;
            margin-top: 8px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .payment-section {
            text-align: center;
            padding: 10px 0;
            border-bottom: 1px dashed #000;
        }
        .payment-method {
            font-size: 10px;
            margin-bottom: 4px;
        }
        .payment-amount {
            font-size: 16px;
            font-weight: bold;
        }
        .payment-status {
            display: inline-block;
            background: #000;
            color: #fff;
            padding: 2px 8px;
            font-size: 9px;
            font-weight: bold;
            margin-top: 4px;
        }
        
        .cod-box {
            border: 2px solid #000;
            padding: 10px;
            margin: 10px 0;
            text-align: center;
        }
        .cod-label {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .cod-amount {
            font-size: 20px;
            font-weight: bold;
        }
        
        .qr-section {
            text-align: center;
            padding: 12px 0;
        }
        .qr-code {
            margin: 8px auto;
        }
        .track-url {
            font-size: 9px;
            color: #666;
        }
        
        .footer {
            text-align: center;
            padding-top: 10px;
            border-top: 2px solid #000;
            margin-top: 10px;
        }
        .thank-you {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .footer-info {
            font-size: 8px;
            color: #666;
            margin: 2px 0;
        }
        .footer-line {
            border-top: 1px dashed #ccc;
            margin: 8px 0;
        }
        
        .signature-line {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #000;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        
        .copy-type {
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            color: #999;
            margin-top: 10px;
        }
        
        @media print {
            body { 
                width: 100%; 
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="receipt-header">
        <div class="company-logo">{{ strtoupper($company['name']) }}</div>
        <div class="company-tagline">Fast. Reliable. Professional.</div>
        <div class="branch-info">
            {{ $company['address'] }}<br>
            Tel: {{ $company['phone'] }}
        </div>
    </div>
    
    {{-- Receipt Type --}}
    <div class="receipt-type">SHIPMENT RECEIPT</div>
    
    {{-- Tracking Section --}}
    <div class="tracking-section">
        <div class="tracking-label">TRACKING NUMBER</div>
        <div class="tracking-number">{{ $shipment->tracking_number }}</div>
        <div class="barcode-container">
            <svg id="barcode"></svg>
        </div>
        <div class="datetime">
            {{ $transaction_date->format('l, F d, Y') }}<br>
            {{ $transaction_date->format('h:i:s A') }}
        </div>
    </div>
    
    {{-- Sender Information --}}
    <div class="section">
        <div class="section-title">Sender</div>
        <div class="address-block">
            <div class="address-name">{{ $shipment->customer?->name ?? 'Walk-in Customer' }}</div>
            @if($shipment->customer?->phone)
            <div>{{ $shipment->customer->phone }}</div>
            @endif
            @if($shipment->customer?->email)
            <div>{{ $shipment->customer->email }}</div>
            @endif
        </div>
    </div>
    
    {{-- Receiver Information --}}
    <div class="section">
        <div class="section-title">Receiver</div>
        <div class="address-block">
            <div class="address-name">{{ $shipment->metadata['receiver_name'] ?? 'To Be Advised' }}</div>
            @if(!empty($shipment->metadata['receiver_phone']))
            <div>{{ $shipment->metadata['receiver_phone'] }}</div>
            @endif
            @if(!empty($shipment->metadata['delivery_address']))
            <div style="margin-top: 4px;">{{ $shipment->metadata['delivery_address'] }}</div>
            @endif
        </div>
    </div>
    
    {{-- Shipment Details --}}
    <div class="section">
        <div class="section-title">Shipment Details</div>
        <div class="info-row">
            <span class="info-label">Waybill #:</span>
            <span class="info-value">{{ $shipment->waybill_number ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Origin:</span>
            <span class="info-value">{{ $shipment->originBranch?->name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Destination:</span>
            <span class="info-value">{{ $shipment->destBranch?->name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Service:</span>
            <span class="info-value">{{ strtoupper($shipment->service_level ?? 'STANDARD') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Weight:</span>
            <span class="info-value">{{ number_format($shipment->chargeable_weight_kg ?? 0, 2) }} KG</span>
        </div>
        <div class="info-row">
            <span class="info-label">Pieces:</span>
            <span class="info-value">{{ $shipment->metadata['pieces'] ?? 1 }}</span>
        </div>
        @if(!empty($shipment->metadata['description']))
        <div class="info-row">
            <span class="info-label">Contents:</span>
            <span class="info-value">{{ Str::limit($shipment->metadata['description'], 25) }}</span>
        </div>
        @endif
        @if($shipment->metadata['is_fragile'] ?? false)
        <div class="info-row">
            <span class="info-label">⚠️ FRAGILE</span>
            <span class="info-value">Handle with care</span>
        </div>
        @endif
    </div>
    
    {{-- Charges --}}
    <div class="charges-section">
        <div class="section-title">Charges</div>
        @php
            $pricing = $shipment->metadata['pricing_breakdown'] ?? [];
        @endphp
        <div class="charge-row">
            <span>Base Rate:</span>
            <span>${{ number_format($pricing['base_rate'] ?? 0, 2) }}</span>
        </div>
        <div class="charge-row">
            <span>Weight Charge:</span>
            <span>${{ number_format($pricing['weight_charge'] ?? 0, 2) }}</span>
        </div>
        @if(($pricing['surcharges']['total'] ?? 0) > 0)
        <div class="charge-row">
            <span>Surcharges:</span>
            <span>${{ number_format($pricing['surcharges']['total'], 2) }}</span>
        </div>
        @endif
        @if(($pricing['insurance']['amount'] ?? 0) > 0)
        <div class="charge-row">
            <span>Insurance:</span>
            <span>${{ number_format($pricing['insurance']['amount'], 2) }}</span>
        </div>
        @endif
        @if(($pricing['cod_fee'] ?? 0) > 0)
        <div class="charge-row">
            <span>COD Fee:</span>
            <span>${{ number_format($pricing['cod_fee'], 2) }}</span>
        </div>
        @endif
        @if(($pricing['tax'] ?? 0) > 0)
        <div class="charge-row">
            <span>Tax (18%):</span>
            <span>${{ number_format($pricing['tax'], 2) }}</span>
        </div>
        @endif
        <div class="charge-row charge-total">
            <span>TOTAL:</span>
            <span>${{ number_format($shipment->price_amount ?? 0, 2) }}</span>
        </div>
    </div>
    
    {{-- Payment --}}
    <div class="payment-section">
        <div class="payment-method">
            Payment: {{ strtoupper(str_replace('_', ' ', $shipment->metadata['payment_method'] ?? 'CASH')) }}
        </div>
        @if(($shipment->metadata['amount_received'] ?? 0) > 0)
        <div class="payment-amount">
            Received: ${{ number_format($shipment->metadata['amount_received'], 2) }}
        </div>
        @if(($shipment->metadata['amount_received'] ?? 0) > ($shipment->price_amount ?? 0))
        <div style="margin-top: 4px;">
            Change: ${{ number_format(($shipment->metadata['amount_received'] ?? 0) - ($shipment->price_amount ?? 0), 2) }}
        </div>
        @endif
        @endif
        <div class="payment-status">PAID</div>
    </div>
    
    {{-- COD Box (if applicable) --}}
    @if(($shipment->metadata['cod_amount'] ?? 0) > 0)
    <div class="cod-box">
        <div class="cod-label">⚠️ COLLECT ON DELIVERY</div>
        <div class="cod-amount">${{ number_format($shipment->metadata['cod_amount'], 2) }}</div>
        <div style="font-size: 9px; margin-top: 4px;">Collect this amount from receiver</div>
    </div>
    @endif
    
    {{-- QR Code for Tracking --}}
    <div class="qr-section">
        <div style="font-size: 9px; margin-bottom: 4px;">Scan to track your shipment</div>
        <div class="qr-code">
            <svg id="qrcode" width="80" height="80"></svg>
        </div>
        <div class="track-url">{{ config('app.url') }}/track/{{ $shipment->tracking_number }}</div>
    </div>
    
    {{-- Terms --}}
    <div style="font-size: 8px; color: #666; text-align: center; padding: 8px 0; border-top: 1px dashed #ccc;">
        <strong>Terms & Conditions:</strong><br>
        Liability limited to declared value. Claims within 7 days.<br>
        Prohibited items not accepted. Subject to inspection.
    </div>
    
    {{-- Footer --}}
    <div class="footer">
        <div class="thank-you">Thank You!</div>
        <div class="footer-info">Cashier: {{ $cashier }}</div>
        <div class="footer-info">Trans ID: {{ str_pad($shipment->id, 8, '0', STR_PAD_LEFT) }}</div>
        <div class="footer-line"></div>
        <div class="footer-info">Customer Support: support@baraka.co</div>
        <div class="footer-info">www.baraka.co</div>
    </div>
    
    {{-- Copy Type --}}
    <div class="copy-type">*** CUSTOMER COPY ***</div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Generate barcode
            try {
                JsBarcode("#barcode", "{{ $shipment->tracking_number }}", {
                    format: "CODE128",
                    width: 1.5,
                    height: 35,
                    displayValue: false,
                    margin: 0
                });
            } catch(e) {
                console.log('Barcode generation failed');
            }
            
            // Generate QR code
            try {
                var qr = qrcode(0, 'M');
                qr.addData('{{ config('app.url') }}/track/{{ $shipment->tracking_number }}');
                qr.make();
                document.getElementById('qrcode').innerHTML = qr.createSvgTag({
                    cellSize: 2,
                    margin: 0
                });
            } catch(e) {
                console.log('QR generation failed');
            }
            
            // Auto-print after short delay
            setTimeout(function() { 
                window.print(); 
            }, 800);
        });
    </script>
</body>
</html>
