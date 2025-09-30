# API v1 MVP Implementation Plan

## Overview
Minimal viable REST API for DHL-style logistics app focusing on Auth + Shipment CRUD only.

## Architecture Decisions

### ✅ What's Already Built
- Laravel 12 + Sanctum configured
- Middleware: `IdempotencyMiddleware`, `BindDeviceMiddleware`, `RoleMiddleware`
- Models: `User`, `Shipment`, `Device`
- Migrations: `devices`, `shipments.public_token`
- Spatie ActivityLog for audit trails
- L5-Swagger configured
- Feature flag support in `RouteServiceProvider`
- Some controllers: `Admin\MetricsController`, `Admin\ShipmentController`

### 🎯 Phase 1: Minimal MVP (This Implementation)
**Endpoints:**
- Public: `GET /api/v1/tracking/{token}` - Read-only shipment tracking
- Auth: `POST /api/v1/login`, `POST /api/v1/logout`, `GET /api/v1/me`
- Client Shipments: `GET|POST /api/v1/shipments`, `GET /api/v1/shipments/{id}`, `POST /api/v1/shipments/{id}/cancel`
- Admin Customers: `GET /api/v1/admin/customers`, `GET /api/v1/admin/customers/{id}`, `PATCH /api/v1/admin/customers/{id}`
- Admin Shipments: `GET /api/v1/admin/shipments`, `PATCH /api/v1/admin/shipments/{id}/status`
- Admin Metrics: `GET /api/v1/admin/metrics`

**Guards:**
- `api` (Sanctum) for client/mobile
- `admin` (session) for dashboard users

**Feature Flag:** `FEATURE_MOBILE_API=true`

### 📋 Implementation Tasks (20 Steps)

1. Add `FEATURE_MOBILE_API=true` to .env + document env vars
2. Create `AuthController` (login, logout, me)
3. Create `Client\ShipmentController` (index, show, store, cancel)
4. Create `TrackingController` (public tracking)
5. Create `Admin\CustomerController` (index, show, update)
6. Verify existing `Admin\ShipmentController` and `Admin\MetricsController`
7. Create FormRequests: `LoginRequest`, `StoreShipmentRequest`, `CancelShipmentRequest`, `UpdateCustomerRequest`, `UpdateShipmentStatusRequest`, `UpdateProfileRequest`
8. Create Resources: `UserResource`, `ShipmentResource`, `CustomerResource`
9. Create `ShipmentPolicy` (view, create, cancel)
10. Complete `routes/api_v1.php` with all endpoints
11. Add OpenAPI annotations to all controllers
12. Write Pest tests: Auth flow
13. Write Pest tests: Shipment CRUD
14. Write Pest tests: Admin endpoints
15. Write Pest test: Idempotency
16. Generate OpenAPI docs + verify Swagger UI
17. Create README section
18. Run Laravel Pint
19. Verify all tests pass
20. Create git branch + commit

### 🚫 Phase 2 (Deferred)
- WebSockets / Broadcasting
- POD (Proof of Delivery)
- Tasks management
- Webhooks
- Quotation system
- Pickup requests
- Dispatch/optimization
- Driver location tracking

## API Structure

```
/api/v1/
├── tracking/{token}              [PUBLIC]
├── login                         [PUBLIC]
├── logout                        [AUTH:api]
├── me                            [AUTH:api]
├── shipments                     [AUTH:api + idempotency]
│   ├── GET    /                  List my shipments
│   ├── POST   /                  Create shipment
│   ├── GET    /{id}              View shipment
│   └── POST   /{id}/cancel       Cancel shipment
└── admin/                        [AUTH:admin + role:admin]
    ├── customers
    │   ├── GET    /              List customers
    │   ├── GET    /{id}          View customer
    │   └── PATCH  /{id}          Update customer
    ├── shipments
    │   ├── GET    /              List all shipments
    │   └── PATCH  /{id}/status   Update status
    └── metrics
        └── GET    /              Dashboard metrics
```

## Security

- **Idempotency:** Required on all POST/PATCH/DELETE via `Idempotency-Key` header
- **Device Binding:** Required on mobile login via `device_uuid` header
- **Activity Log:** Auto-tracked via Spatie on all state changes
- **Authorization:** `ShipmentPolicy` enforces ownership/admin rules
- **Feature Flag:** Can disable API instantly if needed

## Testing Strategy

- Auth: Device binding, token creation, logout
- Shipments: Full CRUD flow with ownership checks
- Admin: Customer management, shipment status updates, metrics
- Idempotency: Duplicate requests return cached responses
- All tests use `ApiV1DemoSeeder` for test data

## Success Criteria

✅ Mobile client can login with device binding
✅ Client can create, list, view, cancel own shipments
✅ Admin can manage customers and shipments
✅ Public tracking works via public_token
✅ Idempotency prevents duplicate operations
✅ OpenAPI docs accessible at /api/docs
✅ All tests pass
✅ Non-breaking: existing routes unchanged