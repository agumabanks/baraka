# Operations Control Center - Enhanced Architecture with Real-Time Notifications

## Architecture Overview

The Operations Control Center implements a comprehensive real-time notification system that connects operational services with dashboard interfaces and mobile apps through WebSocket and push notifications.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    Operations Control Center Enhanced                        │
└─────────────────────────────────────────────────────────────────────────────┘
                                      │
                    ┌─────────────────┼─────────────────┐
                    │                 │                 │
                    ▼                 ▼                 ▼
        ┌───────────────────┐ ┌──────────────┐ ┌─────────────────┐
        │ DispatchBoard     │ │ AssetMgmt    │ │ ExceptionTower  │
        │ Service           │ │ Service      │ │ Service         │
        └─────────┬─────────┘ └──────┬───────┘ └────────┬────────┘
                  │                  │                   │
                  │                  │                   │
                  └──────────────────┼───────────────────┘
                                     │
                                     ▼
                    ┌──────────────────────────────┐
                    │ OperationsNotificationService│
                    │                              │
                    │  • Database Persistence      │
                    │  • Real-time Broadcasting    │
                    │  • Push Notifications        │
                    └──────┬──────────────┬────────┘
                           │              │
                ┌──────────┴─────┐       │
                │                │       │
                ▼                ▼       ▼
    ┌──────────────────┐  ┌──────────────────┐  ┌──────────────┐
    │ Laravel          │  │ PushNotification │  │ Notification │
    │ Broadcasting     │  │ Service (FCM)    │  │ Model (DB)   │
    └────────┬─────────┘  └────────┬─────────┘  └──────────────┘
             │                     │
             ▼                     ▼
    ┌──────────────────┐  ┌──────────────────┐
    │ WebSocket Server │  │ FCM/Firebase     │
    └────────┬─────────┘  └────────┬─────────┘
             │                     │
             ▼                     ▼
    ┌──────────────────┐  ┌──────────────────┐
    │ Dashboard UI     │  │ Mobile Apps      │
    └──────────────────┘  └──────────────────┘
