# Analytics Platform API Documentation

## Overview

This documentation provides comprehensive information about the Analytics Platform API, which serves as the backend for multi-dimensional reporting and analytics capabilities in the logistics system.

## Base URL

```
Production: https://analytics.yourcompany.com/api
Staging: https://staging-analytics.yourcompany.com/api
Development: http://localhost:8000/api
```

## Authentication

All API requests require authentication using Bearer tokens:

```http
Authorization: Bearer {your_api_token}
```

## API Endpoints

### Analytics Controller

#### Dashboard Metrics

**GET** `/api/analytics/dashboard/{branchId}`

Retrieve comprehensive dashboard metrics for a specific branch.

**Parameters:**
- `branchId` (integer, required): Branch identifier
- `dateRange` (string, optional): Date range in format "YYYY-MM-DD,YYYY-MM-DD"

**Response Example:**
```json
{
  "success": true,
  "data": [
    {
      "total_shipments": 1250,
      "delivered_shipments": 1180,
      "exception_shipments": 25,
      "avg_delivery_time": 145.5,
      "total_revenue": 250000.00,
      "total_cost": 180000.00,
      "total_margin": 70000.00,
      "avg_margin_percentage": 28.0
    }
  ],
  "cached": true,
  "message": "Dashboard metrics retrieved successfully"
}
```

#### Operational Reports

**GET** `/api/analytics/operational/{branchId}`

Generate operational reports with various aggregation levels.

**Parameters:**
- `branchId` (integer, required): Branch identifier
- `reportType` (string, required): Type of report
  - `daily_summary`: Daily operational summary
  - `weekly_summary`: Weekly operational summary
  - `monthly_summary`: Monthly operational summary
  - `performance_metrics`: Performance indicators
- `dateRange` (string, optional): Date range filter

#### Financial Reports

**GET** `/api/analytics/financial/{clientId}`

Retrieve financial analytics for client accounts.

**Parameters:**
- `clientId` (integer, required): Client identifier
- `reportType` (string, optional): Type of financial report
- `dateRange` (string, optional): Date range filter

#### Performance Metrics

**GET** `/api/analytics/performance/{branchId}`

Get performance metrics including delivery rates, time analysis, and operational KPIs.

**Parameters:**
- `branchId` (integer, required): Branch identifier
- `dateRange` (string, optional): Date range filter

### Cache Management

#### Preload Cache

**POST** `/api/analytics/cache/preload`

Preload frequently accessed data into cache for improved performance.

**Request Body:**
```json
{
  "branchIds": [1, 2, 3],
  "dateRange": ["2023-01-01", "2023-12-31"],
  "clientIds": [101, 102, 103]
}
```

#### Clear Cache

**DELETE** `/api/analytics/cache/clear`

Clear cache by specific patterns or tags.

**Request Body:**
```json
{
  "patterns": ["dashboard:*", "operational:*"],
  "tags": ["branch:123", "client:456"]
}
```

#### Cache Statistics

**GET** `/api/analytics/cache/stats`

Retrieve cache performance statistics and health information.

## Error Handling

All API endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "error": "Detailed error message",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### HTTP Status Codes

- `200` - Success
- `400` - Bad Request (validation errors)
- `401` - Unauthorized (invalid or missing token)
- `404` - Not Found (resource doesn't exist)
- `422` - Unprocessable Entity (validation failed)
- `500` - Internal Server Error

## Rate Limiting

API requests are rate-limited to ensure system stability:

- **1000 requests per hour** per API token
- **100 requests per minute** burst limit
- Rate limit headers are included in responses:
  - `X-RateLimit-Limit`: Request limit
  - `X-RateLimit-Remaining`: Requests remaining
  - `X-RateLimit-Reset`: Reset timestamp

## Data Formats

### Date Format
All dates should be in ISO 8601 format: `YYYY-MM-DD`

### Currency
All monetary values are in cents and displayed as decimals with 2 decimal places.

### Coordinates
Geographic coordinates use decimal degrees format (WGS84).

## SDK Examples

### JavaScript/Node.js

```javascript
const axios = require('axios');

const api = axios.create({
  baseURL: 'https://analytics.yourcompany.com/api',
  headers: {
    'Authorization': 'Bearer YOUR_API_TOKEN'
  }
});

// Get dashboard metrics
const getDashboardMetrics = async (branchId, dateRange) => {
  try {
    const response = await api.get(`/analytics/dashboard/${branchId}`, {
      params: { dateRange }
    });
    return response.data;
  } catch (error) {
    console.error('Error:', error.response.data);
  }
};
```

### Python

```python
import requests

class AnalyticsAPI:
    def __init__(self, base_url, token):
        self.base_url = base_url
        self.headers = {'Authorization': f'Bearer {token}'}
    
    def get_dashboard_metrics(self, branch_id, date_range=None):
        params = {}
        if date_range:
            params['dateRange'] = date_range
        
        response = requests.get(
            f"{self.base_url}/analytics/dashboard/{branch_id}",
            headers=self.headers,
            params=params
        )
        return response.json()
```

### PHP

```php
class AnalyticsAPI {
    private $baseUrl;
    private $token;
    
    public function __construct($baseUrl, $token) {
        $this->baseUrl = $baseUrl;
        $this->token = $token;
    }
    
    public function getDashboardMetrics($branchId, $dateRange = null) {
        $url = $this->baseUrl . "/analytics/dashboard/" . $branchId;
        $params = [];
        
        if ($dateRange) {
            $params['dateRange'] = $dateRange;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
```

## Testing

Use the following test endpoints to verify API functionality:

- **Health Check**: `GET /api/health`
- **Authentication Test**: `GET /api/auth/test`
- **Cache Test**: `GET /api/cache/test`

## Support

For API support and questions:
- Email: api-support@yourcompany.com
- Documentation: https://docs.analytics.yourcompany.com
- Status Page: https://status.analytics.yourcompany.com