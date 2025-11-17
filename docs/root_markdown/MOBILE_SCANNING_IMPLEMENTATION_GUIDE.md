# Mobile Scanning & Workflow Implementation Guide

## Overview

This guide covers the complete implementation of mobile scanning and workflow automation for the Baraka Logistics Platform. The system includes enhanced API endpoints, PWA mobile interface, real-time updates, and automated workflow processing.

## Table of Contents

1. [System Architecture](#system-architecture)
2. [API Documentation](#api-documentation)
3. [Frontend Integration](#frontend-integration)
4. [Database Setup](#database-setup)
5. [PWA Deployment](#pwa-deployment)
6. [WebSocket Configuration](#websocket-configuration)
7. [Workflow Automation](#workflow-automation)
8. [Testing](#testing)
9. [Monitoring & Logging](#monitoring--logging)
10. [Troubleshooting](#troubleshooting)

## System Architecture

### Components Overview

```
┌─────────────────────┐    ┌─────────────────────┐
│   Mobile PWA        │    │   Backend API       │
│   - Camera Scan     │◄──►│   - Enhanced APIs   │
│   - Offline Sync    │    │   - Workflow Jobs   │
│   - WebSocket       │    │   - WebSocket       │
└─────────────────────┘    └─────────────────────┘
                                       │
                                       ▼
                          ┌─────────────────────┐
                          │   Database          │
                          │   - Scans           │
                          │   - Shipments       │
                          │   - Devices         │
                          └─────────────────────┘
```

### Technology Stack

**Backend:**
- Laravel 10+ (Enhanced API controllers)
- Redis (Caching & Session)
- Laravel WebSockets (Real-time events)
- Laravel Queue (Workflow automation)

**Frontend:**
- React 19+ with TypeScript
- PWA capabilities
- Camera API access
- WebSocket client
- IndexedDB (Offline storage)

## API Documentation

### Authentication

All mobile scanning endpoints require device authentication:

```http
POST /api/v1/devices/authenticate
Content-Type: application/json

{
    "device_id": "mobile_123456789",
    "device_token": "secure_token_here"
}
```

**Response:**
```json
{
    "success": true,
    "authenticated": true,
    "device": {
        "id": 1,
        "device_id": "mobile_123456789",
        "device_name": "Samsung Galaxy S24",
        "platform": "android",
        "app_version": "1.0.0",
        "is_active": true
    }
}
```

### Single Scan Endpoint

```http
POST /api/v1/mobile/scan
X-Device-ID: mobile_123456789
X-Device-Token: secure_token_here
Content-Type: application/json

{
    "tracking_number": "BL123456789",
    "action": "inbound|outbound|delivery|exception|manual_intervention",
    "location_id": 1,
    "timestamp": "2025-11-11T12:41:17Z",
    "notes": "Optional notes",
    "latitude": 40.7128,
    "longitude": -74.0060,
    "accuracy": 10.5,
    "barcode_type": "barcode|qr|sscc|sscc18",
    "offline_sync_key": "sync_123"
}
```

**Response:**
```json
{
    "success": true,
    "scan_id": 123,
    "shipment_id": 456,
    "status": "in_transit",
    "previous_status": "pending",
    "next_expected": "arrival",
    "branch_info": {
        "id": 1,
        "name": "Main Hub",
        "code": "MH001"
    }
}
```

### Bulk Scan Endpoint

```http
POST /api/v1/mobile/bulk-scan
X-Device-ID: mobile_123456789
X-Device-Token: secure_token_here
Content-Type: application/json

{
    "scans": [
        {
            "tracking_number": "BL123456789",
            "action": "inbound",
            "location_id": 1,
            "batch_id": "batch_001"
        }
    ],
    "batch_id": "batch_001"
}
```

**Response:**
```json
{
    "success": true,
    "results": {
        "success": [
            {
                "index": 0,
                "tracking": "BL123456789",
                "status": "in_transit"
            }
        ],
        "failed": [],
        "conflicts": []
    },
    "processed": 1,
    "failed": 0,
    "conflicts": 0,
    "batch_id": "batch_001"
}
```

### Offline Sync Endpoint

```http
POST /api/v1/mobile/enhanced-offline-sync
X-Device-ID: mobile_123456789
X-Device-Token: secure_token_here
Content-Type: application/json

{
    "pending_scans": [
        {
            "tracking_number": "BL123456789",
            "action": "inbound",
            "location_id": 1,
            "timestamp": "2025-11-11T12:41:17Z",
            "offline_sync_key": "sync_123"
        }
    ]
}
```

**Response:**
```json
{
    "success": true,
    "results": {
        "processed": 1,
        "conflicts": 0,
        "errors": []
    },
    "sync_count": 1,
    "conflict_count": 0,
    "error_count": 0
}
```

### Device Info Endpoint

```http
GET /api/v1/mobile/device-info
X-Device-ID: mobile_123456789
X-Device-Token: secure_token_here
```

**Response:**
```json
{
    "success": true,
    "device": {
        "id": 1,
        "device_id": "mobile_123456789",
        "device_name": "Samsung Galaxy S24",
        "platform": "android",
        "app_version": "1.0.0",
        "is_active": true,
        "last_seen_at": "2025-11-11T12:41:17Z"
    }
}
```

## Frontend Integration

### React Component Setup

1. **Install Dependencies:**
```bash
cd react-dashboard
npm install @zxing/library react-qr-reader socket.io-client idb
npm install -D vite-plugin-pwa
```

2. **Add Camera Permissions:**
```typescript
// In your camera component
const startCamera = async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { 
        facingMode: 'environment',
        width: { ideal: 1920 },
        height: { ideal: 1080 }
      }
    });
    // Handle successful camera access
  } catch (error) {
    // Handle camera access denied
  }
};
```

3. **WebSocket Connection:**
```typescript
import io from 'socket.io-client';

const socket = io(process.env.REACT_APP_WEBSOCKET_URL || 'ws://localhost:3001');

socket.on('shipment.scanned', (data) => {
  // Handle real-time scan events
  console.log('Scan event received:', data);
});
```

### PWA Configuration

**vite.config.ts:**
```typescript
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
  plugins: [
    react(),
    VitePWA({
      registerType: 'autoUpdate',
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg}']
      },
      manifest: {
        name: 'Baraka Logistics Mobile Scanner',
        short_name: 'Baraka Scanner',
        description: 'Mobile scanning application for Baraka Logistics',
        theme_color: '#3B82F6',
        background_color: '#F9FAFB',
        display: 'standalone',
        icons: [
          {
            src: '/icon-192x192.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: '/icon-512x512.png',
            sizes: '512x512',
            type: 'image/png'
          }
        ]
      }
    })
  ],
});
```

## Database Setup

### Run Migrations

```bash
# Generate migration for device table updates
php artisan make:migration add_mobile_scanning_to_devices

# Run migrations
php artisan migrate
```

### Seed Device Data (Optional)

```php
// DatabaseSeeder.php
use App\Models\Device;
use App\Models\Backend\Branch;

public function run()
{
    // Create test devices
    Device::create([
        'device_id' => 'mobile_test_001',
        'device_name' => 'Test Mobile Device',
        'platform' => 'android',
        'app_version' => '1.0.0',
        'device_token' => bin2hex(random_bytes(32)),
        'is_active' => true,
    ]);
}
```

## PWA Deployment

### Build and Deploy

1. **Build the PWA:**
```bash
cd react-dashboard
npm run build
```

2. **Deploy Static Files:**
```bash
# Copy to public directory
cp -r dist/* ../public/mobile/

# Or deploy to CDN
aws s3 sync dist/ s3://your-bucket/mobile/
```

3. **Configure HTTPS:**
PWA requires HTTPS for camera access. Configure SSL certificates on your web server.

### Service Worker Registration

The service worker is automatically registered in production. For development:

```typescript
// In your main App.tsx
if ('serviceWorker' in navigator && process.env.NODE_ENV === 'production') {
  navigator.serviceWorker.register('/sw.js')
    .then((registration) => {
      console.log('SW registered: ', registration);
    })
    .catch((registrationError) => {
      console.log('SW registration failed: ', registrationError);
    });
}
```

## WebSocket Configuration

### Laravel WebSockets Setup

1. **Install Laravel WebSockets:**
```bash
composer require beyondcode/laravel-websockets
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware\Authorize"
```

2. **Environment Configuration:**
```env
# .env
BROADCAST_DRIVER=laravel-websockets
QUEUE_CONNECTION=sync

# WebSockets
LARAVEL_WEBSOCKETS_PORT=6001
LARAVEL_WEBSOCKETS_HOST=0.0.0.0
```

3. **Start WebSocket Server:**
```bash
php artisan websockets:serve
```

### Frontend WebSocket Client

```typescript
const connectWebSocket = () => {
  const socket = io('ws://your-websocket-server:6001', {
    auth: {
      token: 'your-auth-token'
    }
  });

  socket.on('connect', () => {
    console.log('Connected to WebSocket server');
  });

  socket.on('shipment.scanned', (data) => {
    // Handle real-time updates
    updateUI(data);
  });

  return socket;
};
```

## Workflow Automation

### Queue Configuration

```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'high,low',
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

### Run Queue Workers

```bash
# Start queue worker
php artisan queue:work redis --queue=high,default

# Start multiple workers
php artisan queue:work redis --queue=high --workers=3
php artisan queue:work redis --queue=default --workers=2
```

### Workflow Jobs

The system automatically creates workflow tasks for:
- **Exception scans**: Create high-priority exception handling tasks
- **Delivery scans**: Create customer communication tasks
- **Manual intervention scans**: Create escalation tasks

## Testing

### Run Feature Tests

```bash
# Run mobile scanning tests
php artisan test tests/Feature/MobileScanningTest.php

# Run all tests
php artisan test
```

### Manual Testing

1. **Device Authentication:**
   - Register device with valid credentials
   - Test with invalid credentials

2. **Single Scan:**
   - Test successful scan processing
   - Test duplicate detection
   - Test error handling

3. **Bulk Scan:**
   - Test multiple shipments
   - Test conflict resolution

4. **Offline Sync:**
   - Test offline scanning
   - Test sync when back online

5. **PWA:**
   - Test installation
   - Test offline functionality
   - Test camera access

## Monitoring & Logging

### Application Logs

```php
// Configured for mobile scanning endpoints
Log::info('Mobile scan successful', [
    'device_id' => $request->header('X-Device-ID'),
    'tracking_number' => $scanData['tracking_number'],
    'action' => $scanData['action'],
    'timestamp' => now(),
]);
```

### Performance Monitoring

```php
// Add to middleware for performance tracking
$startTime = microtime(true);

$response = $next($request);

$duration = microtime(true) - $startTime;
Log::info('API Response Time', [
    'endpoint' => $request->path(),
    'duration' => $duration,
    'device_id' => $request->header('X-Device-ID'),
]);

return $response;
```

### Database Monitoring

```php
// Monitor slow queries
DB::listen(function ($query) {
    if ($query->time > 1000) { // > 1 second
        Log::warning('Slow Query', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
        ]);
    }
});
```

## Troubleshooting

### Common Issues

**1. Camera Access Denied**
```typescript
// Check permissions
if (navigator.permissions) {
  const permission = await navigator.permissions.query({name: 'camera'});
  console.log('Camera permission:', permission.state);
}
```

**2. WebSocket Connection Failed**
```typescript
// Implement reconnection logic
socket.on('disconnect', () => {
  setTimeout(() => {
    socket.connect();
  }, 5000);
});
```

**3. Offline Sync Not Working**
```typescript
// Check service worker registration
navigator.serviceWorker.ready.then((registration) => {
  console.log('Service Worker registered:', registration);
});

// Check IndexedDB support
if (!window.indexedDB) {
  console.error('IndexedDB not supported');
}
```

**4. API Rate Limiting**
```typescript
// Handle rate limit responses
if (response.status === 429) {
  const retryAfter = response.headers.get('Retry-After');
  setTimeout(() => {
    // Retry request
  }, retryAfter * 1000);
}
```

### Debug Mode

Enable debug logging in production for troubleshooting:

```php
// In .env
LOG_LEVEL=debug
APP_DEBUG=false
```

### Health Check Endpoints

```http
# API health
GET /api/v1/health

# WebSocket health
GET /laravel-websockets

# Database health
GET /api/v1/health/database
```

## Performance Optimization

### Caching Strategy

```php
// Cache device information
Cache::remember(
    "device:{$deviceId}",
    3600,
    function () use ($device) {
        return $device->toArray();
    }
);
```

### Database Indexing

```sql
-- Add indexes for performance
CREATE INDEX idx_scans_shipment_id ON scans(shipment_id);
CREATE INDEX idx_scans_device_id ON scans(device_id);
CREATE INDEX idx_devices_device_id ON devices(device_id);
CREATE INDEX idx_scans_created_at ON scans(created_at);
```

### API Response Optimization

```php
// Use resource collections for efficient responses
return ScanResource::collection($scans->paginate(50));
```

## Security Considerations

### Device Authentication

- Each device has unique token
- Tokens are validated on each request
- Rate limiting per device
- Automatic token expiration

### Data Encryption

- HTTPS required for all API calls
- Sensitive data encrypted in database
- Device tokens stored securely

### Access Control

- Role-based access to workflows
- Branch-based data isolation
- Audit logging for all actions

## Support & Maintenance

### Regular Maintenance

1. **Database Cleanup:**
```bash
# Clean old scan data
php artisan command:cleanup-scans --days=90
```

2. **Queue Monitoring:**
```bash
# Monitor queue health
php artisan horizon:status
```

3. **Performance Monitoring:**
```bash
# Check API performance
php artisan command:check-api-performance
```

### Contact Information

- **Technical Support**: tech@barakalogistics.com
- **Emergency Hotline**: +1-XXX-XXX-XXXX
- **Documentation**: docs.barakalogistics.com

## Version History

- **v1.0.0** (2025-11-11): Initial mobile scanning implementation
  - Enhanced API endpoints
  - PWA mobile interface
  - Real-time WebSocket updates
  - Workflow automation
  - Comprehensive test suite

---

*This documentation is part of the Baraka Logistics Platform Mobile Scanning implementation. For updates and additional resources, visit the project repository.*