```

## Components

### 1. Core Services

#### DispatchBoardService
- Manages shipment assignment to workers
- Load balancing and capacity planning
- Worker workload tracking
- Real-time dispatch board updates

**Key Methods:**
- `getDispatchBoard()` - Get real-time dispatch board
- `assignShipmentToWorker()` - Assign shipment to worker
- `getLoadBalancingMetrics()` - Get load balancing insights

#### AssetManagementService
- Vehicle and asset tracking
- Maintenance scheduling
- Fuel consumption monitoring
- Asset utilization metrics

**Key Methods:**
- `getAssetStatus()` - Get asset status overview
- `getVehicleUtilization()` - Vehicle usage metrics
- `checkMaintenanceAlerts()` - Maintenance alerts

#### ExceptionTowerService
- Exception detection and tracking
- Exception resolution workflow
- Priority-based exception handling
- Exception metrics and analytics

**Key Methods:**
- `createException()` - Create new exception
- `assignExceptionToResolver()` - Assign to resolver
- `getExceptionMetrics()` - Exception analytics

#### ControlTowerService
- Operational KPIs and metrics
- Branch performance monitoring
- Worker utilization tracking
- Alert management

**Key Methods:**
- `getOperationalKPIs()` - Real-time KPIs
- `getBranchPerformance()` - Branch metrics
- `getAlerts()` - Critical alerts

### 2. Notification System

#### OperationsNotificationService
Central notification hub that:
- Persists notifications to database
- Broadcasts to WebSocket channels
- Sends push notifications via FCM
- Manages user notification preferences

**Key Features:**
- Multi-channel delivery (WebSocket, Push, Email, SMS)
- Priority-based routing
- User preferences and quiet hours
- Notification history and analytics

**Notification Types:**
- `exception.created` - New exception created
- `alert.capacity_warning` - Capacity warning
- `alert.sla_breach_risk` - SLA breach risk
- `alert.worker_overload` - Worker overload
- `alert.asset_maintenance` - Asset maintenance due
- `alert.stuck_shipments` - Stuck shipments detected

#### OperationsNotification Model
Database model for notification persistence with:
- UUID-based identification
- Rich metadata (severity, priority, type)
- Status tracking (pending, sent, delivered, read)
- Multi-channel support
- Related entity associations

**Scopes:**
- `unread()` - Unread notifications
- `forUser($userId)` - User-specific notifications
- `requiresAction()` - Action-required notifications
- `critical()` - Critical priority notifications
- `recent($hours)` - Recent notifications

### 3. Real-Time Broadcasting

#### Broadcasting Channels
Private and public channels for real-time updates:

**Public Channels:**
- `operations.dashboard` - Dashboard updates
- `operations.exceptions` - Exception notifications
- `operations.alerts` - Operational alerts
- `operations.dispatch` - Dispatch board updates

**Branch-Specific Channels:**
- `operations.dashboard.branch.{branchId}`
- `operations.exceptions.branch.{branchId}`
- `operations.dispatch.branch.{branchId}`

**User-Specific Channels:**
- `operations.alerts.user.{userId}` - Personal alerts

### 4. Push Notifications

#### PushNotificationService
FCM integration for mobile push notifications:
- Firebase Cloud Messaging (FCM v1)
- Topic-based subscriptions
- Device token management
- Delivery tracking

**Notification Channels:**
- **WebSocket** - Real-time dashboard updates
- **Push** - Mobile app notifications
- **Email** - Email notifications (future)
- **SMS** - SMS alerts (future)

## Database Schema

### operations_notifications Table

```sql
CREATE TABLE operations_notifications (
    id BIGINT PRIMARY KEY,
    notification_uuid UUID UNIQUE,
    
    -- Metadata
    type VARCHAR,
    category VARCHAR DEFAULT 'operational',
    title VARCHAR,
    message TEXT,
    severity ENUM('low', 'medium', 'high', 'critical'),
    priority ENUM('1', '2', '3', '4', '5'),
    
    -- Content
    data JSON,
    action_data JSON,
    
    -- Status
    status ENUM('pending', 'sent', 'delivered', 'read', 'failed'),
    requires_action BOOLEAN DEFAULT false,
    is_dismissed BOOLEAN DEFAULT false,
    
    -- Delivery
    channels JSON,
    sent_at TIMESTAMP,
    delivered_at TIMESTAMP,
    read_at TIMESTAMP,
    dismissed_at TIMESTAMP,
    
    -- Recipients
    user_id BIGINT FK,
    branch_id BIGINT FK,
    recipient_role VARCHAR,
    
    -- Related entities
    shipment_id BIGINT FK,
    worker_id BIGINT FK,
    asset_id BIGINT FK,
    related_entity_type VARCHAR,
    related_entity_id BIGINT,
    
    -- Tracking
    created_by BIGINT FK,
    error_message TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

**Indexes:**
- `idx_user_status` - (user_id, status, created_at)
- `idx_branch_status` - (branch_id, status)
- `idx_type_created` - (type, created_at)
- `idx_severity_priority` - (severity, priority)
- `idx_read_user` - (read_at, user_id)

## API Endpoints

### Operations Control Center
Base URL: `/api/operations`

#### Dispatch Board
- `GET /dispatch-board` - Get dispatch board
- `POST /assign-shipment` - Assign shipment to worker
- `POST /reassign-shipment` - Reassign shipment
- `GET /unassigned-shipments` - Get unassigned shipments
- `GET /workers/{worker}/workload` - Worker workload
- `GET /load-balancing-metrics` - Load balancing metrics

#### Exception Tower
- `GET /exceptions` - Get active exceptions
- `POST /exceptions` - Create new exception
- `POST /shipments/{shipment}/assign-exception` - Assign to resolver
- `PUT /shipments/{shipment}/exception-status` - Update status
- `GET /exception-metrics` - Exception analytics
- `GET /priority-exceptions` - Priority exceptions

#### Asset Management
- `GET /asset-status` - Asset status overview
- `GET /vehicles/{vehicleId}/utilization` - Vehicle utilization
- `GET /maintenance-schedule` - Maintenance schedule
- `GET /vehicles/{vehicleId}/fuel-consumption` - Fuel consumption
- `GET /asset-metrics` - Asset metrics
- `GET /available-vehicles` - Available vehicles

#### Control Tower
- `GET /kpis` - Operational KPIs
- `GET /branch-performance` - Branch performance
- `GET /worker-utilization` - Worker utilization
- `GET /shipment-metrics` - Shipment metrics
- `GET /alerts` - Critical alerts
- `GET /operational-trends` - Operational trends

#### Notifications
- `GET /notifications` - Get user notifications
- `PUT /notifications/{notificationId}/read` - Mark as read
- `GET /notifications/unread-count` - Unread count
- `PUT /notification-preferences` - Update preferences
- `GET /notification-history` - Notification history

## Setup & Configuration

### 1. Environment Variables

Add to `.env`:

```bash
# Broadcasting Configuration
BROADCAST_DRIVER=pusher  # or 'redis' for Laravel Echo Server

# Pusher Configuration (for Laravel WebSockets)
PUSHER_APP_ID=local
PUSHER_APP_KEY=local-key
PUSHER_APP_SECRET=local-secret
PUSHER_APP_CLUSTER=mt1

# WebSocket Server Configuration
WEBSOCKETS_HOST=127.0.0.1
WEBSOCKETS_PORT=6001
WEBSOCKETS_SCHEME=http

# Firebase Cloud Messaging (FCM)
FCM_PROJECT_ID=your-project-id
FCM_SECRET_KEY=your-server-key

# Notification Settings
OPERATIONS_NOTIFICATION_RETENTION_DAYS=90
OPERATIONS_NOTIFICATION_BATCH_SIZE=100
```

### 2. Install Dependencies

```bash
# Install Laravel WebSockets (if not using Pusher)
composer require beyondcode/laravel-websockets

# Publish configuration
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Start WebSocket Server

```bash
# Start Laravel WebSockets server
php artisan websockets:serve

# Or use Laravel Queue Worker for broadcasting
php artisan queue:work
```

### 5. Enable Broadcasting

Uncomment in `config/app.php`:

```php
App\Providers\BroadcastServiceProvider::class,
```

## Frontend Integration

### JavaScript Client Setup

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    wsHost: process.env.MIX_PUSHER_HOST || window.location.hostname,
    wsPort: process.env.MIX_PUSHER_PORT || 6001,
    forceTLS: false,
    disableStats: true,
    encrypted: true,
});
```

### Subscribe to Channels

```javascript
// Subscribe to dashboard updates
Echo.channel('operations.dashboard')
    .listen('.operational.update', (e) => {
        console.log('Dashboard update:', e);
        updateDashboard(e.data);
    });

// Subscribe to exceptions
Echo.channel('operations.exceptions')
    .listen('.exception.created', (e) => {
        console.log('New exception:', e);
        showExceptionAlert(e.exception_data);
    });

// Subscribe to user-specific alerts
Echo.private(`operations.alerts.user.${userId}`)
    .listen('.operational.alert', (e) => {
        console.log('Personal alert:', e);
        showNotification(e);
    });

// Subscribe to branch-specific updates
Echo.channel(`operations.dashboard.branch.${branchId}`)
    .listen('.operational.update', (e) => {
        console.log('Branch update:', e);
        updateBranchDashboard(e.data);
    });
```

## Mobile App Integration

### FCM Setup

1. Add Firebase configuration to your mobile app
2. Request notification permissions
3. Register device token with backend

```javascript
// Register device token
async function registerDeviceToken() {
    const token = await getDeviceToken(); // From FCM SDK
    
    await fetch('/api/register-device', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${authToken}`
        },
        body: JSON.stringify({
            device_token: token,
            platform: 'ios' // or 'android'
        })
    });
}
```

### Subscribe to Topics

```javascript
// Subscribe to operations notifications
await fetch('/api/fcm/subscribe', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${authToken}`
    },
    body: JSON.stringify({
        device_token: deviceToken,
        topic: 'operations_notifications'
    })
});
```

## Notification Flow

### 1. Exception Created Flow

```
Service detects exception
    ↓
Creates OperationsNotification record
    ↓
OperationsNotificationService.notifyException()
    ↓
    ├─→ Broadcast to WebSocket channels
    │       └─→ Dashboard receives real-time update
    │
    ├─→ Send push notifications to mobile apps
    │       └─→ FCM delivers to devices
    │
    └─→ Store in database for persistence
            └─→ Notification history available
```

### 2. Alert Flow

```
Service generates alert
    ↓
ControlTowerService.getAlerts()
    ↓
OperationsNotificationService.notifyAlert()
    ↓
    ├─→ Broadcast to operations.alerts channel
    │
    ├─→ Broadcast to user-specific channels
    │
    ├─→ Send push notifications (if critical)
    │
    └─→ Create database records for recipients
```

## Testing

### Test Notification Creation

```php
use App\Models\OperationsNotification;
use App\Models\Shipment;

// Create test exception notification
$shipment = Shipment::first();
$notification = OperationsNotification::createExceptionNotification($shipment, [
    'exception_type' => 'delayed_delivery',
    'severity' => 'high',
    'priority' => 4,
    'tracking_number' => $shipment->tracking_number,
]);
```

### Test Broadcasting

```php
use App\Events\OperationalAlertEvent;

// Trigger alert
event(new OperationalAlertEvent([
    'type' => 'alert.capacity_warning',
    'title' => 'Capacity Warning',
    'message' => 'Branch capacity at 90%',
    'severity' => 'medium',
    'branch_id' => 1,
], [1, 2, 3])); // User IDs
```

### Test API Endpoints

```bash
# Get operational KPIs
curl -X GET http://localhost/api/operations/kpis \
  -H "Authorization: Bearer {token}"

# Get user notifications
curl -X GET http://localhost/api/operations/notifications \
  -H "Authorization: Bearer {token}"

# Mark notification as read
curl -X PUT http://localhost/api/operations/notifications/{uuid}/read \
  -H "Authorization: Bearer {token}"
```

## Monitoring & Maintenance

### Cleanup Old Notifications

```php
use App\Models\OperationsNotification;

// Clean up notifications older than 90 days
OperationsNotification::cleanupOldNotifications(90);
```

### Schedule Cleanup

In `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Clean up old notifications daily
    $schedule->call(function () {
        OperationsNotification::cleanupOldNotifications(90);
    })->daily();
}
```

### Monitor Performance

```php
// Get notification statistics
$stats = [
    'total' => OperationsNotification::count(),
    'unread' => OperationsNotification::unread()->count(),
    'pending' => OperationsNotification::pending()->count(),
    'critical' => OperationsNotification::critical()->count(),
];
```

## Troubleshooting

### WebSocket Connection Issues

1. Check WebSocket server is running
2. Verify firewall allows port 6001
3. Check `.env` configuration
4. Verify Pusher credentials

### Push Notifications Not Delivered

1. Verify FCM configuration
2. Check device token is valid
3. Verify user has device_token in database
4. Check Firebase project settings

### Database Performance

1. Ensure indexes are created
2. Run cleanup regularly
3. Archive old notifications
4. Monitor query performance

## Security Considerations

1. **Channel Authorization**: All private channels require authentication
2. **User Permissions**: Check user roles before allowing channel access
3. **Data Sanitization**: Sanitize notification data before broadcasting
4. **Rate Limiting**: Implement rate limiting on notification endpoints
5. **Token Security**: Secure device tokens and API keys

## Future Enhancements

1. **Email Notifications**: Add email delivery channel
2. **SMS Notifications**: Add SMS delivery for critical alerts
3. **Notification Templates**: Template system for notification formatting
4. **Analytics Dashboard**: Notification analytics and insights
5. **A/B Testing**: Test different notification strategies
6. **Machine Learning**: Predict optimal notification timing
7. **Multi-language Support**: Localized notifications

## Support & Resources

- **API Documentation**: `/docs/api`
- **Broadcasting Documentation**: https://laravel.com/docs/broadcasting
- **Laravel WebSockets**: https://beyondco.de/docs/laravel-websockets
- **Firebase Cloud Messaging**: https://firebase.google.com/docs/cloud-messaging

---

**Version**: 1.0.0  
**Last Updated**: 2025-10-08  
**Author**: Operations Team
