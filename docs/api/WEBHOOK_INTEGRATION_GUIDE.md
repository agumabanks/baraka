# Baraka API - Webhook Integration Guide

**Version:** 1.0  
**Last Updated:** November 28, 2025

---

## Table of Contents

1. [Overview](#overview)
2. [Getting Started](#getting-started)
3. [Webhook Events](#webhook-events)
4. [Payload Structure](#payload-structure)
5. [Security](#security)
6. [Error Handling](#error-handling)
7. [Best Practices](#best-practices)
8. [Code Examples](#code-examples)

---

## Overview

Baraka webhooks allow you to receive real-time notifications when events occur in your shipments. Instead of polling our API, webhooks push data to your server when something happens.

### Benefits
- Real-time updates
- Reduced API calls
- Better user experience
- Automated workflows

---

## Getting Started

### 1. Register Your Webhook Endpoint

```bash
POST /api/v1/webhooks/register
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json

{
  "url": "https://your-server.com/webhooks/baraka",
  "events": ["shipment.created", "shipment.delivered", "shipment.exception"],
  "secret": "your_webhook_secret_for_signature_verification"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "wh_abc123",
    "url": "https://your-server.com/webhooks/baraka",
    "events": ["shipment.created", "shipment.delivered", "shipment.exception"],
    "status": "active",
    "created_at": "2025-11-28T10:00:00Z"
  }
}
```

### 2. Verify Your Endpoint

After registration, we'll send a verification request:

```json
{
  "type": "webhook.verification",
  "challenge": "ch_xyz789"
}
```

Your server must respond with:
```json
{
  "challenge": "ch_xyz789"
}
```

### 3. Start Receiving Events

Once verified, your endpoint will receive webhook events.

---

## Webhook Events

### Available Events

| Event | Description | Trigger |
|-------|-------------|---------|
| `shipment.created` | New shipment booked | Shipment created via API/UI |
| `shipment.picked_up` | Shipment collected | Driver picks up shipment |
| `shipment.in_transit` | In transit to destination | Shipment leaves origin hub |
| `shipment.out_for_delivery` | Out for delivery | Loaded for final delivery |
| `shipment.delivered` | Successfully delivered | POD confirmed |
| `shipment.exception` | Exception occurred | Any exception logged |
| `shipment.returned` | Returned to sender | Return completed |
| `shipment.cancelled` | Shipment cancelled | Cancellation processed |
| `scan.created` | New scan event | Any scan recorded |
| `invoice.created` | Invoice generated | Invoice created |
| `invoice.paid` | Invoice paid | Payment received |
| `cod.collected` | COD collected | COD payment received |

### Event Subscription

Subscribe to specific events or all events:

```json
{
  "events": ["shipment.delivered", "shipment.exception"]
}
```

Or subscribe to all events:
```json
{
  "events": ["*"]
}
```

---

## Payload Structure

### Standard Webhook Payload

```json
{
  "id": "evt_abc123def456",
  "type": "shipment.delivered",
  "created_at": "2025-11-28T14:30:00Z",
  "data": {
    "shipment": {
      "id": 12345,
      "tracking_number": "TRK-ABCD1234",
      "status": "delivered",
      "origin_branch": {
        "id": 1,
        "name": "Istanbul Hub",
        "code": "IST"
      },
      "destination_branch": {
        "id": 5,
        "name": "Kinshasa Hub",
        "code": "FIH"
      },
      "customer": {
        "id": 100,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "parcels_count": 2,
      "total_weight_kg": 5.5,
      "delivered_at": "2025-11-28T14:25:00Z",
      "pod": {
        "signature": true,
        "photo": true,
        "recipient_name": "Jane Smith"
      }
    }
  },
  "metadata": {
    "api_version": "v1",
    "webhook_id": "wh_abc123",
    "delivery_attempt": 1
  }
}
```

### Event-Specific Payloads

#### shipment.created
```json
{
  "type": "shipment.created",
  "data": {
    "shipment": {
      "id": 12345,
      "tracking_number": "TRK-ABCD1234",
      "status": "booked",
      "service_level": "express",
      "origin_branch": {...},
      "destination_branch": {...},
      "customer": {...},
      "parcels": [
        {
          "id": 1,
          "weight_kg": 2.5,
          "dimensions": {"length": 30, "width": 20, "height": 15}
        }
      ],
      "pricing": {
        "total": 45.00,
        "currency": "USD"
      },
      "created_at": "2025-11-28T10:00:00Z"
    }
  }
}
```

#### shipment.exception
```json
{
  "type": "shipment.exception",
  "data": {
    "shipment": {
      "id": 12345,
      "tracking_number": "TRK-ABCD1234",
      "status": "exception"
    },
    "exception": {
      "type": "ADDR",
      "severity": "medium",
      "description": "Invalid delivery address",
      "occurred_at": "2025-11-28T12:00:00Z",
      "resolution_required": true
    }
  }
}
```

#### scan.created
```json
{
  "type": "scan.created",
  "data": {
    "scan": {
      "id": 5678,
      "shipment_id": 12345,
      "tracking_number": "TRK-ABCD1234",
      "type": "arrival",
      "location": {
        "branch_id": 3,
        "branch_name": "Kigali Hub",
        "latitude": -1.9403,
        "longitude": 29.8739
      },
      "scanned_by": "Driver John",
      "scanned_at": "2025-11-28T11:30:00Z",
      "notes": "Arrived at destination hub"
    }
  }
}
```

---

## Security

### Signature Verification

All webhooks include a signature header for verification:

```
X-Baraka-Signature: sha256=abc123def456...
X-Baraka-Timestamp: 1732800000
```

**Verify the signature:**

```php
// PHP Example
function verifyWebhookSignature($payload, $signature, $timestamp, $secret) {
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    return hash_equals($expectedSignature, $signature);
}

// Usage
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_BARAKA_SIGNATURE'];
$timestamp = $_SERVER['HTTP_X_BARAKA_TIMESTAMP'];

if (!verifyWebhookSignature($payload, $signature, $timestamp, 'your_webhook_secret')) {
    http_response_code(401);
    exit('Invalid signature');
}
```

```javascript
// Node.js Example
const crypto = require('crypto');

function verifyWebhookSignature(payload, signature, timestamp, secret) {
    const expectedSignature = 'sha256=' + 
        crypto.createHmac('sha256', secret)
            .update(timestamp + '.' + payload)
            .digest('hex');
    return crypto.timingSafeEqual(
        Buffer.from(signature),
        Buffer.from(expectedSignature)
    );
}
```

### Timestamp Validation

Reject webhooks older than 5 minutes to prevent replay attacks:

```php
$timestamp = $_SERVER['HTTP_X_BARAKA_TIMESTAMP'];
if (abs(time() - $timestamp) > 300) {
    http_response_code(400);
    exit('Webhook too old');
}
```

### IP Whitelisting (Optional)

Baraka webhooks originate from these IP addresses:
- 45.33.32.156
- 45.33.32.157
- 45.79.112.203

---

## Error Handling

### Response Codes

Your endpoint should return:

| Code | Meaning | Our Action |
|------|---------|------------|
| 200-299 | Success | Mark delivered |
| 400 | Bad request | No retry |
| 401 | Unauthorized | No retry |
| 404 | Not found | No retry |
| 500-599 | Server error | Retry |
| Timeout | No response | Retry |

### Retry Policy

Failed webhooks are retried with exponential backoff:

| Attempt | Delay |
|---------|-------|
| 1 | Immediate |
| 2 | 1 minute |
| 3 | 5 minutes |
| 4 | 30 minutes |
| 5 | 2 hours |
| 6 | 12 hours |
| 7 | 24 hours |

After 7 failed attempts, the webhook is marked as failed and we'll notify you via email.

### Idempotency

Webhooks may be delivered more than once. Use the event `id` to ensure idempotent processing:

```php
// Store processed event IDs
$eventId = $webhook['id'];

if (ProcessedEvents::exists($eventId)) {
    return response(200); // Already processed
}

// Process the webhook
processWebhook($webhook);

// Mark as processed
ProcessedEvents::create($eventId);
```

---

## Best Practices

### 1. Respond Quickly

Return a 200 response immediately, then process asynchronously:

```php
// Acknowledge receipt immediately
http_response_code(200);
fastcgi_finish_request(); // For PHP-FPM

// Process asynchronously
Queue::push(new ProcessWebhook($payload));
```

### 2. Handle All Event Types

Always check the event type:

```php
$event = json_decode($payload, true);

switch ($event['type']) {
    case 'shipment.delivered':
        handleDelivered($event['data']);
        break;
    case 'shipment.exception':
        handleException($event['data']);
        break;
    default:
        // Log unknown event types
        Log::info('Unknown webhook event', $event);
}
```

### 3. Use HTTPS

Always use HTTPS endpoints. HTTP endpoints will be rejected.

### 4. Log Everything

Log all webhooks for debugging:

```php
Log::info('Webhook received', [
    'event_id' => $event['id'],
    'type' => $event['type'],
    'timestamp' => $event['created_at'],
]);
```

### 5. Test with Webhook CLI

Use our testing tool to simulate webhooks:

```bash
curl -X POST "https://api.baraka.co/v1/webhooks/test" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"event_type": "shipment.delivered", "target_url": "https://your-server.com/webhooks"}'
```

---

## Code Examples

### PHP (Laravel)

```php
// routes/web.php
Route::post('/webhooks/baraka', [WebhookController::class, 'handle']);

// app/Http/Controllers/WebhookController.php
class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify signature
        $signature = $request->header('X-Baraka-Signature');
        $timestamp = $request->header('X-Baraka-Timestamp');
        $payload = $request->getContent();
        
        if (!$this->verifySignature($payload, $signature, $timestamp)) {
            return response('Invalid signature', 401);
        }
        
        // Parse event
        $event = json_decode($payload, true);
        
        // Dispatch to queue
        WebhookJob::dispatch($event);
        
        return response('OK', 200);
    }
    
    private function verifySignature($payload, $signature, $timestamp)
    {
        $expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $payload, config('services.baraka.webhook_secret'));
        return hash_equals($expected, $signature);
    }
}

// app/Jobs/WebhookJob.php
class WebhookJob implements ShouldQueue
{
    public function handle()
    {
        switch ($this->event['type']) {
            case 'shipment.delivered':
                $this->handleDelivered();
                break;
            case 'shipment.exception':
                $this->handleException();
                break;
        }
    }
    
    private function handleDelivered()
    {
        $shipment = $this->event['data']['shipment'];
        
        // Update your database
        Order::where('tracking_number', $shipment['tracking_number'])
            ->update([
                'status' => 'delivered',
                'delivered_at' => $shipment['delivered_at'],
            ]);
        
        // Notify customer
        Mail::to($shipment['customer']['email'])
            ->send(new OrderDelivered($shipment));
    }
}
```

### Node.js (Express)

```javascript
const express = require('express');
const crypto = require('crypto');
const app = express();

app.post('/webhooks/baraka', express.raw({type: 'application/json'}), (req, res) => {
    const signature = req.headers['x-baraka-signature'];
    const timestamp = req.headers['x-baraka-timestamp'];
    const payload = req.body.toString();
    
    // Verify signature
    const expectedSig = 'sha256=' + crypto
        .createHmac('sha256', process.env.WEBHOOK_SECRET)
        .update(timestamp + '.' + payload)
        .digest('hex');
    
    if (!crypto.timingSafeEqual(Buffer.from(signature), Buffer.from(expectedSig))) {
        return res.status(401).send('Invalid signature');
    }
    
    // Parse event
    const event = JSON.parse(payload);
    
    // Respond immediately
    res.status(200).send('OK');
    
    // Process async
    processWebhook(event);
});

async function processWebhook(event) {
    console.log(`Processing ${event.type}:`, event.id);
    
    switch (event.type) {
        case 'shipment.delivered':
            await handleDelivered(event.data);
            break;
        case 'shipment.exception':
            await handleException(event.data);
            break;
    }
}
```

### Python (Flask)

```python
from flask import Flask, request, abort
import hmac
import hashlib
import json
from celery import Celery

app = Flask(__name__)
celery = Celery('tasks')

@app.route('/webhooks/baraka', methods=['POST'])
def webhook():
    signature = request.headers.get('X-Baraka-Signature')
    timestamp = request.headers.get('X-Baraka-Timestamp')
    payload = request.data.decode('utf-8')
    
    # Verify signature
    expected = 'sha256=' + hmac.new(
        WEBHOOK_SECRET.encode(),
        f'{timestamp}.{payload}'.encode(),
        hashlib.sha256
    ).hexdigest()
    
    if not hmac.compare_digest(signature, expected):
        abort(401)
    
    event = json.loads(payload)
    
    # Queue for async processing
    process_webhook.delay(event)
    
    return 'OK', 200

@celery.task
def process_webhook(event):
    if event['type'] == 'shipment.delivered':
        handle_delivered(event['data'])
    elif event['type'] == 'shipment.exception':
        handle_exception(event['data'])
```

---

## API Reference

### Register Webhook

```
POST /api/v1/webhooks/register
```

### List Webhooks

```
GET /api/v1/webhooks
```

### Update Webhook

```
PUT /api/v1/webhooks/{webhook_id}
```

### Delete Webhook

```
DELETE /api/v1/webhooks/{webhook_id}
```

### Get Webhook Deliveries

```
GET /api/v1/webhooks/{webhook_id}/deliveries
```

### Retry Failed Delivery

```
POST /api/v1/webhooks/deliveries/{delivery_id}/retry
```

---

## Support

- **Documentation:** https://docs.baraka.co/webhooks
- **API Status:** https://status.baraka.co
- **Support Email:** api-support@baraka.co
- **Developer Discord:** https://discord.gg/baraka-dev

---

**Happy Integrating!**
