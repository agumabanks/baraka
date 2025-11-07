# 🚀 Quick Start Guide - Unified Logistics Pricing API

## Overview
This guide will help you get started with the Enhanced Logistics Pricing API quickly and efficiently.

## 📋 **Prerequisites**
- API Base URL: `https://api.logistics.com/v1` (or your local URL)
- API Authentication Key (Bearer token)
- Postman or similar API testing tool (optional but recommended)

## 🔐 **Authentication**
All API requests require authentication using Bearer token:

```http
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json
```

## ⚡ **Quick Start Examples**

### 1. **Health Check**
```bash
curl -X GET "https://api.logistics.com/v1/health" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

### 2. **Generate Instant Quote**
```bash
curl -X POST "https://api.logistics.com/v1/pricing/quote" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "origin": "US",
    "destination": "CA",
    "service_level": "standard",
    "shipment_data": {
      "weight_kg": 2.5,
      "pieces": 1
    },
    "currency": "USD"
  }'
```

### 3. **Create Contract**
```bash
curl -X POST "https://api.logistics.com/v1/contracts" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": 12345,
    "name": "Enterprise Contract 2024",
    "contract_type": "enterprise",
    "start_date": "2024-02-01",
    "end_date": "2025-01-31",
    "volume_commitment": 10000
  }'
```

### 4. **Validate Promotion**
```bash
curl -X GET "https://api.logistics.com/v1/promotions/validate?code=WELCOME10&customer_id=12345" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

### 5. **Get Analytics**
```bash
curl -X GET "https://api.logistics.com/v1/analytics/customer-insights?customer_id=12345&timeframe=30d" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

## 📊 **Testing with Postman**

1. **Import Collection**: Download `docs/Postman_Collection.json`
2. **Set Environment Variables**:
   ```
   baseUrl: https://api.logistics.com/v1
   apiKey: YOUR_API_KEY
   customerId: 12345
   contractId: 67890
   ```
3. **Run Health Check** to verify connectivity
4. **Test Basic Quote** to ensure authentication works
5. **Run Full Test Suite** from the Postman collection

## 🛡️ **Rate Limits**
- **Quotes**: 100 requests/minute (base tier)
- **Bulk Operations**: 10 requests/hour
- **Contracts**: 50 requests/minute
- **Promotions**: 60 requests/minute

**Rate Limit Headers**:
- `X-RateLimit-Limit-Minute`: Request limit per minute
- `X-RateLimit-Remaining-Minute`: Remaining requests
- `X-RateLimit-Reset-Minute`: Reset timestamp

## 📁 **File Structure**
```
├── app/Http/Controllers/Api/V1/
│   ├── UnifiedPricingApiController.php    # Main controller
│   ├── SystemHealthController.php         # Health monitoring
│   ├── WebhookController.php              # Webhook management
│   ├── IntegrationController.php          # Third-party integration
│   └── AnalyticsController.php            # Analytics & reporting
├── app/Http/Middleware/
│   ├── AdvancedRateLimitMiddleware.php    # Tier-based rate limiting
│   └── APISecurityValidationMiddleware.php # Security validation
├── app/Services/
│   ├── APIMonitoringService.php           # Performance monitoring
│   ├── DynamicPricingService.php          # Core pricing service
│   ├── ContractManagementService.php      # Contract management
│   └── PromotionEngineService.php         # Promotion handling
├── routes/api.php                         # API routes configuration
├── docs/
│   ├── api-documentation.yaml             # OpenAPI documentation
│   ├── Postman_Collection.json           # Postman collection
│   └── IMPLEMENTATION_SUMMARY.md          # Detailed documentation
```

## 🔄 **Error Handling**
```javascript
// Common error response structure
{
  "success": false,
  "error": "Error Type",
  "message": "Detailed description",
  "timestamp": "2024-01-01T00:00:00Z",
  "retry_after": 60  // For rate limit errors
}
```

## 📈 **Monitoring**
- **Real-time Metrics**: Check `/analytics/performance`
- **System Health**: Use `/health` endpoint
- **Customer Insights**: Access via `/analytics/customer-insights`

## 🚦 **Best Practices**

### 1. **Use Bulk Operations**
```bash
# Instead of multiple individual requests
curl -X POST "https://api.logistics.com/v1/pricing/quote/bulk" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "shipment_requests": [
      {"origin": "US", "destination": "CA", ...},
      {"origin": "US", "destination": "UK", ...}
    ]
  }'
```

### 2. **Handle Rate Limits**
```javascript
if (response.status === 429) {
  const retryAfter = response.headers.get('Retry-After');
  await new Promise(resolve => setTimeout(resolve, retryAfter * 1000));
  // Retry the request
}
```

### 3. **Cache Quote Results**
- Quote results are cached for 15 minutes
- Use consistent parameters to benefit from caching
- Consider implementing client-side caching

### 4. **Monitor Performance**
- Check response times with `X-Response-Time` header
- Use `/analytics/performance` for detailed metrics
- Set up alerts for high error rates

## 🔗 **Webhooks**
Register webhooks for real-time updates:

```bash
curl -X POST "https://api.logistics.com/v1/webhooks/register" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "url": "https://yourapp.com/webhook",
    "events": ["quote.calculated", "contract.updated"]
  }'
```

## 📚 **Additional Resources**
- **Full Documentation**: `docs/IMPLEMENTATION_SUMMARY.md`
- **API Reference**: OpenAPI docs in `docs/api-documentation.yaml`
- **Testing Collection**: `docs/Postman_Collection.json`
- **Example Code**: Curl examples above

## ⚠️ **Common Issues**
1. **Authentication**: Ensure Bearer token is correct
2. **Rate Limits**: Check rate limit headers and implement retry logic
3. **Validation**: Verify all required fields are provided
4. **CORS**: Configure CORS headers for browser-based applications

## 🆘 **Support**
- **Documentation**: Complete implementation guide available
- **API Testing**: Use Postman collection for comprehensive testing
- **Monitoring**: Built-in performance and error tracking
- **Error Handling**: Detailed error responses with troubleshooting info

## 🎯 **Next Steps**
1. Set up authentication and test basic connectivity
2. Implement quote generation in your application
3. Configure contract management workflows
4. Set up promotion validation
5. Integrate webhook notifications
6. Configure monitoring and analytics
7. Optimize performance with caching strategies

---

**Ready to integrate?** Start with the health check endpoint and work through the basic quote generation example!