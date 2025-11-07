# Contract Management API Documentation

## Base URL
```
Production: https://your-domain.com/api/v10
Staging: https://staging.your-domain.com/api/v10
```

## Authentication
All API endpoints require authentication via Bearer token in the Authorization header:
```
Authorization: Bearer {your_access_token}
```

## Response Format
All responses follow a consistent format:
```json
{
    "success": true,
    "message": "Operation successful",
    "data": { ... },
    "meta": {
        "timestamp": "2024-11-07T02:12:00Z",
        "request_id": "uuid"
    }
}
```

## Error Response Format
```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field_name": ["Validation error message"]
    },
    "code": 422
}
```

## Rate Limiting
- 100 requests per minute per user
- 1000 requests per hour per IP
- 10000 requests per day per user

---

## Contract Management Endpoints

### GET /contracts
Retrieve all contracts with filtering and pagination.

**Query Parameters:**
- `status` (string, optional): Filter by status (active, draft, expired, suspended, all)
- `customer_id` (integer, optional): Filter by customer ID
- `search` (string, optional): Search in contract name and customer details
- `per_page` (integer, optional): Number of items per page (default: 20, max: 100)

**Response:**
```json
{
    "success": true,
    "data": {
        "contracts": [
            {
                "id": 1,
                "name": "Premium Service Contract",
                "customer": {
                    "id": 1,
                    "company_name": "ABC Corp",
                    "contact_person": "John Doe"
                },
                "contract_type": "premium",
                "status": "active",
                "start_date": "2024-01-01",
                "end_date": "2024-12-31",
                "volume_progress": {
                    "current_volume": 750,
                    "required_volume": 1000,
                    "progress_percentage": 75.0,
                    "volume_commitment_met": false
                }
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 150,
            "last_page": 8
        }
    }
}
```

### POST /contracts
Create a new contract.

**Request Body:**
```json
{
    "customer_id": 1,
    "name": "New Premium Contract",
    "start_date": "2024-01-01",
    "end_date": "2024-12-31",
    "contract_type": "premium",
    "volume_commitment": 500,
    "volume_commitment_period": "monthly"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Contract created successfully",
    "data": {
        "contract": {
            "id": 1,
            "name": "New Premium Contract",
            "status": "draft",
            "created_at": "2024-11-07T02:12:00Z"
        }
    }
}
```

### GET /contracts/{id}
Retrieve a specific contract by ID.

**Response:**
```json
{
    "success": true,
    "data": {
        "contract": {
            "id": 1,
            "name": "Premium Service Contract",
            "customer": { ... },
            "template": { ... },
            "volume_discounts": [ ... ],
            "compliance_status": { ... },
            "service_level_commitments": [ ... ]
        }
    }
}
```

### PUT /contracts/{id}
Update a contract.

**Request Body:**
```json
{
    "name": "Updated Contract Name",
    "end_date": "2025-01-31",
    "notes": "Updated contract terms"
}
```

### POST /contracts/{id}/activate
Activate a contract.

**Response:**
```json
{
    "success": true,
    "message": "Contract activated successfully",
    "data": {
        "contract": {
            "id": 1,
            "status": "active",
            "activated_at": "2024-11-07T02:12:00Z",
            "activated_by": 1
        }
    }
}
```

### POST /contracts/{id}/renew
Renew a contract.

**Request Body:**
```json
{
    "new_end_date": "2025-12-31",
    "renewal_terms": {
        "volume_commitment_increased": true,
        "new_volume": 1200
    },
    "auto_renewal": true
}
```

### GET /contracts/{id}/compliance
Get contract compliance status.

**Response:**
```json
{
    "success": true,
    "data": {
        "compliance_status": {
            "overall_score": 88.5,
            "breach_count": 1,
            "warning_count": 2,
            "requirements": [
                {
                    "name": "Delivery On-Time Rate",
                    "current_value": 92.0,
                    "target_value": 95.0,
                    "status": "warning",
                    "is_critical": true
                }
            ]
        }
    }
}
```

### POST /contracts/{id}/volume-update
Update contract volume.

**Request Body:**
```json
{
    "volume_increase": 100,
    "shipment_data": {
        "shipment_id": "SHP001",
        "weight_kg": 25.5,
        "destination": "New York, NY"
    }
}
```

