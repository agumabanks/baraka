# Webhook & Event Streaming API Documentation

## Overview
This API enables real-time integration with external systems through webhooks and event streams.

## Authentication
All webhook management endpoints require authentication and authorization. Use Bearer token authentication.

```bash
Authorization: Bearer {api_token}
```

## Webhook Management Endpoints

### Admin Webhook Management (Requires `auth:admin`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/webhooks` | Paginated list of all webhook endpoints |
| POST | `/api/v1/admin/webhooks` | Create endpoint (url, events, active flag) |
| GET | `/api/v1/admin/webhooks/{endpoint}` | Show endpoint metadata + retry policy |
| PUT | `/api/v1/admin/webhooks/{endpoint}` | Update url/events/active |
| DELETE | `/api/v1/admin/webhooks/{endpoint}` | Remove endpoint |
| POST | `/api/v1/admin/webhooks/{endpoint}/rotate-secret` | Generate new HMAC secret |
| GET | `/api/v1/admin/webhooks/{endpoint}/deliveries` | Delivery history & statuses |
| POST | `/api/v1/admin/webhooks/deliveries/{delivery}/retry` | Force retry of failed delivery |
| POST | `/api/v1/admin/webhooks/{endpoint}/test` | Queue `webhook.test` event |
| GET | `/api/v1/admin/webhooks/health/status` | Aggregated health stats |

All responses include `secret_key`, `retry_policy`, and `failure_count` fields so you can rotate keys and monitor unhealthy endpoints without touching the database manually.

### 1. List Webhooks
**GET** `/api/v1/webhooks`

List all configured webhook endpoints with pagination.

**Query Parameters:**
- `page` (optional, default: 1)
- `per_page` (optional, default: 20)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Order System",
      "url": "https://example.com/webhooks/baraka",
      "events": ["shipment.created", "shipment.updated"],
      "active": true,
      "failure_count": 0,
      "last_triggered_at": "2025-11-10T15:30:00Z",
      "created_at": "2025-11-10T10:00:00Z"
    }
  ],
  "pagination": {
    "total": 10,
    "per_page": 20,
    "current_page": 1
  }
}
```

---

### 2. Create Webhook
**POST** `/api/v1/webhooks`

Register a new webhook endpoint.

**Request Body:**
```json
{
  "name": "Order Management System",
  "url": "https://api.example.com/webhooks/baraka",
  "events": ["shipment.created", "shipment.updated", "delivery.completed"],
  "active": true
}
```

**Validation Rules:**
- `name`: required, string, max 255
- `url`: required, valid URL
- `events`: required, array with at least 1 event
- `active`: optional, boolean

**Response:** `201 Created`
```json
{
  "id": 1,
  "name": "Order Management System",
  "url": "https://api.example.com/webhooks/baraka",
  "events": ["shipment.created", "shipment.updated", "delivery.completed"],
  "active": true,
  "secret_key": "[REDACTED - secret key returned only at creation]",
  "created_at": "2025-11-10T15:45:00Z"
}
```

---

### 3. Get Webhook Details
**GET** `/api/v1/webhooks/{id}`

Retrieve detailed information about a webhook including recent deliveries.

**Response:**
```json
{
  "id": 1,
  "name": "Order Management System",
  "url": "https://api.example.com/webhooks/baraka",
  "events": ["shipment.created", "shipment.updated"],
  "active": true,
  "secret_key": "[REDACTED - secret key returned only at creation]",
  "failure_count": 2,
  "last_triggered_at": "2025-11-10T15:30:00Z",
  "deliveries": [
    {
      "id": 1,
      "event_type": "shipment.updated",
      "http_status": 200,
      "delivered_at": "2025-11-10T15:30:15Z",
      "attempts": 1
    }
  ]
}
```

---

### 4. Update Webhook
**PUT/PATCH** `/api/v1/webhooks/{id}`

Update webhook configuration.

**Request Body:**
```json
{
  "name": "Updated Name",
  "url": "https://api.example.com/webhooks/baraka-v2",
  "events": ["shipment.created", "shipment.updated"],
  "active": false
}
```

**Response:** `200 OK`

---

### 5. Delete Webhook
**DELETE** `/api/v1/webhooks/{id}`

Remove a webhook endpoint permanently.

**Response:** `204 No Content`

---

### 6. Rotate Webhook Secret
**POST** `/api/v1/webhooks/{id}/rotate-secret`

Generate a new secret key for webhook signing.

**Response:**
```json
{
  "message": "Secret rotated successfully",
  "new_secret": "[REDACTED - secret key returned only at creation]"
}
```

---

### 7. Get Webhook Deliveries
**GET** `/api/v1/webhooks/{id}/deliveries`

List all deliveries for a webhook with status and retry information.

**Query Parameters:**
- `status` (optional): `delivered`, `failed`, `pending`
- `page` (optional, default: 1)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "event_type": "shipment.updated",
      "payload": { "shipment_id": 123, "status": "in_transit" },
      "http_status": 200,
      "response": { "success": true },
      "attempts": 1,
      "delivered_at": "2025-11-10T15:30:15Z",
      "created_at": "2025-11-10T15:30:00Z"
    }
  ]
}
```

