@extends('admin.layout')

@section('title', 'Live Tracking Dashboard')
@section('header', 'Real-Time Shipment Tracking')

@push('head')
<style>
    #tracking-map { height: 450px; width: 100%; }
    .shipment-marker { cursor: pointer; }
    .info-window { max-width: 300px; }
    .progress-bar { transition: width 0.5s ease-in-out; }
    .pulse-dot { animation: pulse 2s infinite; }
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.7; transform: scale(1.1); }
    }
    .status-in_transit { color: #3b82f6; }
    .status-out_for_delivery { color: #a855f7; }
    .status-delivered { color: #10b981; }
    .status-delayed { color: #f59e0b; }
</style>
@endpush

@section('content')
    {{-- Stats Cards --}}
    <div class="grid gap-3 md:grid-cols-4 mb-6">
        <div class="stat-card bg-gradient-to-r from-sky-500/10 to-sky-500/5 border border-sky-500/20">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase tracking-wider">In Transit</div>
                    <div class="text-3xl font-bold text-sky-400">{{ number_format($stats['in_transit']) }}</div>
                </div>
                <div class="w-12 h-12 rounded-full bg-sky-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="stat-card bg-gradient-to-r from-purple-500/10 to-purple-500/5 border border-purple-500/20">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase tracking-wider">Out for Delivery</div>
                    <div class="text-3xl font-bold text-purple-400">{{ number_format($stats['out_for_delivery']) }}</div>
                </div>
                <div class="w-12 h-12 rounded-full bg-purple-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="stat-card bg-gradient-to-r from-emerald-500/10 to-emerald-500/5 border border-emerald-500/20">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase tracking-wider">On Time</div>
                    <div class="text-3xl font-bold text-emerald-400">{{ number_format($stats['on_time']) }}</div>
                </div>
                <div class="w-12 h-12 rounded-full bg-emerald-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="stat-card bg-gradient-to-r from-amber-500/10 to-amber-500/5 border border-amber-500/20">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase tracking-wider">Delayed</div>
                    <div class="text-3xl font-bold text-amber-400">{{ number_format($stats['delayed']) }}</div>
                </div>
                <div class="w-12 h-12 rounded-full bg-amber-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Tracking Dashboard --}}
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Map View (2 columns) --}}
        <div class="lg:col-span-2 glass-panel">
            <div class="p-4 border-b border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="pulse-dot w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span class="text-sm font-semibold">Live Map</span>
                    <span class="text-xs muted ml-2" id="last-update">Last updated: just now</span>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="toggleClustering()" class="btn btn-xs btn-secondary" title="Toggle Clustering">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </button>
                    <button onclick="fitAllMarkers()" class="btn btn-xs btn-secondary" title="Fit All">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                        </svg>
                    </button>
                    <button onclick="refreshMap()" class="btn btn-xs btn-primary">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Refresh
                    </button>
                </div>
            </div>
            <div id="tracking-map" class="relative">
                {{-- Map will be rendered here --}}
                <div id="map-loading" class="absolute inset-0 flex items-center justify-center bg-obsidian-900 z-10">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-sky-500 mx-auto mb-4"></div>
                        <p class="text-slate-400">Initializing map...</p>
                    </div>
                </div>
            </div>
            
            {{-- Map Legend --}}
            <div class="p-3 border-t border-white/10 flex items-center gap-6 text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-sky-500"></span>
                    <span class="muted">In Transit</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-purple-500"></span>
                    <span class="muted">Out for Delivery</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                    <span class="muted">Delivered</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                    <span class="muted">Delayed</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-sm bg-slate-600"></span>
                    <span class="muted">Branch/Hub</span>
                </div>
            </div>
        </div>

        {{-- Active Shipments List (1 column) --}}
        <div class="glass-panel flex flex-col">
            <div class="p-4 border-b border-white/10 flex items-center justify-between">
                <div class="text-sm font-semibold">Active Shipments</div>
                <span class="badge badge-sm badge-secondary">{{ $activeShipments->count() }}</span>
            </div>
            <div class="overflow-y-auto flex-1" style="max-height: 500px;">
                @forelse($activeShipments as $shipment)
                    <div class="p-4 border-b border-white/5 hover:bg-white/5 cursor-pointer transition shipment-item" 
                         data-shipment-id="{{ $shipment->id }}"
                         data-lat="{{ $shipment->originBranch?->latitude ?? 0 }}"
                         data-lng="{{ $shipment->originBranch?->longitude ?? 0 }}"
                         onclick="focusShipment({{ $shipment->id }})">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <div class="font-mono text-sm font-semibold text-sky-400">{{ $shipment->tracking_number ?? '#' . $shipment->id }}</div>
                                <div class="text-xs muted mt-1 flex items-center gap-1">
                                    <span>{{ Str::limit($shipment->originBranch->name ?? 'N/A', 12) }}</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                    </svg>
                                    <span>{{ Str::limit($shipment->destBranch->name ?? 'N/A', 12) }}</span>
                                </div>
                            </div>
                            @php
                                $statusClass = match($shipment->status) {
                                    'in_transit' => 'badge-primary',
                                    'out_for_delivery' => 'bg-purple-500/20 text-purple-400',
                                    'delayed' => 'badge-warning',
                                    default => 'badge-secondary'
                                };
                            @endphp
                            <span class="badge badge-sm {{ $statusClass }}">
                                {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                            </span>
                        </div>
                        
                        {{-- Progress Bar --}}
                        <div class="w-full bg-white/5 rounded-full h-1.5 mt-2 overflow-hidden">
                            @php
                                $progress = match($shipment->status) {
                                    'created' => 10,
                                    'picked_up' => 25,
                                    'processing' => 40,
                                    'in_transit' => 60,
                                    'out_for_delivery' => 85,
                                    'delivered' => 100,
                                    default => 0
                                };
                                $progressColor = match($shipment->status) {
                                    'in_transit' => 'bg-sky-500',
                                    'out_for_delivery' => 'bg-purple-500',
                                    'delivered' => 'bg-emerald-500',
                                    default => 'bg-slate-500'
                                };
                            @endphp
                            <div class="{{ $progressColor }} h-1.5 rounded-full progress-bar" style="width: {{ $progress }}%"></div>
                        </div>
                        
                        {{-- ETA --}}
                        @if($shipment->expected_delivery_date)
                            <div class="text-xs muted mt-2 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                ETA: {{ $shipment->expected_delivery_date->format('M d, H:i') }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="p-8 text-center muted">
                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p>No active shipments</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="mt-6 flex gap-3 flex-wrap">
        <a href="{{ route('admin.shipments.index') }}" class="btn btn-secondary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
            All Shipments
        </a>
        <a href="{{ route('admin.pos.index') }}" class="btn btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Shipment POS
        </a>
        <a href="#" class="btn btn-secondary opacity-50 cursor-not-allowed" title="Coming Soon">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
            </svg>
            Manage Geofences
        </a>
    </div>
@endsection

@push('scripts')
{{-- Google Maps API --}}
<script>
    // Configuration
    const GOOGLE_MAPS_API_KEY = '{{ config('services.google_maps.api_key', '') }}';
    const MAP_CENTER = { lat: {{ config('services.google_maps.default_lat', 0.3476) }}, lng: {{ config('services.google_maps.default_lng', 32.5825) }} };
    const REFRESH_INTERVAL = 30000; // 30 seconds
    
    // Map state
    let map;
    let markers = {};
    let infoWindow;
    let markerClusterer;
    let clusteringEnabled = true;
    let refreshTimer;
    
    // Shipment data for markers
    @php
        $progressMap = [
            'created' => 10, 'picked_up' => 25, 'processing' => 40,
            'in_transit' => 60, 'out_for_delivery' => 85, 'delivered' => 100,
        ];
        $shipmentMapData = $activeShipments->map(function($s) use ($progressMap) {
            return [
                'id' => $s->id,
                'tracking_number' => $s->tracking_number,
                'status' => $s->status,
                'origin' => $s->originBranch?->name,
                'destination' => $s->destBranch?->name,
                'lat' => $s->originBranch?->latitude ?? 0,
                'lng' => $s->originBranch?->longitude ?? 0,
                'dest_lat' => $s->destBranch?->latitude ?? 0,
                'dest_lng' => $s->destBranch?->longitude ?? 0,
                'progress' => $progressMap[$s->status] ?? 0,
                'eta' => $s->expected_delivery_date?->format('M d, H:i'),
            ];
        })->values();
    @endphp
    const shipmentData = @json($shipmentMapData);

    // Initialize Google Maps
    function initMap() {
        const mapElement = document.getElementById('tracking-map');
        
        map = new google.maps.Map(mapElement, {
            center: MAP_CENTER,
            zoom: 10,
            styles: getMapStyles(),
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
        });
        
        infoWindow = new google.maps.InfoWindow();
        
        // Add shipment markers
        addShipmentMarkers();
        
        // Hide loading overlay
        document.getElementById('map-loading').style.display = 'none';
        
        // Start auto-refresh
        refreshTimer = setInterval(refreshMap, REFRESH_INTERVAL);
    }
    
    function addShipmentMarkers() {
        const bounds = new google.maps.LatLngBounds();
        const markersArray = [];
        
        shipmentData.forEach(shipment => {
            if (!shipment.lat || !shipment.lng) return;
            
            const position = { lat: parseFloat(shipment.lat), lng: parseFloat(shipment.lng) };
            const markerColor = getMarkerColor(shipment.status);
            
            const marker = new google.maps.Marker({
                position: position,
                map: clusteringEnabled ? null : map,
                icon: createMarkerIcon(markerColor),
                title: shipment.tracking_number,
            });
            
            marker.addListener('click', () => {
                const content = createInfoWindowContent(shipment);
                infoWindow.setContent(content);
                infoWindow.open(map, marker);
            });
            
            markers[shipment.id] = marker;
            markersArray.push(marker);
            bounds.extend(position);
            
            // Also add destination marker if different
            if (shipment.dest_lat && shipment.dest_lng) {
                const destPosition = { lat: parseFloat(shipment.dest_lat), lng: parseFloat(shipment.dest_lng) };
                bounds.extend(destPosition);
            }
        });
        
        // Initialize marker clusterer if enabled
        if (clusteringEnabled && markersArray.length > 0 && typeof MarkerClusterer !== 'undefined') {
            markerClusterer = new MarkerClusterer(map, markersArray, {
                imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m',
                maxZoom: 15,
            });
        } else {
            markersArray.forEach(m => m.setMap(map));
        }
        
        // Fit map to markers if any exist
        if (markersArray.length > 0) {
            map.fitBounds(bounds);
            if (map.getZoom() > 15) map.setZoom(15);
        }
    }
    
    function getMarkerColor(status) {
        const colors = {
            'in_transit': '#3b82f6',
            'out_for_delivery': '#a855f7',
            'delivered': '#10b981',
            'delayed': '#f59e0b',
            'created': '#64748b',
            'picked_up': '#06b6d4',
        };
        return colors[status] || '#64748b';
    }
    
    function createMarkerIcon(color) {
        return {
            path: google.maps.SymbolPath.CIRCLE,
            fillColor: color,
            fillOpacity: 0.9,
            strokeColor: '#ffffff',
            strokeWeight: 2,
            scale: 10,
        };
    }
    
    function createInfoWindowContent(shipment) {
        return `
            <div class="info-window p-2" style="color: #333; font-family: sans-serif;">
                <div style="font-weight: bold; color: #3b82f6; margin-bottom: 4px;">
                    ${shipment.tracking_number}
                </div>
                <div style="font-size: 12px; margin-bottom: 8px;">
                    <strong>Status:</strong> ${shipment.status.replace('_', ' ')}
                </div>
                <div style="font-size: 12px; margin-bottom: 4px;">
                    <strong>From:</strong> ${shipment.origin || 'N/A'}
                </div>
                <div style="font-size: 12px; margin-bottom: 8px;">
                    <strong>To:</strong> ${shipment.destination || 'N/A'}
                </div>
                ${shipment.eta ? `<div style="font-size: 12px;"><strong>ETA:</strong> ${shipment.eta}</div>` : ''}
                <div style="margin-top: 8px;">
                    <div style="background: #e5e7eb; border-radius: 4px; height: 6px; overflow: hidden;">
                        <div style="background: ${getMarkerColor(shipment.status)}; height: 100%; width: ${shipment.progress}%;"></div>
                    </div>
                    <div style="text-align: right; font-size: 10px; color: #666; margin-top: 2px;">
                        ${shipment.progress}%
                    </div>
                </div>
                <a href="/admin/tracking/${shipment.id}" 
                   style="display: inline-block; margin-top: 8px; color: #3b82f6; font-size: 12px; text-decoration: none;">
                    View Details &rarr;
                </a>
            </div>
        `;
    }
    
    function focusShipment(shipmentId) {
        const marker = markers[shipmentId];
        if (marker && map) {
            map.panTo(marker.getPosition());
            map.setZoom(14);
            google.maps.event.trigger(marker, 'click');
        }
    }
    
    function fitAllMarkers() {
        if (Object.keys(markers).length === 0) return;
        
        const bounds = new google.maps.LatLngBounds();
        Object.values(markers).forEach(marker => {
            bounds.extend(marker.getPosition());
        });
        map.fitBounds(bounds);
    }
    
    function toggleClustering() {
        clusteringEnabled = !clusteringEnabled;
        
        if (markerClusterer) {
            markerClusterer.clearMarkers();
        }
        
        if (clusteringEnabled) {
            const markersArray = Object.values(markers);
            markersArray.forEach(m => m.setMap(null));
            if (typeof MarkerClusterer !== 'undefined') {
                markerClusterer = new MarkerClusterer(map, markersArray, {
                    imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m',
                });
            }
        } else {
            Object.values(markers).forEach(m => m.setMap(map));
        }
    }
    
    function refreshMap() {
        const shipmentIds = Object.keys(markers);
        if (shipmentIds.length === 0) return;
        
        fetch('/admin/tracking/multiple', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ shipment_ids: shipmentIds.map(id => parseInt(id)) })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateMarkerPositions(data.data);
                document.getElementById('last-update').textContent = 'Last updated: just now';
            }
        })
        .catch(error => {
            console.error('Failed to refresh map:', error);
        });
    }
    
    function updateMarkerPositions(shipments) {
        shipments.forEach(shipment => {
            const marker = markers[shipment.id];
            if (marker && shipment.position) {
                const newPosition = new google.maps.LatLng(
                    shipment.position.lat,
                    shipment.position.lng
                );
                marker.setPosition(newPosition);
                marker.setIcon(createMarkerIcon(getMarkerColor(shipment.status)));
            }
        });
    }
    
    function getMapStyles() {
        // Dark mode map style
        return [
            { elementType: 'geometry', stylers: [{ color: '#1d2c4d' }] },
            { elementType: 'labels.text.fill', stylers: [{ color: '#8ec3b9' }] },
            { elementType: 'labels.text.stroke', stylers: [{ color: '#1a3646' }] },
            { featureType: 'administrative.country', elementType: 'geometry.stroke', stylers: [{ color: '#4b6878' }] },
            { featureType: 'administrative.land_parcel', elementType: 'labels.text.fill', stylers: [{ color: '#64779e' }] },
            { featureType: 'administrative.province', elementType: 'geometry.stroke', stylers: [{ color: '#4b6878' }] },
            { featureType: 'landscape.man_made', elementType: 'geometry.stroke', stylers: [{ color: '#334e87' }] },
            { featureType: 'landscape.natural', elementType: 'geometry', stylers: [{ color: '#023e58' }] },
            { featureType: 'poi', elementType: 'geometry', stylers: [{ color: '#283d6a' }] },
            { featureType: 'poi', elementType: 'labels.text.fill', stylers: [{ color: '#6f9ba5' }] },
            { featureType: 'poi.park', elementType: 'geometry.fill', stylers: [{ color: '#023e58' }] },
            { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#304a7d' }] },
            { featureType: 'road', elementType: 'labels.text.fill', stylers: [{ color: '#98a5be' }] },
            { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#2c6675' }] },
            { featureType: 'road.highway', elementType: 'geometry.stroke', stylers: [{ color: '#255763' }] },
            { featureType: 'transit', elementType: 'labels.text.fill', stylers: [{ color: '#98a5be' }] },
            { featureType: 'transit.line', elementType: 'geometry.fill', stylers: [{ color: '#283d6a' }] },
            { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#0e1626' }] },
            { featureType: 'water', elementType: 'labels.text.fill', stylers: [{ color: '#4e6d70' }] },
        ];
    }
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (refreshTimer) clearInterval(refreshTimer);
    });
</script>

{{-- Load Google Maps API --}}
@if(config('services.google_maps.api_key'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&callback=initMap" async defer></script>
<script src="https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js"></script>
@else
<script>
    // Fallback when no API key is configured
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('map-loading').innerHTML = `
            <div class="text-center p-8">
                <svg class="w-16 h-16 mx-auto mb-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                </svg>
                <p class="text-slate-400 mb-2">Google Maps API key not configured</p>
                <p class="text-xs text-slate-500">Add GOOGLE_MAPS_API_KEY to your .env file</p>
            </div>
        `;
    });
</script>
@endif
@endpush
