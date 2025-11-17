# Operational Reporting Module - API Documentation

## Overview

The Operational Reporting Module provides comprehensive real-time analytics and insights for logistics operations. It builds upon the existing star schema database design and provides 7 core service areas with full API coverage.

## Base URL

```
https://your-domain.com/api/v1/reports/operational
```

## Authentication

All endpoints require API authentication via Laravel Sanctum tokens.

```bash
Authorization: Bearer {your_token}
```

## Service Modules

### 1. Origin-Destination Volume Analytics

#### Get Volume Analytics
```http
GET /volumes
```

**Query Parameters:**
- `date_range.start` (required): YYYYMMDD format
- `date_range.end` (required): YYYYMMDD format
- `granularity` (optional): `hourly`, `daily`, `weekly`, `monthly` (default: `daily`)
- `filters.client_key` (optional): Filter by client
- `filters.carrier_key` (optional): Filter by carrier
- `filters.branch_key` (optional): Filter by branch

**Response:**
```json
{
  "success": true,
  "data": {
    "volume_summary": {
      "total_shipments": 1250,
      "total_volume": 1250,
      "growth_rate": 12.5
    },
    "volume_by_date": [...],
    "route_analysis": [...],
    "trends": [...]
  },
  "timestamp": "2025-11-06T14:54:56Z"
}
```

#### Generate Geographic Heat Map
```http
GET /volumes/heatmap
```

**Query Parameters:**
- `date_range.start` (required): YYYYMMDD format
- `date_range.end` (required): YYYYMMDD format
- `map_type` (optional): `route`, `branch`, `client` (default: `route`)
- `filters` (optional): Additional filters object

### 2. Route Efficiency Analysis

#### Get Route Efficiency Score
```http
GET /route-efficiency/{routeKey}
```

**Path Parameters:**
- `routeKey` (required): The route key

**Query Parameters:**
- `date_range.start` (required): YYYYMMDD format
- `date_range.end` (required): YYYYMMDD format

**Response:**
```json
{
  "success": true,
  "data": {
    "route_key": "R001",
    "efficiency_score": 85.2,
    "performance_metrics": {
      "on_time_rate": 92.5,
      "avg_transit_time": 24.5,
      "cost_per_mile": 2.45
    },
    "bottlenecks": [...],
    "recommendations": [...]
  }
}
```

#### Identify Route Bottlenecks
```http
GET /route-efficiency/bottlenecks
```

**Query Parameters:**
- `filters.date_range.start` (optional): YYYYMMDD format
- `filters.date_range.end` (optional): YYYYMMDD format
- `filters.client_key` (optional): Filter by client
- `filters.carrier_key` (optional): Filter by carrier

### 3. On-Time Delivery Analysis

#### Get On-Time Delivery Rate
```http
GET /on-time-delivery
```

**Query Parameters:**
- `filters.date_range.start` (optional): YYYYMMDD format
- `filters.date_range.end` (optional): YYYYMMDD format
- `filters.client_key` (optional): Filter by client
- `filters.route_key` (optional): Filter by route
- `filters.driver_key` (optional): Filter by driver

**Response:**
```json
{
  "success": true,
  "data": {
    "overall_metrics": {
      "on_time_rate": 94.2,
      "total_shipments": 1580,
      "delayed_shipments": 92
    },
    "detailed_breakdown": [...],
    "trends": [...]
  }
}
```

#### Perform Variance Analysis
```http
GET /on-time-delivery/variance
```

**Query Parameters:**
- `date_range.start` (required): YYYYMMDD format
- `date_range.end` (required): YYYYMMDD format
- `dimension` (required): `route`, `driver`, `client`, `branch`, `carrier`
- `filters` (optional): Additional filters

#### Get Historical Trends
```http
GET /on-time-delivery/trends
```

**Query Parameters:**
- `period` (optional): `hourly`, `daily`, `weekly`, `monthly` (default: `daily`)
- `days` (optional): Number of days (1-365, default: 30)

#### Monitor SLA Compliance
```http
GET /on-time-delivery/sla/{clientKey}
```

**Path Parameters:**
- `clientKey` (required): The client key

### 4. Exception Analysis

#### Get Exception Analysis
```http
GET /exceptions
```