---

### 8. Retry Webhook Delivery
**POST** `/api/v1/webhooks/deliveries/{delivery_id}/retry`

Manually retry a failed or pending webhook delivery.

**Response:**
```json
{
  "message": "Delivery queued for retry",
  "delivery": {
    "id": 1,
    "attempts": 1,
    "next_retry_at": "2025-11-10T15:32:00Z"
  }
}
```

---

### 9. Test Webhook
**POST** `/api/v1/webhooks/{id}/test`

Send a test webhook to verify endpoint configuration.

**Response:**
```json
{
  "message": "Test webhook queued"
}
```

The test webhook will be sent with `"test": true` in the payload.

---

### 10. Webhook Health Status
**GET** `/api/v1/webhooks/health`

Get health status of all configured webhooks.

**Response:**
```json
[
  {
    "endpoint_id": 1,
    "name": "Order System",
    "url": "https://example.com/webhooks",
    "is_healthy": true,
    "failure_count": 0,
    "last_triggered_at": "2025-11-10T15:30:00Z",
    "recent_success_rate": 100.0
  }
]
```

---

## Webhook Event Types

### Shipment Events
- `shipment.created` - New shipment created
- `shipment.updated` - Shipment details updated
- `shipment.status_changed` - Status transition
- `shipment.scanned` - Shipment scanned at location
- `shipment.delivered` - Delivery completed
- `shipment.exception` - Exception/issue with shipment

### Dispatch Events
- `dispatch.assigned` - Shipment assigned to driver
- `dispatch.picked_up` - Pickup completed
- `dispatch.in_transit` - Vehicle in motion
- `dispatch.completed` - Delivery completed

### Branch Events
- `branch.capacity_exceeded` - Capacity threshold reached
- `branch.maintenance` - Maintenance mode activated
- `branch.performance` - Performance metrics updated

### System Events
- `webhook.test` - Test webhook delivery

---

## Webhook Payload Format

### Standard Headers
```
X-Webhook-Signature: sha256=hmac_signature
X-Event-Type: shipment.updated
X-Delivery-ID: 12345
X-Timestamp: 2025-11-10T15:30:00Z
Content-Type: application/json
```

### Payload Structure
```json
{
  "event_type": "shipment.updated",
  "timestamp": "2025-11-10T15:30:00Z",
  "delivery_id": "12345",
  "data": {
    "id": 123,
    "tracking_number": "TRK123456789",
    "status": "in_transit",
    "current_location": "Dubai Hub",
    "estimated_delivery": "2025-11-12T18:00:00Z"
  }
}
```

---

## Signature Verification

Verify webhook signatures using HMAC-SHA256:

```php
// PHP Example
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$secret = 'your_webhook_secret_key';

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (hash_equals($expected, $signature)) {
    // Signature is valid
} else {
    // Invalid signature - reject
}
```

```javascript
// JavaScript Example
const crypto = require('crypto');

function verifyWebhookSignature(payload, signature, secret) {
  const expected = 'sha256=' + 
    crypto.createHmac('sha256', secret)
      .update(payload)
      .digest('hex');
  
  return crypto.timingSafeEqual(
    Buffer.from(signature),
    Buffer.from(expected)
  );
}
```

---

## Retry Policy

Failed webhooks are automatically retried with exponential backoff:

**Default Policy:**
- Max attempts: 5
- Initial delay: 60 seconds
- Backoff multiplier: 2x
- Max delay: 3600 seconds (1 hour)

**Retry Schedule:**
1. Attempt 1: Immediate
2. Attempt 2: +60 seconds
3. Attempt 3: +120 seconds
4. Attempt 4: +240 seconds
5. Attempt 5: +480 seconds

