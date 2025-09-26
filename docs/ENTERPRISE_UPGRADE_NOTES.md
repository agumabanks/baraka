# Enterprise Upgrade Notes

This document summarizes the enhancements implemented to harden and extend the client/customer module for enterprise readiness.

## Highlights

- Security headers middleware (`App/Http/Middleware/SecurityHeaders.php`) applied globally to add HSTS, CSP, X-Frame-Options, and more.
- Customer Portal routes protected behind `auth` + `verified` for actions that create or access personal data. Public informational pages remain open.
- Admin impersonation flow: start/stop impersonation with audit logging and an on-screen banner.
- Shipment status notification stack wired via Events/Listeners/Notifications; users can control preferences.
- Branch hierarchy support surfaced in `Hub` model (parent/children relations) aligning with multi-branch migrations.
- Admin Shipment index now supports filtering (query, status, branch, dates) and uses tracking_number accessor.
- GDPR/Privacy export endpoint for customers to download their data.

## Changes

1. Security
   - New middleware `SecurityHeaders` added to global middleware stack.
   - Recommendation: set `APP_ENV=production` and `APP_DEBUG=false` in production. Do not commit `.env` with secrets.

2. Portal Hardening
   - Portal create/store/history routes now require `auth` + `verified`.
   - Added rate-limiting to shipment creation POST.
   - New `PrivacyController@export` for user data export.

3. Impersonation (Admin Support Tool)
   - Routes: `POST /admin/impersonate/{user}` and `POST /admin/impersonate/stop`.
   - Controller: `Admin/ImpersonationController`.
   - Banner partial `backend/partials/impersonation_banner.blade.php` included in backend master layout.
   - Migration: `create_impersonation_logs_table`.

4. Notifications
   - Event: `App\Events\ShipmentStatusChanged`.
   - Listener: `App\Listeners\SendShipmentStatusNotification`.
   - Notification: `App\Notifications\ShipmentStatusChangedNotification` (queued).
   - Migration: add `notification_prefs` JSON to users.

5. Branch Management
   - `Hub` model updated with `parent()` and `children()` relations and `scopeCode()`.
   - Migrations already support extended hub attributes and branch configurations.

6. Admin UX & Filters
   - `Admin\ShipmentController@index` supports filters: `q`, `status`, `branch`, `date_from`, `date_to`.
   - Shipment index view displays `tracking_number` accessor.

## Next Steps (Recommended)

- Replace legacy Parcel-based portal creation with Shipment-first flow and TransportLegs.
- Add queue workers (e.g., Redis + Horizon) to process notifications and heavy tasks.
- Integrate Stripe/PayPal webhooks and produce a Billing History page in portal.
- Add audit trails UI for admin using Spatie Activity Log entries.
- Configure Spatie Backup for automated encrypted backups and scheduled cleanups.
- Implement per-branch configuration UI backed by `branch_configurations` table.
- Add carrier adapters (DHL/FedEx/UPS) using a `CarrierGateway` contract and job-based polling.
- Tighten CSP by eliminating `unsafe-inline` after asset pipeline adjustments.

