# Operations Control Center - Quick Start Guide

## Overview

This guide will help you quickly set up and start using the Operations Control Center with real-time notifications.

## Prerequisites

- PHP 8.1+
- MySQL/PostgreSQL
- Composer
- Node.js & NPM
- Firebase account (for push notifications)

## Step-by-Step Setup

### 1. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install WebSocket support (if not already installed)
composer require beyondcode/laravel-websockets

# Install frontend dependencies
npm install
```

### 2. Configure Environment

Copy `.env.example` to `.env` and update:

```bash
cp .env.example .env
```

Update these values in `.env`:

```env
# Database
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Broadcasting (use pusher for WebSockets)
BROADCAST_DRIVER=pusher

# Pusher/WebSocket Configuration
PUSHER_APP_ID=local
PUSHER_APP_KEY=local-key
PUSHER_APP_SECRET=local-secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1

# WebSocket Server
WEBSOCKETS_HOST=127.0.0.1
WEBSOCKETS_PORT=6001
WEBSOCKETS_SCHEME=http

# Firebase Cloud Messaging (for push notifications)
FCM_PROJECT_ID=your-firebase-project-id
FCM_SECRET_KEY=your-fcm-server-key
```

### 3. Run Migrations

```bash
# Run all migrations including operations_notifications table
php artisan migrate

# If you get errors about existing tables, you can run:
php artisan migrate:fresh  # WARNING: This drops all tables
```

### 4. Enable Broadcasting

In `config/app.php`, ensure `BroadcastServiceProvider` is uncommented:

```php
'providers' => [
    // ...
    App\Providers\BroadcastServiceProvider::class,
],
```

### 5. Start Services

Open **3 terminal windows**:

**Terminal 1 - Laravel Application:**
```bash
php artisan serve
```

**Terminal 2 - WebSocket Server:**
```bash
php artisan websockets:serve
```

**Terminal 3 - Queue Worker (for processing broadcasts):**
```bash
php artisan queue:work
```

### 6. Verify Setup

Test that everything is working:

```bash
# Test database connection
php artisan migrate:status

# Test API endpoints
curl http://localhost:8000/api/operations/kpis

# Check WebSocket dashboard (in browser)
http://localhost:8000/laravel-websockets
```

## Using the API

### Authentication

All API requests require authentication. Include your token in the header:

```bash
Authorization: Bearer YOUR_TOKEN_HERE
```

### Example Requests

#### Get Operational KPIs

```bash
curl -X GET http://localhost:8000/api/operations/kpis \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Get Dispatch Board

```bash
curl -X GET "http://localhost:8000/api/operations/dispatch-board?branch_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Get User Notifications

```bash
curl -X GET http://localhost:8000/api/operations/notifications \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Mark Notification as Read

```bash
curl -X PUT http://localhost:8000/api/operations/notifications/{uuid}/read \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Create Exception

```bash
curl -X POST http://localhost:8000/api/operations/exceptions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "shipment_id": 1,
    "type": "delayed_delivery",
    "severity": "high",
    "notes": "Shipment delayed due to weather"
  }'
```

## Frontend Integration

### 1. Install Laravel Echo

```bash
npm install --save laravel-echo pusher-js
```

### 2. Configure Echo

In your JavaScript entry file (e.g., `resources/js/app.js` or `resources/js/bootstrap.js`):

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY || 'local-key',
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST || window.location.hostname,
    wsPort: import.meta.env.VITE_PUSHER_PORT || 6001,
    wssPort: import.meta.env.VITE_PUSHER_PORT || 6001,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME || 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});
```

### 3. Subscribe to Channels

#### Dashboard Updates

```javascript
// Subscribe to operations dashboard
Echo.channel('operations.dashboard')
    .listen('.operational.update', (e) => {
        console.log('Dashboard update received:', e);
        updateDashboard(e.data);
    });
```

#### Exception Notifications

```javascript
// Subscribe to exception notifications
Echo.channel('operations.exceptions')
    .listen('.exception.created', (e) => {
        console.log('New exception:', e);
        
        // Show notification to user
        showNotification({
            title: 'New Exception',
            message: e.exception_data.message,
            type: 'warning'
        });
        
        // Update exception list
        refreshExceptionList();
    });
```

#### User-Specific Alerts

```javascript
// Get current user ID from your auth system
const userId = getCurrentUserId();

// Subscribe to personal alerts
Echo.private(`operations.alerts.user.${userId}`)
    .listen('.operational.alert', (e) => {
        console.log('Personal alert:', e);
        
        // Show toast notification
        toast.error(e.message, {
            title: e.title,
            duration: 5000
        });
        
        // Play sound if critical
        if (e.severity === 'critical') {
            playAlertSound();
        }
    });
```

#### Branch-Specific Updates

```javascript
// Subscribe to branch-specific dashboard
const branchId = getCurrentBranchId();

Echo.channel(`operations.dashboard.branch.${branchId}`)
    .listen('.operational.update', (e) => {
        console.log('Branch update:', e);
        updateBranchMetrics(e.data);
    });
```

### 4. Complete React/Vue Example

#### React Component

