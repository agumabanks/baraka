## DHL-Grade Branch Module Progress Checker
Context: Execution-ready plan for Baraka Courier ERP’s branch module, preserving existing scope and sequencing to build a DHL-grade, branch-isolated workflow (Order → Operations → Workforce/Fleet → CRM → Finance → Warehouse → Settings → Hardening).

## Legend
`[ ]` not started · `[~]` in progress · `[x]` done · `[!]` blocked  
Tags: (B) backend/domain · (UI) Blade/UX · (INT) integration/hook · (MVP) must-have v1 · (ADV) advanced/phase-2

### 0) Tracker & Dependencies
- [x] Maintain progress checker with execution order and dependency notes.
- [ ] Keep CI gate green for branch module features as they land (MVP).

### Order to Ship (execution sequence)
1) Operations & Alerts foundation  
2) Workforce + Fleet  
3) Clients & CRM  
4) Finance & Invoicing  
5) Warehouse & Inventory  
6) Settings & Localization  
7) Seeders + Quick Fixes

### 1) Operations & Shipment Management (90% Complete)
- [x] Enforce branch-scoped create/view and status machine with SLA timestamps (B, MVP).
- [x] Provide manual assign/reprioritize/hold/re-route; wire auto-assign hook in `App\Services\Dispatch\AssignmentEngine` (B/UI, MVP).
- [x] Surface SLA risk (nearing-breach badges, escalation actions on dashboard/ops board) (UI/B, MVP).
- [x] Enforce branch-to-branch coordination: origin/dest/hub handoffs, inter-branch SLAs, reroute approvals; consolidation/deconsolidation for groupage vs individual with chain-of-custody trail (B/UI, MVP).
- [x] Barcode/QR lifecycle: auto-generate IDs, reprint labels, manifest printing, batch PDFs/ZPL; scanner-friendly views with duplicate/misroute validation (B/UI, MVP).
- [x] Printing & exports: printable shipment detail sheets, batch labels, route/bag manifests; shareable tracking links respecting branch isolation (UI, MVP).
- [x] Scanning & trackers: scan modes (bag/load/unload/route/delivery/returns) with ScanType enum; ScanEvent model with branch isolation (B/UI, MVP).
- [x] Alerts system: BranchAlert model with severity/category filters; acknowledge/resolve with audit trail (B/UI, MVP).
- [x] Maintenance windows: MaintenanceWindow model linked to branch/vehicle/location; capacity impact tracking (B, MVP).
- [x] UI for maintenance window scheduling and capacity visualization with filters (UI, MVP).
- [ ] Offline-safe queueing for scans; tracker hooks (GPS/temperature) attachable per shipment (UI/INT, ADV).
- [ ] Extension hooks: import from other branch/marketplace; barcode/QR fields ready (INT, ADV).
- [ ] Tests: lifecycle happy-path + invalid transitions; branch isolation; SLA risk flags; barcode duplicate/misroute validation; alert acknowledge/resolve; maintenance capacity impact.

### 2) Workforce Management (80% Complete)
- [x] Extend profiles with contact, role/job title, certifications, shift prefs, branch assignment, assignment history (B/UI, MVP).
- [x] Provide scheduling & attendance: daily/weekly shifts with calendar matrix view (UI, MVP).
- [x] Check-in/out system: track attendance with late detection and status management (B/UI, MVP).
- [x] Weekly schedule matrix: visual scheduling grid showing all workers and shifts (UI, MVP).
- [x] Today's attendance dashboard: real-time attendance tracking with quick actions (UI, MVP).
- [x] Shift statistics: on-duty count, late check-ins, no-shows tracking (B/UI, MVP).
- [~] Enforce RBAC/audit on role/status changes and onboarding/termination events (B, MVP).
- [ ] Sync driver assignments with Fleet trips (B/INT, MVP).
- [ ] Tests: RBAC on edits, attendance edge cases (late/no-show), assignment history visibility, branch isolation.

### 3) Clients & CRM (80% Complete)
- [x] Enrich client data: credit_limit, payment_terms, risk_score, kyc_status, notes fields added (B, MVP).
- [x] Client addresses table: billing/pickup/delivery addresses with contacts (B, MVP).
- [x] CRM activities: record calls, visits, emails, meetings with outcomes (B, MVP).
- [x] CRM reminders: task tracking with priority and due dates (B, MVP).
- [x] **Centralized client architecture**: Customers are system-wide, branch-scoped visibility (B, MVP).
- [x] **Branch scoping**: Customer.visibleToUser() auto-filters based on user role (admin sees all, branches see theirs) (B, MVP).
- [x] **CRM branch tracking**: Activities, reminders, addresses track branch_id for branch context (B, MVP).
- [x] **Access control**: Branch users edit only their customers, admin edits all (B, MVP).
- [x] **Audit trail**: created_by_branch_id and created_by_user_id tracking (B, MVP).
- [x] **Documentation**: Comprehensive CENTRALIZED_CLIENT_ARCHITECTURE.md guide created (DOC, MVP).
- [~] Link shipments tab and invoices tab with status filters; enforce credit limit in InvoiceGenerationService (B/UI, MVP).
- [ ] CRM pipeline UI: onboarding → active → at-risk → retention/lost workflow (UI, MVP).
- [ ] Tests: credit limit blocks/flags, activities visibility per branch, address/contact CRUD, KYC/risk flag display.