**Response:**
```json
{
    "success": true,
    "message": "Contract volume updated successfully",
    "data": {
        "volume_update": {
            "old_volume": 650,
            "new_volume": 750,
            "volume_increase": 100,
            "milestone_achieved": true,
            "tier_progression": {
                "current_tier": "Silver",
                "next_tier": "Gold",
                "remaining_volume": 50
            }
        }
    }
}
```

### GET /contracts/{id}/discounts
Get applicable discounts for a contract.

**Query Parameters:**
- `volume` (integer, required): Shipment volume to calculate discounts for

**Response:**
```json
{
    "success": true,
    "data": {
        "applicable_discounts": {
            "applicable": true,
            "tier_name": "Gold",
            "discount_percentage": 10.0,
            "benefits": [
                "24_7_support",
                "daily_reporting",
                "api_access",
                "custom_integrations"
            ],
            "estimated_savings": 150.75
        }
    }
}
```

### GET /contracts/{id}/tier-progression
Get contract tier progression information.

**Response:**
```json
{
    "success": true,
    "data": {
        "tier_progression": {
            "current_tier": {
                "name": "Silver",
                "volume_requirement": 50,
                "discount_percentage": 5.0,
                "achieved_at": "2024-06-15T10:30:00Z"
            },
            "next_tier": {
                "name": "Gold",
                "volume_requirement": 200,
                "discount_percentage": 10.0,
                "remaining_volume": 75
            },
            "progress_percentage": 37.5,
            "tier_achievements": [
                {
                    "tier_name": "Bronze",
                    "achieved_at": "2024-01-10T14:20:00Z"
                },
                {
                    "tier_name": "Silver",
                    "achieved_at": "2024-06-15T10:30:00Z"
                }
            ]
        }
    }
}
```

### GET /contracts/{id}/summary
Generate contract summary report.

**Response:**
```json
{
    "success": true,
    "data": {
        "contract_summary": {
            "contract_details": { ... },
            "compliance_summary": { ... },
            "volume_summary": { ... },
            "financial_summary": {
                "estimated_monthly_value": 2500.00,
                "potential_savings": 250.00,
                "total_discount_received": 1250.00
            },
            "performance_metrics": {
                "utilization_rate": 75.0,
                "compliance_score": 88.5,
                "on_time_delivery_rate": 92.0
            }
        }
    }
}
```

---

## Contract Template Management Endpoints

### GET /contract-templates
Retrieve all contract templates.

### POST /contract-templates
Create a new contract template.

### GET /contract-templates/{id}
Get specific contract template.

### PUT /contract-templates/{id}
Update contract template.

### DELETE /contract-templates/{id}
Delete contract template.

### POST /contract-templates/{id}/clone
Clone a contract template.

### POST /contract-templates/{id}/generate-contract
Generate contract from template.

---

## Analytics and Reporting Endpoints

### GET /contract-analytics/dashboard
Get contract management dashboard metrics.

**Response:**
```json
{
    "success": true,
    "data": {
        "dashboard": {
            "total_contracts": 450,
            "active_contracts": 380,
            "expiring_contracts": 15,
            "total_contract_value": 2500000.00,
            "average_contract_value": 5555.56,
            "compliance_overview": {
                "average_score": 91.2,
                "breaches_this_month": 3,
                "critical_breaches": 1
            },
            "volume_trends": {
                "total_volume": 15420,
                "monthly_growth": 12.5,
                "tier_distribution": {
                    "bronze": 120,
                    "silver": 180,
                    "gold": 65,
                    "platinum": 15
                }
            }
        }
    }
}
```

### GET /contract-analytics/performance-metrics
Get contract performance metrics.

### GET /contract-analytics/revenue-analysis
Get contract revenue analysis.

### GET /contract-analytics/compliance-trends
Get compliance trend analysis.

### GET /contract-analytics/volume-insights
Get volume and discount insights.

---

## Bulk Operations Endpoints

### POST /contract-bulk/activation
Bulk activate contracts.

**Request Body:**
```json
{
    "contract_ids": [1, 2, 3, 4, 5]
}
```

### POST /contract-bulk/compliance-check
Run compliance checks on multiple contracts.

