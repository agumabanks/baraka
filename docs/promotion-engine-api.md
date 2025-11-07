# Promotion Engine API Documentation

## Overview

The Promotion & Discount Engine is a comprehensive system for managing promotional campaigns, tracking customer milestones, calculating ROI, and integrating with the existing logistics pricing infrastructure. This document provides complete API documentation for developers integrating with the system.

## Table of Contents

1. [Base Configuration](#base-configuration)
2. [Authentication](#authentication)
3. [Promotional Code Management](#promotional-code-management)
4. [Milestone Tracking](#milestone-tracking)
5. [Analytics and ROI](#analytics-and-roi)
6. [Webhook Integration](#webhook-integration)
7. [Event System](#event-system)
8. [Error Handling](#error-handling)
9. [Rate Limiting](#rate-limiting)
10. [Examples](#examples)

## Base Configuration

### Base URL
```
Production: https://api.yourcompany.com/v1
Staging: https://staging-api.yourcompany.com/v1
Development: http://localhost:8000/api/v1
```

### Headers
All requests require the following headers:
```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {api_key}
X-Request-ID: {unique_request_id}
```

### API Versioning
The API uses URL versioning. All endpoints are prefixed with `/v1/`.

## Authentication

### API Key Authentication
```http
POST /v1/auth/login
Content-Type: application/json

{
    "email": "user@company.com",
    "password": "password"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "expires_at": "2025-12-07T02:40:12Z",
        "user": {
            "id": 1,
            "email": "user@company.com",
            "role": "admin"
        }
    }
}
```

Use the returned token in subsequent requests:
```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

## Promotional Code Management

### Validate Promotional Code

**Endpoint:** `POST /v1/promotions/validate`

**Description:** Validates a promotional code and returns eligibility information.

**Request Body:**
```json
{
    "code": "SAVE20",
    "customer_id": 123,
    "order_data": {
        "total_amount": 150.00,
        "shipping_cost": 15.00,
        "dimensions": {
            "length_cm": 30,
            "width_cm": 20,
            "height_cm": 10
        }
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "valid": true,
        "promotion": {
            "id": 1,
            "name": "Save 20% on orders over $100",
            "campaign_type": "percentage",
            "value": 20,
            "promo_code": "SAVE20",
            "effective_from": "2025-11-01T00:00:00Z",
            "effective_to": "2025-12-31T23:59:59Z",
            "usage_limit": 1000,
            "usage_count": 245,
            "customer_eligibility": {
                "customer_types": ["premium", "standard"],
                "minimum_order_value": 100
            }
        },
        "discount_calculation": {
            "applicable": true,
            "discount_amount": 30.00,
            "final_amount": 120.00,
            "discount_percentage": 20.0,
            "max_discount_applied": false
        },
        "validation_details": {
            "customer_eligible": true,
            "order_meets_requirements": true,
            "usage_limit_available": true,
            "within_timeframe": true
        }
    },
    "message": "Promo code is valid"
}
```

**Error Response (Invalid Code):**
```json
{
    "success": false,
    "data": null,
    "message": "Invalid promo code",
    "error": "Promotional code 'SAVE20' not found or expired",
    "error_code": "INVALID_PROMO_CODE"
}
```

### Apply Promotional Discount

**Endpoint:** `POST /v1/promotions/apply-discount`

**Description:** Applies a promotional discount to an order.

**Request Body:**
```json
{
    "campaign_type": "percentage",
    "amount": 150.00,
    "customer_id": 123,
    "context_data": {
        "percentage": 20,
        "max_discount": 50.00,
        "shipping_cost": 15.00
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "type": "percentage",
        "value": 20,
        "discount_amount": 30.00,
        "final_amount": 120.00,
        "percentage_saved": 20.0,
        "discount_details": {
            "base_amount": 150.00,
            "discount_rate": 20.0,
            "cap_applied": false,
            "stacking_allowed": true
        }
    },
    "message": "Promotional discount applied successfully"
}
```

### Generate Promotion Code

**Endpoint:** `POST /v1/promotions/generate-code`

**Description:** Generates new promotional codes with specified patterns.

**Request Body:**
```json
{
    "template": {
        "type": "random",
        "length": 8,
        "charset": "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
    },
    "constraints": {
        "max_attempts": 10,
        "unique_only": true
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "code": "SAVE2024A",
        "template_used": {
            "type": "random",
            "length": 8,
            "attempts": 1
        },
        "validation": {
            "unique": true,
            "pattern_valid": true
        }
    },
    "message": "Promotion code generated successfully"
}
```

## Milestone Tracking

### Track Milestone Progress

**Endpoint:** `POST /v1/milestones/track`

**Description:** Tracks customer milestone progress based on shipment data.

**Request Body:**
```json
{
    "customer_id": 123,
    "shipment_data": {
        "weight": 10.5,
        "volume": 5.2,
        "value": 250.00,
        "service_type": "express"
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "milestones_achieved": [
            {
                "id": 1,
                "type": "shipment_count",
                "value": 100,
                "title": "100 Shipments",
                "achieved_at": "2025-11-07T02:40:12Z",
                "reward_given": true,
                "reward_details": {
                    "type": "percentage_discount",
                    "value": 15,
                    "description": "15% off next order"
                }
            }
        ],
        "progress_updated": true,
        "next_milestones": [
            {
                "type": "shipment_count",
                "current": 100,
                "next_threshold": 250,
                "remaining": 150,
                "progress_percentage": 40.0
            }
        ]
    },
    "message": "Milestone progress tracked successfully"
}
```

### Get Milestone Recommendations

**Endpoint:** `GET /v1/milestones/recommendations/{customer_id}`

**Description:** Gets personalized milestone recommendations for a customer.

**Response:**
```json
{
    "success": true,
    "data": {
        "customer_id": 123,
        "current_milestones": [
            {
                "type": "shipment_count",
                "achieved": 5,
                "next_threshold": 10,
                "progress": 50.0
            }
        ],
        "recommendations": [
            {
                "type": "shipment_count",
                "description": "Ship 5 more packages to reach 10 shipments",
                "reward": "10% discount on next order",
                "urgency": "medium"
            }
        ],
        "personalized_offers": [
            {
                "offer_type": "volume_discount",
                "title": "Volume Shipping Discount",
                "description": "Get 15% off when shipping 20+ packages this month",
                "eligible": true
            }
        ]
    },
    "message": "Milestone recommendations retrieved successfully"
}
```

## Analytics and ROI

### Get Promotion ROI

**Endpoint:** `GET /v1/analytics/roi/{promotion_id}`

**Query Parameters:**
- `timeframe`: `7d`, `30d`, `90d`, `1y` (default: `30d`)
- `detailed`: `true` or `false` (default: `true`)

**Response:**
```json
{
    "success": true,
    "data": {
        "promotion_id": 1,
        "timeframe": "30d",
        "roi_metrics": {
            "roi_percentage": 145.5,
            "revenue_impact": 2500.00,
            "cost_impact": 1750.00,
            "net_profit": 750.00,
            "break_even_point": 12.5
        },
        "performance_metrics": {
            "total_uses": 156,
            "unique_customers": 89,
            "conversion_rate": 0.23,
            "average_order_value": 145.50,
            "customer_lifetime_value": 850.00
        },
        "trends": {
            "roi_trend": "improving",
            "usage_trend": "stable",
            "customer_satisfaction": 4.2
        },
        "comparisons": {
            "vs_previous_period": {
                "roi_change": "+12.3%",
                "usage_change": "+5.7%",
                "satisfaction_change": "+0.3"
            },
            "vs_campaign_average": {
                "roi_difference": "+8.5%",
                "usage_difference": "+15.2%"
            }
        }
    },
    "message": "Promotion ROI data retrieved successfully"
}
```

### Get Segment Performance

**Endpoint:** `GET /v1/analytics/segments`

**Query Parameters:**
- `segment_type`: `customer_type`, `region`, `industry`, `size` (default: `customer_type`)

**Response:**
```json
{
    "success": true,
    "data": {
        "segment_type": "customer_type",
        "segments": {
            "premium": {
                "count": 234,
                "total_roi": 167.5,
                "avg_conversion_rate": 0.28,
                "total_revenue": 45230.00,
                "roi_trend": "improving"
            },
            "standard": {
                "count": 567,
                "total_roi": 98.2,
                "avg_conversion_rate": 0.15,
                "total_revenue": 34560.00,
                "roi_trend": "stable"
            },
            "basic": {
                "count": 123,
                "total_roi": 67.8,
                "avg_conversion_rate": 0.08,
                "total_revenue": 8950.00,
                "roi_trend": "declining"
            }
        },
        "insights": [
            "Premium customers show 70% higher ROI than average",
            "Consider targeted campaigns for basic tier customers",
            "Standard segment shows stable performance with growth potential"
        ]
    },
    "message": "Segment performance data retrieved successfully"
}
```

### Run A/B Test

**Endpoint:** `POST /v1/analytics/ab-test`

**Request Body:**
```json
{
    "test_name": "Discount Percentage Optimization",
    "variants": {
        "control": {
            "discount_percentage": 10,
            "description": "10% discount"
        },
        "treatment": {
            "discount_percentage": 15,
            "description": "15% discount"
        }
    },
    "eligibility_criteria": {
        "customer_types": ["premium", "standard"],
        "min_total_spent": 100.00,
        "min_shipments": 5
    },
    "success_metric": "conversion_rate",
    "duration_days": 14
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "test_id": "test_123",
        "test_name": "Discount Percentage Optimization",
        "status": "active",
        "started_at": "2025-11-07T02:40:12Z",
        "estimated_completion": "2025-11-21T02:40:12Z",
        "variants": {
            "control": {
                "name": "control",
                "discount_percentage": 10,
                "traffic_allocation": 50,
                "enrolled_customers": 145
            },
            "treatment": {
                "name": "treatment",
                "discount_percentage": 15,
                "traffic_allocation": 50,
                "enrolled_customers": 152
            }
        },
        "success_criteria": {
            "metric": "conversion_rate",
            "target_improvement": "10%",
            "confidence_level": 95
        }
    },
    "message": "A/B test started successfully"
}
```

### Get A/B Test Results

**Endpoint:** `GET /v1/analytics/ab-test/{test_id}`

**Response:**
```json
{
    "success": true,
    "data": {
        "test_id": "test_123",
        "status": "completed",
        "started_at": "2025-11-01T00:00:00Z",
        "ended_at": "2025-11-15T00:00:00Z",
        "results": {
            "control": {
                "conversions": 23,
                "total_users": 145,
                "conversion_rate": 0.1586,
                "avg_order_value": 135.50
            },
            "treatment": {
                "conversions": 31,
                "total_users": 152,
                "conversion_rate": 0.2039,
                "avg_order_value": 142.75
            }
        },
        "statistical_analysis": {
            "winner": "treatment",
            "improvement": "+28.6%",
            "confidence_level": 96.2,
            "statistically_significant": true,
            "p_value": 0.034
        },
        "recommendation": {
            "action": "implement_treatment",
            "reason": "Treatment shows statistically significant improvement",
            "implementation_notes": "Roll out 15% discount to all eligible customers"
        }
    },
    "message": "A/B test results retrieved successfully"
}
```

## Webhook Integration

### Register Webhook Endpoint

**Endpoint:** `POST /v1/webhooks/register`

**Request Body:**
```json
{
    "url": "https://yourapp.com/webhooks/promotions",
    "events": [
        "promotion_activated",
        "milestone_achieved",
        "roi_threshold_breach"
    ],
    "secret": "your_webhook_secret",
    "name": "My App Webhook",
    "description": "Receives promotion events for our CRM integration"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "webhook_id": "webhook_abc123",
        "connectivity_test": {
            "reachable": true,
            "status_code": 200,
            "response_time_ms": 234
        },
        "registered_events": [
            "promotion_activated",
            "milestone_achieved",
            "roi_threshold_breach"
        ]
    },
    "message": "Webhook registered successfully"
}
```

### Webhook Event Payloads

#### Promotion Activated
```json
{
    "event_type": "promotion_activated",
    "timestamp": "2025-11-07T02:40:12Z",
    "data": {
        "promotion_id": 1,
        "promotion_name": "Black Friday 50% Off",
        "campaign_type": "percentage",
        "value": 50,
        "promo_code": "BF50",
        "effective_from": "2025-11-25T00:00:00Z",
        "effective_to": "2025-11-27T23:59:59Z",
        "usage_limit": 10000,
        "customer_eligibility": {
            "customer_types": ["premium", "standard"]
        }
    },
    "signature": "sha256_signature_here"
}
```

#### Milestone Achieved
```json
{
    "event_type": "milestone_achieved",
    "timestamp": "2025-11-07T02:40:12Z",
    "data": {
        "customer_id": 123,
        "customer_name": "John Doe",
        "customer_email": "john@example.com",
        "milestone": {
            "id": 1,
            "type": "shipment_count",
            "value": 100,
            "achieved_at": "2025-11-07T02:40:12Z",
            "reward_given": true,
            "reward_details": {
                "type": "percentage_discount",
                "value": 15,
                "description": "15% off next order"
            }
        },
        "is_major": true,
        "achievement_details": {
            "achievement_title": "100 Shipments Milestone",
            "celebration_level": "high"
        }
    },
    "signature": "sha256_signature_here"
}
```

#### ROI Threshold Breached
```json
{
    "event_type": "roi_threshold_breach",
    "timestamp": "2025-11-07T02:40:12Z",
    "data": {
        "promotion_id": 1,
        "promotion_name": "Holiday Season Sale",
        "breach_type": "high_performance",
        "current_roi": 185.5,
        "threshold": 150.0,
        "breach_magnitude": "significant",
        "roi_analysis": {
            "revenue_impact": 15000.00,
            "cost_impact": 8500.00,
            "net_impact": 6500.00,
            "conversion_rate": 0.28
        },
        "recommended_actions": [
            "Scale up the successful promotion strategy",
            "Expand to similar customer segments",
            "Consider permanent implementation"
        ]
    },
    "signature": "sha256_signature_here"
}
```

## Event System

### Event Listeners

The promotion engine fires several events that you can listen for:

#### PromotionActivated
```php
Event::listen(PromotionActivated::class, function ($event) {
    // Handle promotion activation
    Log::info('Promotion activated', [
        'promotion_id' => $event->promotion->id,
        'name' => $event->promotion->name
    ]);
});
```

#### MilestoneAchieved
```php
Event::listen(MilestoneAchieved::class, function ($event) {
    // Send celebration notifications
    $notificationService->sendCelebrationNotification(
        $event->customer,
        $event->milestone
    );
});
```

#### RoiThresholdBreached
```php
Event::listen(RoiThresholdBreached::class, function ($event) {
    // Send alerts to stakeholders
    $this->sendRoiAlert($event->promotion, $event->breachType);
});
```

## Error Handling

### Standard Error Response Format
```json
{
    "success": false,
    "data": null,
    "message": "Human readable error message",
    "error": {
        "code": "ERROR_CODE",
        "details": "Additional error details",
        "field": "field_name" // Optional, for validation errors
    },
    "request_id": "req_123456789"
}
```

### Common Error Codes

| Code | Description | HTTP Status |
|------|-------------|-------------|
| `INVALID_PROMO_CODE` | Promotional code not found or expired | 404 |
| `CUSTOMER_NOT_ELIGIBLE` | Customer doesn't meet promotion criteria | 422 |
| `USAGE_LIMIT_EXCEEDED` | Promotion usage limit has been reached | 422 |
| `PROMOTION_EXPIRED` | Promotion has expired | 422 |
| `DISCOUNT_STACKING_NOT_ALLOWED` | Cannot stack with existing discounts | 422 |
| `INVALID_REQUEST_DATA` | Request validation failed | 422 |
| `RATE_LIMIT_EXCEEDED` | Too many requests | 429 |
| `UNAUTHORIZED` | Authentication required | 401 |
| `FORBIDDEN` | Insufficient permissions | 403 |
| `INTERNAL_ERROR` | Server error | 500 |

## Rate Limiting

The API implements rate limiting to prevent abuse:

### Rate Limits by Endpoint

| Endpoint | Limit | Window |
|----------|-------|--------|
| `POST /v1/promotions/validate` | 60 requests | 1 minute |
| `POST /v1/promotions/apply-discount` | 30 requests | 1 minute |
| `POST /v1/milestones/track` | 100 requests | 1 minute |
| `GET /v1/analytics/*` | 20 requests | 1 minute |
| `POST /v1/webhooks/*` | 10 requests | 1 minute |

### Rate Limit Headers
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1634567890
```

### Rate Limit Exceeded Response
```json
{
    "success": false,
    "message": "Rate limit exceeded",
    "error": {
        "code": "RATE_LIMIT_EXCEEDED",
        "limit": 60,
        "window": "1 minute",
        "retry_after": 30
    }
}
```

## Examples

### Complete Promotion Workflow

1. **Validate and Apply Discount**
```javascript
// Step 1: Validate promotion code
const validateResponse = await fetch('/v1/promotions/validate', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer your_token'
    },
    body: JSON.stringify({
        code: 'SAVE20',
        customer_id: 123,
        order_data: {
            total_amount: 150.00
        }
    })
});

const validation = await validateResponse.json();

if (validation.success) {
    // Step 2: Apply discount
    const applyResponse = await fetch('/v1/promotions/apply-discount', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer your_token'
        },
        body: JSON.stringify({
            campaign_type: validation.data.promotion.campaign_type,
            amount: 150.00,
            customer_id: 123,
            context_data: {
                percentage: validation.data.promotion.value
            }
        })
    });
    
    const discount = await applyResponse.json();
    console.log('Final amount:', discount.data.final_amount);
}
```

2. **Track Customer Milestone**
```javascript
// Track milestone after order completion
const milestoneResponse = await fetch('/v1/milestones/track', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer your_token'
    },
    body: JSON.stringify({
        customer_id: 123,
        shipment_data: {
            weight: 10.5,
            volume: 5.2,
            value: 250.00
        }
    })
});

const milestone = await milestoneResponse.json();
if (milestone.data.milestones_achieved.length > 0) {
    console.log('New milestone achieved:', milestone.data.milestones_achieved[0]);
}
```

3. **Monitor ROI Analytics**
```javascript
// Get promotion ROI metrics
const roiResponse = await fetch('/v1/analytics/roi/1?timeframe=30d', {
    headers: {
        'Authorization': 'Bearer your_token'
    }
});

const roi = await roiResponse.json();
console.log('ROI:', roi.data.roi_metrics.roi_percentage + '%');
```

### Webhook Integration Example (Node.js)

```javascript
const express = require('express');
const crypto = require('crypto');
const app = express();

app.use(express.json());

// Webhook endpoint
app.post('/webhooks/promotions', (req, res) => {
    const signature = req.headers['x-webhook-signature'];
    const secret = 'your_webhook_secret';
    
    // Verify signature
    const expectedSignature = crypto
        .createHmac('sha256', secret)
        .update(JSON.stringify(req.body))
        .digest('hex');
    
    if (signature !== expectedSignature) {
        return res.status(401).json({ error: 'Invalid signature' });
    }
    
    // Process event
    const { event_type, data } = req.body;
    
    switch (event_type) {
        case 'promotion_activated':
            console.log('New promotion activated:', data.promotion_name);
            // Update CRM, send notifications, etc.
            break;
            
        case 'milestone_achieved':
            console.log('Customer milestone achieved:', data.customer_name);
            // Send congratulations, update customer profile
            break;
            
        case 'roi_threshold_breach':
            console.log('ROI threshold breach:', data.breach_type);
            // Send alerts to stakeholders
            break;
    }
    
    res.json({ received: true });
});

app.listen(3000, () => {
    console.log('Webhook server running on port 3000');
});
```

## Support and Contact

For technical support or questions about the API:

- **Email**: api-support@yourcompany.com
- **Documentation**: https://docs.yourcompany.com/promotion-engine
- **Status Page**: https://status.yourcompany.com

## Changelog

### v1.0.0 (2025-11-07)
- Initial release
- Promotion code management
- Milestone tracking
- ROI analytics
- Webhook integration
- A/B testing framework

---

*This documentation is maintained by the Engineering Team and updated with each API release.*