### 4) Finance & Invoicing (90% Complete)
- [x] Auto-generate draft invoices on `DELIVERED` status with InvoiceGenerationService (B, MVP).
- [x] Rate calculation: weight-based, distance-based, surcharges (fuel, handling, insurance), tax (B, MVP).
- [x] Batch invoicing: combine multiple shipments for single customer (B, MVP).
- [x] Credit limit checking: verify customer credit before invoicing (B, MVP).
- [x] Payment recording: track payments against invoices (B, MVP).
- [x] Allow review/finalize to lock amounts and link to client; store FX snapshot (B/UI, MVP).
- [x] Comprehensive finance dashboard with multiple views: overview, receivables, collections, revenue, invoices, payments (UI, MVP).
- [x] Receivables aging analysis: current, 1-15 days, 16-30 days, 31+ days buckets with visual reporting (UI/B, MVP).
- [x] Collections dashboard: daily trends, period filtering, collection methods breakdown (UI/B, MVP).
- [x] Revenue analytics: by customer, monthly trends, top revenue generators with charts (UI/B, MVP).
- [x] Top debtors report: track largest outstanding balances by customer (UI/B, MVP).
- [x] Invoice management: list, filter by status, view details (UI, MVP).
- [x] Payment history: complete transaction log with customer details (UI, MVP).
- [x] CSV export: invoices and payments export functionality (UI/INT, MVP).
- [~] Cache heavy totals and invalidate on invoice/payment/client credit changes (B, MVP).
- [ ] Tests: validation on amounts/currency/status, cache invalidation, aging buckets, branch isolation on financial data.

### 5) Warehouse & Inventory (75% Complete)
- [x] Model WhLocation with capacity metrics; branch isolation (B, MVP).
- [x] Warehouse movements tracking with from/to locations (B, MVP).
- [x] Inventory management by location with scanning support (B, MVP).
- [x] Picking lists UI: visual interface for outbound shipment picking (UI, MVP).
- [x] Pick list generation: auto-select shipments ready for dispatch (UI/B, MVP).
- [x] Picking statistics: pending, in-progress, completed tracking (UI, MVP).
- [x] Ready shipments view: select multiple for batch pick list creation (UI, MVP).
- [~] Flows: receive to location, move between locations complete; pick/pack workflows ready (B/UI, MVP).
- [ ] Trigger capacity alerts: utilization thresholds, max-age items; simple trend/heatmap (UI/B, MVP).
- [ ] Tests: capacity math, alert triggers, branch isolation on stock moves, pick/putaway correctness.

### 6) Fleet & Drivers (90% Complete)
- [x] Vehicle CRUD: plate/model/type/capacity, service intervals; status (available/on trip/maintenance/down) (B/UI, MVP).
- [x] **VehicleTrip model**: Complete trip management with routes, stops, status workflow (B/DB, MVP).
- [x] **TripStop model**: Waypoints/delivery stops with POD capture (signature/photo, recipient) (B/DB, MVP).
- [x] **VehicleMaintenance model**: Work orders, parts/labor costs, scheduling, priorities (B/DB, MVP).
- [x] **FleetController**: Trip CRUD, start/complete, maintenance scheduling/completion (B, MVP).
- [x] Trip metrics: Distance, fuel consumption, efficiency tracking (B, MVP).
- [x] Maintenance tracking: Odometer, service intervals, overdue alerts (B, MVP).
- [~] GPS/telematics stubs for lat/lng updates (INT, ADV).
- [ ] Tests: trip creation with branch scope, driver/vehicle availability rules, POD capture.

### 7) Settings & Localization (90% Complete)
- [x] **system_settings table**: Key/value store with types, categories, override control (B/DB, MVP).
- [x] **branch_setting_overrides table**: Per-branch overrides with user audit trail (B/DB, MVP).
- [x] **SettingsService**: Complete API with caching, type casting, override management (B, MVP).
- [x] Default settings: Currency (UGX), timezone, language, tax rates, SLA hours (B/DB, MVP).
- [x] Setting categories: General, finance, operations, notifications (B, MVP).
- [x] Override control: is_public flag determines override permissions (B, MVP).
- [x] Type system: String, integer, decimal, boolean, json with auto-casting (B, MVP).
- [x] Cache strategy: Per-branch caching with automatic invalidation (B, MVP).
- [~] UI for branch settings management (scaffolded, needs styling).
- [ ] Tests: override precedence, validation, access control.

### 8) Seeders, Fixes, Hardening
- [ ] Seed demo/factories: shipments across statuses, staff, clients, vehicles, invoices, warehouse locations (B, MVP).
- [ ] UX fixes: dedupe branch selector, fix “Reconcile finance” spelling, ensure dashboard quick actions hit live screens (UI, MVP).
- [ ] Security/audit: CSRF/validation/rate limits affirmed; audit logs for shipment status, roles/permissions, financial events, settings changes (B, MVP).
- [ ] Tests: seed integrity smoke, branch selector dedupe, quick-action routing, audit log generation, rate limit coverage.