---

## Event Streams (Real-time Updates)

Subscribe to real-time events via WebSockets or Server-Sent Events.

### WebSocket Connection
```javascript
const ws = new WebSocket('wss://baraka.app/events');

ws.onmessage = (event) => {
  const message = JSON.parse(event.data);
  console.log('Event:', message.event_type, message.data);
};
```

### Available Channels
- `events.shipment.{shipment_id}` - All events for a specific shipment
- `events.dispatch.{dispatch_id}` - All events for a dispatch
- `events.branch.{branch_id}` - All events for a branch
- `events.user.{user_id}` - All events for a user

### Event Stream Payload
```json
{
  "event_type": "shipment.updated",
  "aggregate_id": "123",
  "aggregate_type": "shipment",
  "actor_id": "456",
  "payload": {
    "status": "in_transit"
  },
  "timestamp": "2025-11-10T15:30:00Z"
}
```

---

## Rate Limiting

Webhook endpoints are rate-limited:
- **Default**: 1,000 requests/hour
- **Webhooks**: 10,000 requests/hour
- **Response Headers**:
  - `X-RateLimit-Limit`: Request limit
  - `X-RateLimit-Remaining`: Remaining requests
  - `Retry-After`: Seconds to wait before retry

---

## EDI Transactions API

The EDI API accepts common document types (850 Purchase Order, 856 Advance Ship Notice, 997 Functional Acknowledgement) and returns a normalized payload plus an optional 997 acknowledgement.

### Submit 850 or 856
**POST** `/api/v1/edi/{documentType}`  
Headers: `Authorization: Bearer {api_token}`, `Content-Type: application/json`

```json
{
  "payload": {
    "purchase_order": {
      "number": "PO-10045",
      "buyer": { "name": "Acme" },
      "items": [
        { "sku": "ABC123", "qty": 10 }
      ]
    }
  }
}
```

**Response** `202 Accepted`
```json
{
  "success": true,
  "transaction_id": 42,
  "document_type": "850",
  "status": "received",
  "acknowledgement": {
    "document_type": "997",
    "acknowledgement_status": "AC",
    "control_number": "000532",
    "original_document_number": "PO-10045"
  }
}
```

### Submit 997 (Acknowledgement)
**POST** `/api/v1/edi/997`

```json
{
  "payload": {
    "acknowledgement": {
      "document_number": "PO-10045",
      "status": "AC"
    }
  }
}
```

### Retrieve Transactions / ACK
- `GET /api/v1/edi/transactions/{id}` – View normalized payload & status  
- `GET /api/v1/edi/transactions/{id}/ack` – Retrieve generated 997 payload  
- Admin list: `GET /api/v1/admin/edi/transactions?document_type=850&status=received`

All transactions persist to `edi_transactions`, capturing raw payload, normalized payload, ack payload, and correlation IDs for troubleshooting.

---

## Error Responses

### 400 Bad Request
```json
{
  "error": "Invalid request",
  "details": {
    "url": ["The url field must be a valid URL"]
  }
}
```

### 401 Unauthorized
```json
{
  "error": "Unauthenticated"
}
```

### 403 Forbidden
```json
{
  "error": "Unauthorized access to webhook"
}
```

### 404 Not Found
```json
{
  "error": "Webhook endpoint not found"
}
```

### 429 Too Many Requests
```json
{
  "error": "Rate limit exceeded",
  "retry_after": 60
}
```

### 500 Server Error
```json
{
  "error": "Internal server error",
  "message": "Failed to process webhook"
}
```

---

## Best Practices

1. **Verify Signatures**: Always verify webhook signatures to ensure authenticity
2. **Idempotency**: Use delivery ID in your webhook handler for idempotent processing
3. **Timeouts**: Respond within 30 seconds; use background jobs for long operations
4. **Logging**: Log all webhook events for debugging and compliance
5. **Error Handling**: Return non-2xx status codes to trigger retries
6. **Secret Rotation**: Rotate secrets regularly for enhanced security
7. **URL Validation**: Use HTTPS and validate certificate chains
8. **Rate Limiting**: Plan for burst traffic patterns

---

## Support

For issues or questions regarding webhooks:
- Check webhook health: `GET /api/v1/webhooks/health`
- Review delivery logs: `GET /api/v1/webhooks/{id}/deliveries`
- Contact support with endpoint URL and delivery ID
