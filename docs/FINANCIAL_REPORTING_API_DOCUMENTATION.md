# Financial Reporting Infrastructure API Documentation

## Overview

The Financial Reporting Infrastructure provides comprehensive financial analytics and insights for business intelligence, built upon the existing star schema database design and ETL pipeline. This module offers 9 core financial reporting categories with real-time data processing, advanced analytics, and regulatory compliance features.

## Base URL

```
Production: https://your-domain.com/api/v1/financial
Development: http://localhost/api/v1/financial
```

## Authentication

All endpoints require authentication using Laravel Sanctum tokens.

```http
Authorization: Bearer {your_token}
Content-Type: application/json
```

## API Response Format

### Success Response
```json
{
  "success": true,
  "data": {
    // Response data
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "error": "Detailed error message",
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

### Validation Error Response
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

## 1. Revenue Recognition Endpoints

### 1.1 Revenue Recognition Analysis
```http
POST /revenue-recognition
```

**Description:** Get real-time revenue tracking and recognition with accrual-based calculations.

**Request Body:**
```json
{
  "date_range": {
    "start": "20240101",
    "end": "20240131"
  },
  "filters": {
    "client_key": "client_123",
    "route_key": "route_456",
    "service_type": "express"
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "revenue_analysis": {
      "total_revenue": 1500000.00,
      "recognized_revenue": 1450000.00,
      "deferred_revenue": 50000.00,
      "accrued_revenue": 25000.00
    },
    "revenue_breakdown": {
      "by_customer": [
        {
          "customer_id": "cust_001",
          "customer_name": "ABC Corp",
          "revenue": 450000.00,
          "percentage": 30.0
        }
      ],
      "by_service_type": {
        "express": 900000.00,
        "standard": 600000.00
      },
      "by_time_period": [
        {
          "period": "2024-01-01",
          "revenue": 50000.00
        }
      ]
    },
    "accrual_adjustments": {
      "total_adjustments": 25000.00,
      "period_end_adjustments": 15000.00,
      "cutoff_adjustments": 10000.00
    }
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

### 1.2 Revenue Forecasting
```http
POST /revenue-forecasting
```

**Description:** Generate revenue forecasts with predictive analytics and confidence intervals.

**Request Body:**
```json
{
  "period": "monthly",
  "forecast_periods": 12,
  "confidence_level": 95,
  "include_trends": true,
  "seasonal_adjustment": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "forecast_data": [
      {
        "period": "2024-02",
        "forecast_revenue": 1550000.00,
        "lower_bound": 1480000.00,
        "upper_bound": 1620000.00,
        "confidence": 95
      }
    ],
    "trend_analysis": {
      "growth_rate": 0.033,
      "seasonal_factor": 1.15,
      "trend_direction": "increasing"
    },
    "methodology": {
      "algorithm": "ARIMA",
      "training_period": "12 months",
      "validation_score": 0.87
    }
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

### 1.3 Deferred Revenue Tracking
```http
POST /deferred-revenue
```

**Description:** Track deferred revenue with recognition schedule and amortization analysis.

**Request Body:**
```json
{
  "date_range": {
    "start": "20240101",
    "end": "20240131"
  },
  "filters": {
    "service_type": "subscription"
  },
  "include_amortization": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "deferred_revenue_balance": 750000.00,
    "recognition_schedule": [
      {
        "month": "2024-02",
        "amount_to_recognize": 125000.00,
        "percentage": 16.67
      }
    ],
    "amortization_analysis": {
      "average_recognition_period": 6.5,
      "amortization_method": "straight_line",
      "remaining_periods": 5
    }
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

## 2. COGS Analysis Endpoints

### 2.1 COGS Breakdown Analysis
```http
POST /cogs-analysis
```

**Description:** Get detailed cost breakdown with variance analysis across all cost categories.

**Request Body:**
```json
{
  "date_range": {
    "start": "20240101",
    "end": "20240131"
  },
  "filters": {
    "cost_category": "fuel",
    "client_key": "client_123",
    "route_key": "route_456"
  },
  "include_variance": true,
  "include_trends": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "cost_breakdown": {
      "total_cogs": 900000.00,
      "fuel_cost": 360000.00,
      "labor_cost": 270000.00,
      "insurance_cost": 90000.00,
      "maintenance_cost": 135000.00,
      "depreciation": 45000.00
    },
    "cost_per_shipment": 15.50,
    "cost_per_mile": 0.85,
    "cost_trends": {
      "period_comparison": {
        "current": 900000.00,
        "previous": 850000.00,
        "variance": 50000.00,
        "variance_percentage": 5.88
      }
    },
    "variance_analysis": {
      "budget_variance": {
        "budgeted": 920000.00,
        "actual": 900000.00,
        "variance": 20000.00,
        "variance_percentage": -2.17
      }
    }
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

### 2.2 Cost Variance Analysis
```http
POST /cost-variance-analysis
```

**Description:** Perform variance analysis comparing actual vs budgeted costs by dimension.

**Request Body:**
```json
{
  "date_range": {
    "start": "20240101",
    "end": "20240131"
  },
  "filters": {
    "dimension": "route",
    "include_benchmarking": true
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "variance_summary": {
      "favorable_variances": 45000.00,
      "unfavorable_variances": 25000.00,
      "net_variance": 20000.00,
      "variance_percentage": 2.22
    },
    "variance_details": [
      {
        "route": "Route 1",
        "budgeted_cost": 150000.00,
        "actual_cost": 145000.00,
        "variance": -5000.00,
        "variance_percentage": -3.33,
        "type": "favorable"
      }
    ],
    "budget_comparison": {
      "total_budget": 920000.00,
      "total_actual": 900000.00,
      "total_variance": 20000.00
    }
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

## 3. Gross Margin Analysis Endpoints

### 3.1 Gross Margin Analysis
```http
POST /gross-margin-analysis
```

**Description:** Get real-time gross margin calculations with historical trending and forecasting.

**Request Body:**
```json
{
  "date_range": {
    "start": "20240101",
    "end": "20240131"
  },
  "filters": {
    "segment": "customer",
    "include_forecasting": true
  },
  "include_competitive_benchmarking": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "margin_analysis": {
      "gross_margin": 600000.00,
      "gross_margin_percentage": 40.0,
      "revenue": 1500000.00,
      "cogs": 900000.00
    },
    "historical_trends": [
      {
        "period": "2023-12",
        "margin_percentage": 38.5,
        "revenue": 1400000.00,
        "cogs": 861000.00
      }
    ],
    "forecasting": {
      "predicted_margin_6m": 42.5,
      "confidence_interval": [40.0, 45.0],
      "growth_trend": "improving"
    },
    "competitive_benchmarking": {
      "industry_average": 35.0,
      "our_performance": 40.0,
      "competitive_advantage": 5.0
    }
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

### 3.2 Margin Forecasting
```http
POST /margin-forecasting
```

**Description:** Generate margin forecasts using predictive analytics with multiple forecasting methods.

**Request Body:**
```json
{
  "forecast_periods": 12,
  "forecast_method": "seasonal",
  "confidence_level": 95,
  "include_scenarios": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "forecast_data": [
      {
        "period": "2024-02",
        "predicted_margin": 41.5,
        "lower_bound": 39.0,
        "upper_bound": 44.0,
        "confidence": 95
      }
    ],
    "methodology": {
      "primary_method": "seasonal_arima",
      "validation_rmse": 2.1,
      "r_squared": 0.89
    },
    "scenarios": {
      "optimistic": 45.0,
      "realistic": 41.5,
      "pessimistic": 38.0
    }
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

## 4. COD Collection Endpoints

### 4.1 COD Collection Tracking
```http
POST /cod-collection-tracking
```

**Description:** Track Cash-on-Delivery collection status with aging analysis and dunning management.

**Request Body:**
```json
{
  "filters": {
    "date_range": {
      "start": "20240101",
      "end": "20240131"
    }
  },
  "include_dunning": true,
  "include_writeoffs": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "cod_summary": {
      "total_cod_amount": 500000.00,
      "collected_amount": 450000.00,
      "pending_amount": 50000.00,
      "collection_rate": 90.0
    },
    "aging_analysis": {
      "current": 35000.00,
      "days_1_30": 10000.00,
      "days_31_60": 5000.00
    },
    "collection_metrics": {
      "average_collection_time": 5.2,
      "collection_cost": 2500.00,
      "success_rate": 92.0
    },
    "dunning_analysis": {
      "accounts_in_dunning": 45,
      "dunning_effectiveness": 78.0
    }
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

### 4.2 Aging Analysis
```http
POST /aging-analysis
```

**Description:** Perform comprehensive aging analysis with risk assessment and collection strategies.

**Request Body:**
```json
{
  "filters": {
    "date_range": {
      "start": "20240101",
      "end": "20240131"
    }
  },
  "aging_buckets": [
    {"min_days": 0, "max_days": 30},
    {"min_days": 31, "max_days": 60},
    {"min_days": 61, "max_days": 90},
    {"min_days": 91, "max_days": null}
  ],
  "include_risk_analysis": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "aging_summary": {
      "total_outstanding": 75000.00,
      "total_accounts": 125,
      "average_outstanding": 600.00
    },
    "aging_details": [
      {
        "bucket": "0-30 days",
        "amount": 45000.00,
        "count": 75,
        "percentage": 60.0
      }
    ],
    "risk_assessment": {
      "high_risk_amount": 15000.00,
      "medium_risk_amount": 20000.00,
      "low_risk_amount": 40000.00
    }
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

## 5. Payment Processing Endpoints

### 5.1 Payment Processing Management
```http
POST /payment-processing
```

**Description:** Manage payment processing workflow with reconciliation status tracking.

**Request Body:**
```json
{
  "filters": {
    "date_range": {
      "start": "20240101",
      "end": "20240131"
    }
  },
  "include_reconciliation": true,
  "include_exception_handling": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "payment_summary": {
      "total_payments": 2500,
      "total_amount": 1200000.00,
      "processing_fees": 18000.00,
      "net_amount": 1182000.00
    },
    "processing_workflow": {
      "pending": 125,
      "processing": 45,
      "completed": 2300,
      "failed": 30
    },
    "reconciliation": {
      "reconciled_amount": 1180000.00,
      "unreconciled_amount": 2000.00,
      "reconciliation_rate": 98.3
    },
    "payment_methods": {
      "credit_card": 800000.00,
      "bank_transfer": 300000.00,
      "digital_wallet": 100000.00
    }
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

## 6. Profitability Analysis Endpoints

### 6.1 Profitability Analysis
```http
POST /profitability-analysis
```

**Description:** Analyze profitability across multiple dimensions with optimization recommendations.

**Request Body:**
```json
{
  "filters": {
    "date_range": {
      "start": "20240101",
      "end": "20240131"
    },
    "include_optimization": true
  },
  "dimensions": ["customer", "route", "service_type"]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "profitability_summary": {
      "total_revenue": 1500000.00,
      "total_costs": 900000.00,
      "net_profit": 600000.00,
      "profit_margin": 40.0
    },
    "profitability_details": {
      "by_customer": [
        {
          "customer": "ABC Corp",
          "revenue": 450000.00,
          "costs": 270000.00,
          "profit": 180000.00,
          "margin": 40.0,
          "profitability_score": 8.5
        }
      ],
      "by_route": [
        {
          "route": "Route 1",
          "profit": 125000.00,
          "margin": 42.0,
          "efficiency_score": 9.2
        }
      ]
    },
    "optimization_recommendations": [
      {
        "type": "route_optimization",
        "description": "Consolidate low-margin routes",
        "potential_savings": 25000.00,
        "implementation_effort": "medium"
      }
    ]
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

## 7. Data Export Endpoints

### 7.1 Data Export
```http
POST /export
```

**Description:** Export financial data in multiple formats with customizable templates.

**Request Body:**
```json
{
  "export_type": "revenue",
  "format": "excel",
  "date_range": {
    "start": "20240101",
    "end": "20240131"
  },
  "filters": {},
  "template": "detailed",
  "include_charts": false,
  "email_recipient": "finance@company.com"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "filename": "financial_report_revenue_20240101_to_20240131_20241106_160000.xlsx",
    "file_path": "exports/financial_reports/financial_report_revenue_20240101_to_20240131_20241106_160000.xlsx",
    "download_url": "/api/v1/financial/download/financial_report_revenue_20240101_to_20240131_20241106_160000.xlsx",
    "format": "excel",
    "export_type": "revenue",
    "size": 2456789,
    "generated_at": "2025-11-06T16:00:00.000Z",
    "expires_at": "2025-11-13T16:00:00.000Z",
    "record_count": 15420
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

### 7.2 Get Export Templates
```http
GET /export-templates?export_type=revenue
```

**Description:** Retrieve available export templates for different report types.

**Response:**
```json
{
  "success": true,
  "data": {
    "revenue": {
      "standard": {
        "name": "Standard Revenue Report",
        "description": "Basic revenue recognition report with accruals",
        "columns": ["date", "revenue_recognized", "deferred_revenue", "revenue_forecast"]
      },
      "detailed": {
        "name": "Detailed Revenue Analysis",
        "description": "Comprehensive revenue analysis with forecasting",
        "columns": ["date", "revenue_recognized", "deferred_revenue", "revenue_forecast", "variance", "growth_rate"]
      }
    }
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

## 8. Integration Endpoints

### 8.1 Sync Accounting Data
```http
POST /sync-accounting-data
```

**Description:** Sync financial data with external accounting systems (QuickBooks, SAP, Oracle).

**Request Body:**
```json
{
  "system": "quickbooks",
  "sync_type": "revenue",
  "date_range": {
    "start": "20240101",
    "end": "20240131"
  },
  "dry_run": false
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "sync_id": "sync_66a7b8c9d0e1f",
    "system": "quickbooks",
    "sync_type": "revenue",
    "dry_run": false,
    "status": "completed",
    "start_time": "2025-11-06T16:00:00.000Z",
    "end_time": "2025-11-06T16:02:15.000Z",
    "duration_seconds": 135,
    "records_processed": 15420,
    "records_success": 15418,
    "records_failed": 2,
    "errors": [
      {
        "record_id": "txn_12345",
        "error": "Invalid customer reference",
        "timestamp": "2025-11-06T16:01:45.000Z"
      }
    ],
    "warnings": []
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

### 8.2 Integration Status
```http
GET /integration-status?system=quickbooks&include_logs=true
```

**Description:** Get integration status and logs for accounting system connections.

**Response:**
```json
{
  "success": true,
  "data": {
    "systems": {
      "quickbooks": {
        "last_sync": "2025-11-06T16:02:15.000Z",
        "last_status": "completed",
        "configuration_status": {
          "valid": true,
          "missing_fields": []
        },
        "connectivity_status": {
          "status": "connected",
          "last_check": "2025-11-06T16:05:00.000Z",
          "response_time": 245
        }
      }
    },
    "recent_syncs": [
      {
        "sync_id": "sync_66a7b8c9d0e1f",
        "system": "quickbooks",
        "sync_type": "revenue",
        "status": "completed",
        "start_time": "2025-11-06T16:00:00.000Z",
        "end_time": "2025-11-06T16:02:15.000Z",
        "records_processed": 15420,
        "records_success": 15418,
        "records_failed": 2,
        "errors": []
      }
    ]
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

## 9. Audit Trail Endpoints

### 9.1 Audit Trail
```http
GET /audit-trail?transaction_type=revenue&user_id=1&include_changes=true
```

**Description:** Get comprehensive audit trail for financial transactions with change tracking.

**Query Parameters:**
- `date_range[start]` (optional): Start date in YYYYMMDD format
- `date_range[end]` (optional): End date in YYYYMMDD format
- `transaction_type` (optional): Filter by transaction type
- `user_id` (optional): Filter by user ID
- `entity_type` (optional): Filter by entity type
- `include_changes` (optional): Include change tracking data

**Response:**
```json
{
  "success": true,
  "data": {
    "total_records": 5420,
    "audit_summary": {
      "total_activities": 5420,
      "unique_users": 15,
      "activity_types": {
        "revenue_recognition": 2500,
        "expense_recording": 1800,
        "adjustment": 1120
      },
      "time_range": {
        "earliest": "2024-01-01T00:00:00.000Z",
        "latest": "2024-01-31T23:59:59.000Z"
      }
    },
    "transaction_log": {
      "revenue_recognition": {
        "count": 2500,
        "latest_activity": "2024-01-31T23:45:00.000Z",
        "users_involved": 8,
        "records": []
      }
    },
    "change_tracking": [
      {
        "table": "fact_financial_transactions",
        "total_changes": 150,
        "significant_changes": 25,
        "users_responsible": 3,
        "latest_change": "2024-01-31T22:30:00.000Z"
      }
    ],
    "user_activity": [
      {
        "user_id": 1,
        "total_activities": 1200,
        "activity_types": {
          "revenue_recognition": 800,
          "adjustment": 400
        },
        "latest_activity": "2024-01-31T23:45:00.000Z",
        "ip_addresses": 2,
        "risk_score": 2
      }
    ],
    "compliance_status": {
      "sox_compliance": {
        "status": "COMPLIANT"
      },
      "gaap_compliance": {
        "status": "COMPLIANT"
      }
    },
    "data_integrity": {
      "overall_status": "PASS",
      "integrity_checks": {
        "data_consistency": {
          "status": "PASS",
          "issues_found": 0
        }
      }
    },
    "retention_info": {
      "retention_policies": {
        "sox": {
          "retention_years": 7,
          "cutoff_date": "2017-01-31T23:59:59.000Z",
          "records_affected": 0,
          "archival_status": "CURRENT"
        }
      }
    }
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

### 9.2 Compliance Report
```http
POST /compliance-report
```

**Description:** Generate compliance reports for SOX, GAAP, IFRS, and internal controls.

**Request Body:**
```json
{
  "compliance_type": "sox",
  "date_range": {
    "start": "20240101",
    "end": "20240131"
  },
  "report_format": "detailed"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "compliance_type": "sox",
    "report_format": "detailed",
    "reporting_period": {
      "start_date": "20240101",
      "end_date": "20240131"
    },
    "generation_date": "2025-11-06T16:00:00.000Z",
    "compliance_requirements": {
      "internal_controls": {
        "status": "EFFECTIVE",
        "testing_results": "PASSED"
      },
      "financial_reporting": {
        "status": "COMPLIANT",
        "accuracy_rate": 99.8
      }
    },
    "findings": [],
    "recommendations": [
      {
        "area": "Control Documentation",
        "priority": "Medium",
        "description": "Enhance documentation for automated controls"
      }
    ],
    "certification": {
      "certification_type": "sox",
      "certified_by": "System Administrator",
      "certification_date": "2025-11-06T16:00:00.000Z",
      "compliance_status": "COMPLIANT",
      "certification_statement": "All SOX compliance requirements have been met."
    }
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

## 10. Utility Endpoints

### 10.1 Financial Dashboard
```http
POST /dashboard
```

**Description:** Get comprehensive financial dashboard data aggregating all financial metrics.

**Request Body:**
```json
{
  "date_range": {
    "start": "20240101",
    "end": "20240131"
  },
  "include_forecasting": true,
  "dashboard_type": "executive"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "dashboard_type": "executive",
    "summary": {
      "revenue": {
        "total_revenue": 1500000.00,
        "revenue_growth": 8.5,
        "deferred_revenue": 50000.00
      },
      "cogs": {
        "total_cogs": 900000.00,
        "cost_reduction": 2.1,
        "cost_per_shipment": 15.50
      },
      "margins": {
        "gross_margin": 40.0,
        "operating_margin": 35.0,
        "net_profit_margin": 30.0
      },
      "cod": {
        "collection_rate": 90.0,
        "outstanding_amount": 50000.00
      },
      "payments": {
        "success_rate": 98.5,
        "processing_time": 2.3
      },
      "profitability": {
        "overall_score": 8.7,
        "improvement_areas": ["Route Optimization", "Cost Reduction"]
      }
    },
    "generated_at": "2025-11-06T16:00:00.000Z"
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

### 10.2 Financial Metrics
```http
POST /financial-metrics
```

**Description:** Get key financial metrics and KPIs with comparative analysis.

**Request Body:**
```json
{
  "date_range": {
    "start": "20240101",
    "end": "20240131"
  },
  "metrics": [
    "revenue_growth",
    "profit_margin",
    "cost_efficiency",
    "cash_flow"
  ],
  "comparison_period": "previous_period"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "revenue_metrics": {
      "total_revenue": 1500000.00,
      "revenue_growth": 8.5,
      "revenue_per_customer": 15000.00,
      "deferred_revenue": 50000.00
    },
    "cost_metrics": {
      "total_cogs": 900000.00,
      "cost_reduction": 2.1,
      "cost_per_shipment": 15.50,
      "cost_trends": "improving"
    },
    "profitability_metrics": {
      "gross_margin": 40.0,
      "operating_margin": 35.0,
      "net_profit_margin": 30.0,
      "profit_growth": 12.3
    },
    "cash_flow_metrics": {
      "cod_collection_rate": 90.0,
      "days_sales_outstanding": 25,
      "cash_conversion_cycle": 30,
      "collection_efficiency": 92.0
    }
  },
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad Request - Invalid request format or parameters |
| 401 | Unauthorized - Authentication required |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource not found |
| 422 | Unprocessable Entity - Validation failed |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error - Server processing error |
| 503 | Service Unavailable - Service temporarily unavailable |

## Rate Limiting

- **Standard Endpoints**: 1000 requests per hour per user
- **Export Endpoints**: 50 requests per hour per user
- **Sync Endpoints**: 10 requests per hour per user
- **Real-time Endpoints**: 500 requests per hour per user

## Data Formats

### Date Range Format
```json
{
  "start": "YYYYMMDD",
  "end": "YYYYMMDD"
}
```

### Filters Format
```json
{
  "client_key": "string",
  "route_key": "string", 
  "service_type": "string",
  "dimension": "string"
}
```

### Response Timestamps
All responses include a timestamp in ISO 8601 UTC format:
```json
{
  "timestamp": "2025-11-06T16:00:00.000Z"
}
```

## SDK and Client Libraries

Client libraries are available for:
- JavaScript/TypeScript: `@company/financial-reporting-js`
- Python: `financial-reporting-python`
- Java: `com.company:financial-reporting-java`
- PHP: `company/financial-reporting-php`

## Support and Contact

- **Technical Support**: support@company.com
- **API Documentation**: https://docs.company.com/financial-reporting
- **Status Page**: https://status.company.com
- **Developer Portal**: https://developers.company.com

---

*Last updated: November 6, 2025*
*Version: 1.0.0*