```javascript
import { useEffect, useState } from 'react';
import Echo from 'laravel-echo';

function OperationsDashboard() {
    const [notifications, setNotifications] = useState([]);
    const [kpis, setKPIs] = useState({});

    useEffect(() => {
        // Fetch initial data
        fetchKPIs();
        fetchNotifications();

        // Subscribe to real-time updates
        window.Echo.channel('operations.dashboard')
            .listen('.operational.update', (e) => {
                setKPIs(e.data);
            });

        window.Echo.channel('operations.exceptions')
            .listen('.exception.created', (e) => {
                // Add new notification to list
                setNotifications(prev => [e.exception_data, ...prev]);
                
                // Show browser notification
                if (Notification.permission === 'granted') {
                    new Notification('New Exception', {
                        body: e.exception_data.message,
                        icon: '/icon.png'
                    });
                }
            });

        // Cleanup on unmount
        return () => {
            window.Echo.leave('operations.dashboard');
            window.Echo.leave('operations.exceptions');
        };
    }, []);

    async function fetchKPIs() {
        const response = await fetch('/api/operations/kpis', {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });
        const data = await response.json();
        setKPIs(data.data);
    }

    async function fetchNotifications() {
        const response = await fetch('/api/operations/notifications', {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });
        const data = await response.json();
        setNotifications(data.data);
    }

    async function markAsRead(notificationId) {
        await fetch(`/api/operations/notifications/${notificationId}/read`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });
        
        // Update local state
        setNotifications(prev =>
            prev.map(n => n.id === notificationId ? { ...n, read_at: new Date() } : n)
        );
    }

    return (
        <div className="operations-dashboard">
            <div className="kpis">
                <KPICard title="Active Shipments" value={kpis.shipments?.active} />
                <KPICard title="Active Workers" value={kpis.workers?.active} />
                <KPICard title="Exceptions" value={kpis.shipments?.exceptions_today} />
            </div>

            <div className="notifications">
                <h2>Recent Notifications</h2>
                {notifications.map(notification => (
                    <NotificationCard
                        key={notification.id}
                        notification={notification}
                        onMarkAsRead={markAsRead}
                    />
                ))}
            </div>
        </div>
    );
}
```

## Testing the Setup

### 1. Test WebSocket Connection

Open your browser console and run:

```javascript
// Test connection
window.Echo.connector.pusher.connection.bind('connected', () => {
    console.log('WebSocket connected successfully!');
});

window.Echo.connector.pusher.connection.bind('error', (err) => {
    console.error('WebSocket connection error:', err);
});
```

### 2. Create Test Notification

In your Laravel application:

```php
use App\Models\OperationsNotification;
use App\Models\Shipment;

// Create a test notification
$shipment = Shipment::first();

$notification = OperationsNotification::createExceptionNotification($shipment, [
    'exception_type' => 'test_exception',
    'severity' => 'high',
    'priority' => 4,
    'tracking_number' => $shipment->tracking_number ?? 'TEST-001',
    'user_id' => auth()->id(),
]);

// Broadcast it
event(new \App\Events\ExceptionCreatedEvent($shipment, [
    'exception_type' => 'test_exception',
    'severity' => 'high',
    'message' => 'This is a test exception',
]));
```

### 3. Monitor WebSocket Dashboard

Visit: `http://localhost:8000/laravel-websockets`

You should see:
- Connected clients
- Active channels
- Real-time statistics

## Troubleshooting

### WebSocket Connection Failed

**Problem**: Can't connect to WebSocket server

**Solutions**:
1. Ensure WebSocket server is running: `php artisan websockets:serve`
2. Check firewall allows port 6001
3. Verify `.env` has correct PUSHER_* values
4. Try accessing: `http://localhost:6001/`

### Broadcasts Not Received

**Problem**: Events are fired but not received in frontend

**Solutions**:
1. Ensure queue worker is running: `php artisan queue:work`
2. Check `BROADCAST_DRIVER=pusher` in `.env`
3. Verify channel authorization in `routes/channels.php`
4. Check browser console for errors
5. Verify you're subscribed to the correct channel

### Push Notifications Not Working

**Problem**: Mobile push notifications not delivered

**Solutions**:
1. Verify FCM_PROJECT_ID and FCM_SECRET_KEY in `.env`
2. Check device token is registered in database
3. Verify Firebase project configuration
4. Check device has granted notification permissions
5. Test with Firebase Console directly

### Database Errors

**Problem**: Migration or query errors

**Solutions**:
1. Run: `php artisan migrate:status`
2. Check database connection in `.env`
3. Ensure all foreign key tables exist
4. Check database user has proper permissions

## Next Steps

1. **Customize Notifications**: Modify `OperationsNotificationService` to add custom notification types
2. **Add Email Channel**: Implement email notifications
3. **Create Dashboard UI**: Build a comprehensive operations dashboard
4. **Mobile App Integration**: Integrate with your mobile app
5. **Analytics**: Add notification analytics and reporting
6. **Monitoring**: Set up monitoring for WebSocket server uptime

## Resources

- **Full Documentation**: [OPERATIONS_CONTROL_CENTER_ARCHITECTURE.md](./OPERATIONS_CONTROL_CENTER_ARCHITECTURE.md)
- **API Reference**: See all endpoints in `routes/api.php`
- **Laravel Broadcasting**: https://laravel.com/docs/broadcasting
- **Laravel WebSockets**: https://beyondco.de/docs/laravel-websockets
- **Laravel Echo**: https://laravel.com/docs/broadcasting#client-side-installation

## Support

For issues or questions:
1. Check the full documentation
2. Review Laravel logs: `storage/logs/laravel.log`
3. Check WebSocket logs in the dashboard
4. Review browser console for errors

---

Happy coding! 🚀
