<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tracking {{ $shipment->tracking_number }} - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .timeline-line {
            position: absolute;
            left: 11px;
            top: 24px;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #3b82f6, transparent);
        }
        .timeline-dot {
            position: absolute;
            left: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .timeline-dot.completed { background: #10b981; }
        .timeline-dot.current { background: #3b82f6; animation: pulse 2s infinite; }
        .timeline-dot.pending { background: #475569; }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
        }
        .progress-bar {
            background: linear-gradient(90deg, #10b981, #3b82f6, #a855f7);
            background-size: 200% 100%;
            animation: gradient 3s ease infinite;
        }
        @keyframes gradient {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        #tracking-map { height: 300px; width: 100%; border-radius: 0.75rem; }
    </style>
</head>
<body class="text-white">
    {{-- Header --}}
    <header class="py-6 px-4 border-b border-white/10">
        <div class="max-w-5xl mx-auto flex items-center justify-between">
            <a href="/" class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-sky-500 to-blue-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <span class="text-xl font-bold">{{ config('app.name') }}</span>
            </a>
            <a href="{{ route('tracking.index') }}" class="text-sm text-slate-400 hover:text-white transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Track Another
            </a>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="max-w-5xl mx-auto px-4 py-8">
        {{-- Tracking Header --}}
        <div class="glass-card rounded-2xl p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="text-sm text-slate-400 mb-1">Tracking Number</div>
                    <div class="text-2xl font-bold font-mono text-sky-400">{{ $shipment->tracking_number }}</div>
                </div>
                <div class="flex items-center gap-4">
                    @php
                        $statusColors = [
                            'created' => 'bg-slate-500',
                            'picked_up' => 'bg-cyan-500',
                            'processing' => 'bg-blue-500',
                            'in_transit' => 'bg-sky-500',
                            'out_for_delivery' => 'bg-purple-500',
                            'delivered' => 'bg-emerald-500',
                            'cancelled' => 'bg-red-500',
                            'returned' => 'bg-amber-500',
                        ];
                        $statusColor = $statusColors[$shipment->status] ?? 'bg-slate-500';
                    @endphp
                    <span class="{{ $statusColor }} px-4 py-2 rounded-full text-sm font-semibold uppercase tracking-wider">
                        {{ str_replace('_', ' ', $shipment->status) }}
                    </span>
                </div>
            </div>

            {{-- Progress Bar --}}
            <div class="mt-6">
                <div class="flex justify-between text-xs text-slate-400 mb-2">
                    <span>Origin</span>
                    <span>Destination</span>
                </div>
                <div class="h-3 bg-white/10 rounded-full overflow-hidden">
                    <div class="progress-bar h-full rounded-full transition-all duration-1000" 
                         style="width: {{ $trackingData['shipment']['progress_percentage'] ?? 0 }}%"></div>
                </div>
                <div class="flex justify-between text-sm mt-2">
                    <span class="text-slate-300">{{ $trackingData['shipment']['origin'] ?? 'N/A' }}</span>
                    <span class="font-bold text-sky-400">{{ $trackingData['shipment']['progress_percentage'] ?? 0 }}%</span>
                    <span class="text-slate-300">{{ $trackingData['shipment']['destination'] ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            {{-- Left Column: ETA & Location --}}
            <div class="md:col-span-2 space-y-6">
                {{-- ETA Card --}}
                @if($trackingData['eta'])
                    <div class="glass-card rounded-2xl p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Estimated Delivery
                        </h3>
                        <div class="flex items-end gap-4">
                            @if($trackingData['eta']['actual'])
                                <div>
                                    <div class="text-sm text-slate-400">Delivered</div>
                                    <div class="text-2xl font-bold text-emerald-400">
                                        {{ \Carbon\Carbon::parse($trackingData['eta']['actual'])->format('M d, Y') }}
                                    </div>
                                    <div class="text-sm text-slate-400">
                                        at {{ \Carbon\Carbon::parse($trackingData['eta']['actual'])->format('h:i A') }}
                                    </div>
                                </div>
                            @elseif($trackingData['eta']['estimated'])
                                <div>
                                    <div class="text-sm text-slate-400">Expected by</div>
                                    <div class="text-2xl font-bold text-sky-400">
                                        {{ \Carbon\Carbon::parse($trackingData['eta']['estimated'])->format('M d, Y') }}
                                    </div>
                                    <div class="text-sm text-slate-400">
                                        by {{ \Carbon\Carbon::parse($trackingData['eta']['estimated'])->format('h:i A') }}
                                    </div>
                                </div>
                                @if(isset($trackingData['eta']['confidence']))
                                    <div class="flex items-center gap-2 px-3 py-1 rounded-full text-xs
                                        {{ $trackingData['eta']['confidence'] === 'high' ? 'bg-emerald-500/20 text-emerald-400' : 
                                           ($trackingData['eta']['confidence'] === 'medium' ? 'bg-amber-500/20 text-amber-400' : 'bg-slate-500/20 text-slate-400') }}">
                                        <span class="w-2 h-2 rounded-full 
                                            {{ $trackingData['eta']['confidence'] === 'high' ? 'bg-emerald-400' : 
                                               ($trackingData['eta']['confidence'] === 'medium' ? 'bg-amber-400' : 'bg-slate-400') }}"></span>
                                        {{ ucfirst($trackingData['eta']['confidence']) }} confidence
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Map --}}
                @if($trackingData['current_position'])
                    <div class="glass-card rounded-2xl overflow-hidden">
                        <div class="p-4 border-b border-white/10 flex items-center justify-between">
                            <h3 class="font-semibold flex items-center gap-2">
                                <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Current Location
                            </h3>
                            <span class="text-xs text-slate-400">
                                {{ $trackingData['current_position']['type'] === 'gps' ? 'GPS Location' : 'Branch Location' }}
                            </span>
                        </div>
                        <div id="tracking-map" class="bg-slate-800">
                            <div class="h-full flex items-center justify-center text-slate-500">
                                <div class="text-center">
                                    <svg class="w-12 h-12 mx-auto mb-2 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                    </svg>
                                    <p class="text-sm">Loading map...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Timeline --}}
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="text-lg font-semibold mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Shipment Journey
                    </h3>
                    
                    <div class="relative pl-10">
                        <div class="timeline-line"></div>
                        
                        @foreach($trackingData['timeline'] as $index => $milestone)
                            @php
                                $isLast = $loop->last;
                                $isCurrent = $milestone['completed'] && !($trackingData['timeline'][$index + 1]['completed'] ?? true);
                                $dotClass = $milestone['completed'] ? ($isCurrent ? 'current' : 'completed') : 'pending';
                            @endphp
                            <div class="relative pb-8 {{ $isLast ? 'pb-0' : '' }}">
                                <div class="timeline-dot {{ $dotClass }}">
                                    @if($milestone['completed'])
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    @endif
                                </div>
                                <div class="ml-2">
                                    <div class="font-semibold {{ $milestone['completed'] ? 'text-white' : 'text-slate-500' }}">
                                        {{ $milestone['label'] }}
                                    </div>
                                    @if($milestone['timestamp'])
                                        <div class="text-sm text-slate-400">
                                            {{ \Carbon\Carbon::parse($milestone['timestamp'])->format('M d, Y \a\t h:i A') }}
                                        </div>
                                    @else
                                        <div class="text-sm text-slate-500">Pending</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Right Column: Details & Notifications --}}
            <div class="space-y-6">
                {{-- Shipment Details --}}
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="text-lg font-semibold mb-4">Shipment Details</h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-slate-400">Current Location</dt>
                            <dd class="text-right font-medium">{{ $trackingData['shipment']['current_location'] ?? 'N/A' }}</dd>
                        </div>
                        @if($trackingData['shipment']['customer'])
                            <div class="flex justify-between">
                                <dt class="text-slate-400">Recipient</dt>
                                <dd class="text-right font-medium">{{ $trackingData['shipment']['customer'] }}</dd>
                            </div>
                        @endif
                        @if($shipment->waybill_number)
                            <div class="flex justify-between">
                                <dt class="text-slate-400">Waybill</dt>
                                <dd class="text-right font-mono text-sm">{{ $shipment->waybill_number }}</dd>
                            </div>
                        @endif
                        @if($shipment->service_level)
                            <div class="flex justify-between">
                                <dt class="text-slate-400">Service</dt>
                                <dd class="text-right capitalize">{{ $shipment->service_level }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- Notification Subscription --}}
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        Get Notifications
                    </h3>
                    <p class="text-sm text-slate-400 mb-4">Receive updates when your shipment status changes</p>
                    
                    <form id="notification-form" class="space-y-4">
                        <input type="hidden" name="tracking_number" value="{{ $shipment->tracking_number }}">
                        <div>
                            <label class="block text-sm text-slate-300 mb-1">Email</label>
                            <input type="email" name="email" placeholder="your@email.com"
                                   class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-sky-500">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-300 mb-1">Phone (SMS)</label>
                            <input type="tel" name="phone" placeholder="+256 700 000 000"
                                   class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-sky-500">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-300 mb-2">Notify me when:</label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="notification_types[]" value="status_change" checked
                                           class="w-4 h-4 rounded border-white/20 bg-white/10 text-sky-500 focus:ring-sky-500">
                                    <span class="text-sm">Status changes</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="notification_types[]" value="out_for_delivery" checked
                                           class="w-4 h-4 rounded border-white/20 bg-white/10 text-sky-500 focus:ring-sky-500">
                                    <span class="text-sm">Out for delivery</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="notification_types[]" value="delivered" checked
                                           class="w-4 h-4 rounded border-white/20 bg-white/10 text-sky-500 focus:ring-sky-500">
                                    <span class="text-sm">Delivered</span>
                                </label>
                            </div>
                        </div>
                        <button type="submit" 
                                class="w-full bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                            Subscribe
                        </button>
                    </form>
                    <div id="notification-success" class="hidden mt-4 text-sm text-emerald-400 text-center">
                        You've been subscribed to notifications!
                    </div>
                </div>

                {{-- Need Help? --}}
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="font-semibold mb-3">Need Help?</h3>
                    <p class="text-sm text-slate-400 mb-4">Contact our support team for assistance with your shipment</p>
                    <a href="mailto:support@{{ request()->host() }}" 
                       class="block w-full text-center bg-white/10 hover:bg-white/20 border border-white/20 text-white py-2 px-4 rounded-lg transition">
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="border-t border-white/10 py-8 px-4 mt-16">
        <div class="max-w-5xl mx-auto text-center text-sm text-slate-500">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Notification form handler
        document.getElementById('notification-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                tracking_number: formData.get('tracking_number'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                notification_types: formData.getAll('notification_types[]'),
            };
            
            try {
                const response = await fetch('{{ route("tracking.subscribe") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(data),
                });
                
                if (response.ok) {
                    document.getElementById('notification-success').classList.remove('hidden');
                    this.reset();
                }
            } catch (error) {
                console.error('Failed to subscribe:', error);
            }
        });
    </script>

    {{-- Google Maps for public tracking --}}
    @if(config('services.google_maps.api_key') && $trackingData['current_position'])
    <script>
        function initMap() {
            const position = { 
                lat: {{ $trackingData['current_position']['lat'] ?? 0 }}, 
                lng: {{ $trackingData['current_position']['lng'] ?? 0 }} 
            };
            
            const map = new google.maps.Map(document.getElementById('tracking-map'), {
                center: position,
                zoom: 14,
                styles: [
                    { elementType: 'geometry', stylers: [{ color: '#1e293b' }] },
                    { elementType: 'labels.text.fill', stylers: [{ color: '#94a3b8' }] },
                    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#334155' }] },
                    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#0f172a' }] },
                ],
                mapTypeControl: false,
                streetViewControl: false,
            });
            
            new google.maps.Marker({
                position: position,
                map: map,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    fillColor: '#3b82f6',
                    fillOpacity: 0.9,
                    strokeColor: '#ffffff',
                    strokeWeight: 2,
                    scale: 12,
                },
                title: '{{ $shipment->tracking_number }}',
            });
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&callback=initMap" async defer></script>
    @endif
</body>
</html>
