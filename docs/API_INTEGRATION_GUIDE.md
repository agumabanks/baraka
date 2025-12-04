# Baraka Logistics API Integration Guide

## Overview
This document provides comprehensive API integration capabilities for third-party merchants and customers to create shipments, track status, and manage accounts.

## Authentication

### API Key Authentication
```bash
curl -X GET "https://api.baraka.sanaa.co/api/v1/shipments" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json"
```

### OAuth2 (For Partner Integrations)
1. Register your application in the admin dashboard
2. Obtain client credentials
3. Exchange for access token

## Endpoints

### Shipments

#### Create Shipment
```http
POST /api/v1/shipments
```
```json
{
  "sender": {
    "name": "John Doe",
    "phone": "+256700000000",
    "address": "123 Main St, Kampala"
  },
  "receiver": {
    "name": "Jane Smith",
    "phone": "+256700000001",
    "address": "456 Oak Ave, Goma"
  },
  "parcels": [{
    "weight_kg": 5.0,
    "length_cm": 30,
    "width_cm": 20,
    "height_cm": 15,
    "description": "Electronics"
  }],
  "service_level": "express",
  "payment_type": "prepaid",
  "declared_value": 500.00,
  "insurance_type": "basic"
}
```

#### Get Shipment
```http
GET /api/v1/shipments/{tracking_number}
```

#### List Shipments
```http
GET /api/v1/shipments?status=in_transit&page=1&per_page=50
```

#### Cancel Shipment
```http
POST /api/v1/shipments/{tracking_number}/cancel
```

### Tracking

#### Public Tracking (No Auth Required)
```http
GET /api/v1/tracking/{tracking_number}
```

### Quotes

#### Get Quote
```http
POST /api/v1/quotes
```
```json
{
  "origin_branch_id": 1,
  "dest_branch_id": 2,
  "parcels": [{
    "weight_kg": 5.0,
    "length_cm": 30,
    "width_cm": 20,
    "height_cm": 15
  }],
  "service_level": "standard"
}
```

### Webhooks

#### Register Webhook
```http
POST /api/v1/webhooks
```
```json
{
  "url": "https://your-domain.com/webhook",
  "events": ["shipment.created", "shipment.delivered", "shipment.exception"],
  "secret": "your_webhook_secret"
}
```

#### Webhook Events
- `shipment.created` - New shipment booked
- `shipment.picked_up` - Shipment collected
- `shipment.in_transit` - In transit to destination
- `shipment.out_for_delivery` - With driver for delivery
- `shipment.delivered` - Successfully delivered
- `shipment.exception` - Delivery exception occurred
- `shipment.returned` - Returned to sender
- `cod.collected` - COD payment collected
- `invoice.created` - Invoice generated

### EDI Integration

#### Submit EDI Document
```http
POST /api/v1/edi/process
Content-Type: text/plain
```
Supports X12 and EDIFACT formats.

## Rate Limits
- Standard: 60 requests/minute
- Premium: 300 requests/minute
- Enterprise: Unlimited (contact sales)

## Error Codes
| Code | Description |
|------|-------------|
| 400  | Bad Request - Invalid parameters |
| 401  | Unauthorized - Invalid API key |
| 403  | Forbidden - Insufficient permissions |
| 404  | Not Found - Resource doesn't exist |
| 422  | Validation Error - Check error details |
| 429  | Rate Limited - Slow down requests |
| 500  | Server Error - Contact support |

## SDKs
- PHP: `composer require baraka/logistics-sdk`
- Node.js: `npm install @baraka/logistics-sdk`
- Python: `pip install baraka-logistics`

## Support
- Email: api-support@baraka.sanaa.co
- Documentation: https://docs.baraka.sanaa.co
- Status Page: https://status.baraka.sanaa.co