**Request Body:**
```json
{
    "contract_ids": [1, 2, 3],
    "compliance_types": ["performance", "quality"]
}
```

---

## Webhook Management Endpoints

### GET /contract-webhooks
List contract webhooks.

### POST /contract-webhooks
Create new webhook endpoint.

### PUT /contract-webhooks/{id}
Update webhook endpoint.

### DELETE /contract-webhooks/{id}
Delete webhook endpoint.

### POST /contract-webhooks/{id}/test
Test webhook delivery.

---

## Contract Integration Endpoints

### POST /contract-integration/apply-pricing
Apply contract pricing to billing.

**Request Body:**
```json
{
    "contract_id": 1,
    "shipment_data": {
        "weight_kg": 25.0,
        "distance": 100,
        "service_level": "standard"
    }
}
```

### POST /contract-integration/validate-contract
Validate contract for external systems.

### GET /contract-integration/customer-contracts/{customer}
Get customer's contracts for integration.

### POST /contract-integration/milestone-trigger
Trigger customer milestone manually.

---

## Internal Processing Endpoints

### POST /internal/contracts/auto-renewal
Process automatic contract renewals.

### POST /internal/contracts/expiry-processing
Process contract expirations.

### POST /internal/contracts/compliance-monitoring
Run compliance monitoring.

### POST /internal/contracts/volume-progression
Process volume progression updates.

### POST /internal/contracts/milestone-processing
Process milestone achievements.

### POST /internal/contracts/notification-batch
Send batch notifications.

---

## Webhook Events

### Contract Events
- `contract.activated`
- `contract.expiring`
- `contract.expired`
- `contract.renewed`
- `contract.suspended`

### Compliance Events
- `compliance.breach`
- `compliance.escalated`
- `compliance.resolved`

### Volume Events
- `volume.milestone_achieved`
- `volume.tier_achieved`
- `volume.commitment_reached`

### Webhook Payload Example
```json
{
    "event": "contract.activated",
    "timestamp": "2024-11-07T02:12:00Z",
    "data": {
        "contract": {
            "id": 1,
            "name": "Premium Service Contract",
            "customer_id": 1
        },
        "customer": {
            "company_name": "ABC Corp"
        }
    },
    "webhook_id": "wh_12345"
}
```

---

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad Request - Invalid request format |
| 401 | Unauthorized - Invalid or missing authentication |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource not found |
| 422 | Validation Error - Request validation failed |
| 429 | Rate Limited - Too many requests |
| 500 | Internal Server Error - Server error occurred |
| 503 | Service Unavailable - Service temporarily unavailable |

---

## SDKs and Libraries

### PHP SDK (Laravel)
```php
use YourCompany\ContractSDK\ContractClient;

$client = new ContractClient([
    'base_url' => 'https://api.yourdomain.com/v10',
    'api_key' => 'your_api_key'
]);

// Create contract
$contract = $client->contracts->create([
    'customer_id' => 1,
    'name' => 'New Contract',
    'start_date' => '2024-01-01',
    'end_date' => '2024-12-31'
]);

// Get compliance status
$compliance = $client->contracts->getCompliance(1);
```

### JavaScript SDK
```javascript
import { ContractClient } from '@yourcompany/contract-sdk';

const client = new ContractClient({
    baseURL: 'https://api.yourdomain.com/v10',
    apiKey: 'your_api_key'
});

// Create contract
const contract = await client.contracts.create({
    customerId: 1,
    name: 'New Contract',
    startDate: '2024-01-01',
    endDate: '2024-12-31'
});
```

---

## Testing

### Sandbox Environment
Use the sandbox environment for testing:
```
https://sandbox-api.yourdomain.com/v10
```

### Test Data
- Use customer ID 999 for testing
- All test data is automatically cleaned up daily
- Rate limits are more relaxed in sandbox

### Mock Responses
For integration testing, you can mock API responses:
```json
{
    "mock": true,
    "response": {
        "success": true,
        "data": {
            "contract": {
                "id": 1,
                "name": "Test Contract"
            }
        }
    }
}
```

---

## Support

For API support and questions:
- Email: api-support@yourdomain.com
- Documentation: https://docs.yourdomain.com
- Status Page: https://status.yourdomain.com
- Changelog: https://changelog.yourdomain.com