**Query Parameters:**
- `filters.date_range.start` (optional): YYYYMMDD format
- `filters.date_range.end` (optional): YYYYMMDD format
- `filters.client_key` (optional): Filter by client
- `filters.route_key` (optional): Filter by route
- `filters.driver_key` (optional): Filter by driver

**Response:**
```json
{
  "success": true,
  "data": {
    "categorized_exceptions": {
      "damaged": 45,
      "delayed": 67,
      "lost": 12,
      "returned": 23
    },
    "root_cause_analysis": [...],
    "preventive_actions": [...],
    "financial_impact": {
      "total_cost": 15600,
      "cost_per_exception": 95.5
    }
  }
}
```

#### Get Root Cause Analysis
```http
GET /exceptions/root-cause/{exceptionType}
```

**Path Parameters:**
- `exceptionType` (required): `damaged`, `delayed`, `lost`, `returned`

### 5. Driver Performance

#### Get Driver Performance Metrics
```http
GET /driver-performance/{driverKey}
```

**Path Parameters:**
- `driverKey` (required): The driver key

**Query Parameters:**
- `date_range.start` (required): YYYYMMDD format
- `date_range.end` (required): YYYYMMDD format
- `metric_type` (optional): `stops_per_hour`, `miles_per_gallon`, `hos_compliance`, `safety_incidents`

**Response:**
```json
{
  "success": true,
  "data": {
    "driver_details": {
      "driver_key": "D001",
      "driver_name": "John Smith",
      "safety_rating": 4.8
    },
    "performance_metrics": {
      "stops_per_hour": 3.2,
      "on_time_rate": 96.5,
      "total_miles": 4500,
      "safety_incidents": 0
    },
    "ranking": {
      "fleet_rank": 5,
      "percentile": 85.0
    }
  }
}
```

#### Get Driver Ranking
```http
GET /driver-performance/ranking
```

**Query Parameters:**
- `period` (optional): `weekly`, `monthly`, `quarterly` (default: `monthly`)
- `months` (optional): Number of months (1-12, default: 3)

### 6. Container Utilization

#### Get Container Utilization
```http
GET /container-utilization/{containerId}
```

**Path Parameters:**
- `containerId` (required): The container ID

**Query Parameters:**
- `date_range.start` (required): YYYYMMDD format
- `date_range.end` (required): YYYYMMDD format
- `analysis_type` (optional): `utilization`, `cost_efficiency`, `load_optimization`

**Response:**
```json
{
  "success": true,
  "data": {
    "container_details": {
      "container_id": "CNT001",
      "capacity_cubic_feet": 1728
    },
    "utilization_analysis": {
      "utilization_rate": 78.5,
      "avg_daily_utilization": 75.2,
      "peak_utilization": 95.0
    },
    "optimization_suggestions": [...],
    "cost_efficiency": {
      "cost_per_utilization": 12.45,
      "roi_score": 8.2
    }
  }
}
```

#### Get Optimization Suggestions
```http
GET /optimization-suggestions
```

**Query Parameters:**
- `route_id` (optional): Specific route ID
- `analysis_type` (optional): `route_optimization`, `capacity_planning`, `load_balancing`

### 7. Transit Time Analysis

#### Get Transit Time Analysis
```http
GET /transit-times
```

**Query Parameters:**
- `entity_key` (required): The entity key
- `type` (optional): `route`, `carrier`, `origin`, `destination`, `driver` (default: `route`)
- `date_range.start` (optional): YYYYMMDD format
- `date_range.end` (optional): YYYYMMDD format
- `analysis_type` (optional): `average_time`, `bottlenecks`, `variance_analysis`, `benchmarking`

**Response:**
```json
{
  "success": true,
  "data": {
    "transit_analysis": {
      "statistics": {
        "average": 24.5,
        "median": 23.0,
        "standard_deviation": 8.2,
        "min": 12.0,
        "max": 48.0
      },
      "performance_metrics": {
        "efficiency_score": 85.2,
        "on_time_rate": 94.5,
        "reliability_score": 89.3
      }
    },
    "bottlenecks": [...],
    "benchmarking": [...],
    "improvement_opportunities": [...]
  }
}
```

#### Get Performance Benchmarking
```http
GET /transit-times/benchmarking
```

**Query Parameters:**
- `entity_keys` (required): Array of entity keys
- `type` (optional): `route`, `carrier`, `origin`, `destination`, `driver` (default: `route`)
- `date_range.start` (optional): YYYYMMDD format
- `date_range.end` (optional): YYYYMMDD format

#### Get Improvement Opportunities
```http
GET /transit-times/improvements
```

**Query Parameters:**
- `filters.date_range.start` (optional): YYYYMMDD format
- `filters.date_range.end` (optional): YYYYMMDD format
- `filters.client_key` (optional): Filter by client
- `filters.carrier_key` (optional): Filter by carrier

## Data Export

#### Export Operational Data
```http
POST /export
```

**Request Body:**
```json
{
  "report_type": "volume_analytics",
  "format": "excel",
  "filters": {
    "client_key": "CLI001"
  },
  "date_range": {
    "start": "20251101",
    "end": "20251106"
  }
}
```

**Supported report types:**
- `volume_analytics`
- `route_efficiency`
- `on_time_delivery`
- `exceptions`
- `driver_performance`
- `container_utilization`
- `transit_times`

**Supported formats:**
- `excel`
- `csv`
- `pdf`

## Drill-Down Functionality

#### Get Drill-Down Data
```http
GET /drilldown
```

**Query Parameters:**
- `entity_type` (required): `shipment`, `route`, `driver`, `client`, `branch`, `carrier`
- `entity_id` (required): The entity ID
- `level` (optional): `aggregate`, `detail`, `summary` (default: `detail`)
- `filters` (optional): Additional filters

**Response:**
```json
{
  "success": true,
  "data": {
    "shipment_details": {...},
    "financial_details": {...},
    "operational_details": {...},
    "exception_details": {...},
    "related_shipments": [...],
    "route_performance": {...},
    "driver_performance": {...}
  }
}
```

## Dashboard Summary

#### Get Dashboard Summary
```http
GET /dashboard/summary
```

**Query Parameters:**
- `date_range.start` (optional): YYYYMMDD format (default: 7 days ago)
- `date_range.end` (optional): YYYYMMDD format (default: today)
- `client_key` (optional): Filter by client
- `branch_key` (optional): Filter by branch

**Response:**
```json
{
  "success": true,
  "data": {
    "volume_analytics": {...},
    "route_efficiency": {...},
    "on_time_delivery": {...},
    "exceptions": {...},
    "driver_performance": {...},
    "container_utilization": {...},
    "transit_times": {...}
  }
}
```

## Error Handling

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "error": "Detailed error message"
}
```

**Common HTTP Status Codes:**
- `200` - Success
- `400` - Bad Request (validation errors)
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Failed
- `500` - Internal Server Error

## Rate Limiting

- Standard rate limit: 100 requests per minute per user
- Export requests: 10 requests per hour per user
- Dashboard summary: 60 requests per hour per user

## Caching

Most endpoints use Redis caching with TTL:
- Volume analytics: 5 minutes
- Route efficiency: 3 minutes
- Drill-down data: 3 minutes
- Performance benchmarking: 5 minutes

Cache keys are automatically invalidated when underlying data changes.

## Performance Monitoring

The module includes built-in performance monitoring accessible via:

```http
GET /performance/monitoring
GET /performance/alerts
GET /performance/optimization
```

## Data Schema

### Date Format
All dates use YYYYMMDD format (e.g., "20251106" for November 6, 2025)

### Pagination
List endpoints support pagination:
- `page` (default: 1)
- `per_page` (default: 20, max: 100)

### Filtering
Most endpoints support filtering through the `filters` parameter:
```json
{
  "filters": {
    "client_key": "CLI001",
    "route_key": "R001",
    "date_range": {
      "start": "20251101",
      "end": "20251106"
    }
  }
}
```

## Webhook Integration

For real-time updates, configure webhooks:

```json
{
  "webhook_url": "https://your-app.com/webhooks/operational-reporting",
  "events": [
    "shipment.delivered",
    "route.bottleneck_detected",
    "exception.occurred"
  ]
}
```

## Support

For technical support or questions about the API:
- Documentation: [Internal Wiki]
- Support Email: [your-email@company.com]
- Slack Channel: #operational-reporting-support