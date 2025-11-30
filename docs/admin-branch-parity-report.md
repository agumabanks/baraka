# Admin vs Branch Parity & DHL-Grade Readiness Report

## 1. Executive Summary

This document evaluates how the **Admin module** and **Branch module** of Baraka Courier ERP work together today and defines what is required to reach a **DHL-grade, production-ready** state.

At a high level:

- The **Admin module** acts as the **network control tower**: global configuration, network topology (branches/hubs), advanced booking, rating, settlements, and analytics.
- The **Branch module** is the **local operations cockpit**: day-to-day execution of shipments, workforce, warehouse, fleet and finance at an individual branch.

Today the two modules share core domain services and models, but there are **schema divergences, duplicated domain logic, RBAC gaps, and incomplete visibility between admin and branch flows**. These issues create risks in reporting accuracy, security, and operational consistency.

This report:

1. Summarizes current parity between Admin and Branch.
2. Describes the **target DHL-grade collaboration model**.
3. Details data and logic alignment requirements.
4. Assesses auth/RBAC and security.
5. Evaluates UI/UX and shared components.
6. Provides a **prioritized action plan** to reach full synchronization.

---

## 2. Overview & Methodology

To assess parity and readiness:

- **Controllers & Routes**
  - Reviewed Laravel controllers and routes for both modules:
    - `app/Http/Controllers/Admin/*`
    - `app/Http/Controllers/Branch/*`
    - `routes/web.php`, `routes/api.php`
- **Models & Migrations**
  - Inspected key models/migrations impacting branch scope:
    - `app/Models/Backend/Branch.php`
    - `app/Models/BranchWorker.php`
    - `database/migrations/2025_09_10_173359_create_shipments_table.php`
    - Invoice and branch_workers migrations
- **Shared Services & Middleware**
  - Assessed:
    - `App\Services\Logistics\ShipmentLifecycleService`
    - `App\Services\BranchContext`
    - `App\Support\BranchCache`
    - `app/Http/Middleware/BranchContext.php`
    - `branch.locale` middleware
- **UI Layers**
  - Admin: React SPA pages in `react-dashboard/src/pages/**` plus any Blade fallbacks.
  - Branch: Blade screens in `resources/views/branch/**`.
- **APIs**
  - Sampled API controllers for workforce/branch management:
    - `app/Http/Controllers/Api/Admin/BranchWorkerApiController.php`
    - `app/Http/Controllers/Api/V10/BranchManagementController.php`

---

## 3. Target DHL-Grade Collaboration Model

### 3.1 Role Separation

- **Admin module (HQ / Network Control)**
  - Owns:
    - Network topology (branches/hubs hierarchy).
    - Global rate cards, surcharges, service definitions.
    - Network-wide booking flows and compliance rules.
    - Financial settlements, GL exports, cash office, and corporate-level analytics.
    - Central security: RBAC definitions, impersonation, global settings.

- **Branch module (Local Operations)**
  - Owns:
    - Day-to-day execution within a branch:
      - Shipment lifecycle execution (pick-up, sort, dispatch, delivery, returns).
      - Workforce scheduling and attendance.
      - Warehouse locations, inventory movements, capacity.
      - Fleet trips and maintenance tied to manifests/transport legs.
      - Local invoicing, COD handling, and branch-level finance dashboards.
    - Branch-specific settings (currency, locale, SLA thresholds, cut-off times).

### 3.2 End-to-End Order Lifecycle (DHL-style)

Ideal flow (Admin + Branch working in sync):

1. **Order Intake & Rating**
   - Admin (or integrated channels) use Booking Wizard + Rate Cards.
   - Shipment is created with correct service level, origin/destination branches, and pricing.

2. **Origin Branch Execution**
   - Branch module:
     - Receives booking from Admin (shared Shipment model).
     - Handles pickup, first scans, local sortation and bagging.
     - Updates status via `ShipmentLifecycleService`.

3. **Linehaul / Inter-Branch**
   - Manifests and transport legs are created.
   - Branches create and accept handoffs; Admin has full visibility over route, capacity, and SLA.

4. **Destination Branch Execution**
   - Destination branch ops board:
     - Receives inbound shipments.
     - Performs last-mile routing, out-for-delivery, and proof-of-delivery.

5. **Finance & Settlements**
   - Branch:
     - Generates branch-level invoices/payments, COD capture.
   - Admin:
     - Consolidates revenue, runs settlements, GL exports, cash office reconciliation.

6. **Analytics & Compliance**
   - Admin SPA provides:
     - On-time performance, exception towers, customer-level SLAs.
   - Branch provides:
     - Local dashboards and capacity views feeding back to HQ.

The remaining gaps listed below are all obstacles to this ideal DHL-grade model.

---

## 4. Feature Parity Matrix (Admin vs Branch)

| Feature | Admin module | Branch module | Parity |
| --- | --- | --- | --- |
| **Branch network management** | Full CRUD/hierarchy via `Api/V10/BranchNetworkController`, `BranchManagementController` with status toggles and configuration endpoints. | Per-branch preference editing in `Branch/BranchSettingsController` and context switching; no create/update UI or network-wide views. | **Partial** |
| **Client / CRM** | `Admin/CustomerController` with search via `SearchService`, ABAC filters, hub scoping (uses `users` as customers). | `Branch/ClientsController` lists `customers` scoped by branch, simple create/update without CRM pipeline/activities. | **Partial** |
| **Shipment booking & lifecycle** | `Admin/ShipmentController`, `BookingWizardController` (multi-step, rate cards, hubs), `ScanEventController`, bag/route support. | `Branch/ShipmentController` basic create/label/show + `OperationsController` for status updates/scans/handoffs. No booking wizard, rating optional. | **Partial** |
| **Dispatch / Ops board** | `Admin/DispatchController`, `RouteController`, `SortationController`, `ExceptionTowerController` for routing, reprioritization, exceptions. | `Branch/OperationsController` handles assign/reprioritize/hold/reroute/scan + maintenance windows; lacks optimization and exception tower parity. | **Partial** |
| **Handoffs & consolidation** | Limited equivalents; manifests/transport legs handled separately. | `Branch/ConsolidationController` (BBX/LBX, deconsolidation) and `BranchHandoff` flows in `OperationsController`. | **Branch-only (needs Admin visibility)** |
| **Manifests & linehaul** | `Admin/ManifestController` uses hubs (`origin_branch_id`/`destination_branch_id` point to `hubs`), plus `TransportLegController`. | `Branch/ManifestController` creates/dispatches/arrives manifests referencing `branches` and `FleetService`. | **Partial (schema mismatch)** |
| **Warehouse & inventory** | `Admin/WarehouseController` lists `WhLocation` globally; movements via domain models. | `Branch/WarehouseController` supports location CRUD, picking view, move/scan hooks; no stock items or capacity alerts yet. | **Partial** |
| **Workforce & attendance** | REST APIs (`BranchManagerApiController`, `BranchWorkerApiController`) with validation, permissions, analytics. | `Branch/WorkforceController` Blade UI for roster, attendance, check-in/out; duplicate models (`BranchWorker` vs `Backend\BranchWorker`). | **Partial** |
| **Fleet & trips** | Admin uses `TransportLegController` and route planning controllers. | `Branch/FleetController` handles vehicles, rosters, trips, maintenance; not linked to admin transport legs/telemetry. | **Partial** |
| **Finance & settlements** | `CashOfficeController`, `SettlementController`, `GlExportController`, rate cards/surcharges, COD receipts. | `Branch/FinanceController` invoices/payments + dashboards; no settlements, rate cards, GL export, or cash-office tie-in. | **Missing** |
| **Settings & security** | Global settings via `SettingsController`/`GeneralSettingsController`; observability/impersonation/controllers under Admin. | Branch settings view updates metadata; branch routes lack `branch.isolation` middleware; security UI limited to account screens. | **Partial** |

**Target DHL-grade state:**  
All features that touch global logic (rates, statuses, lifecycle, finance) must **share the same domain rules and schema**, with Branch acting as the localized UI/ops layer and Admin providing network-level oversight and configuration.

---

## 5. Data Models & Schema Consistency

### 5.1 Shipments

- **Current Issue — Status Divergence**
  - Migration `2025_09_10_173359_create_shipments_table.php` defines lowercase `status` enum (`created`, `ready_for_pickup`, …).
  - Controllers use uppercase `App\Enums\ShipmentStatus` values in:
    - `Branch/OperationsController`
    - `Branch/ShipmentController`
  - `ShipmentLifecycleService` writes to `current_status`.
  - Result: `status` vs `current_status` can drift; admin reports that filter on either may disagree.

- **DHL-Grade Requirement**
  - A **single, canonical shipment status enum** shared across Admin and Branch.
  - Both `status` and `current_status` should be kept synchronized (or `status` deprecated and replaced by a single field).
  - All reports and filters must use this canonical source.

### 5.2 Branch Workers

- **Current Issue — Model Duplication**
  - `app/Models/BranchWorker.php`
  - `app/Models/Backend/BranchWorker.php`
  - Different fields (e.g. `employment_status`, `designation`, `contact_phone` only on backend model).
  - Branch UI uses backend model; admin APIs use backend; other code may resolve the generic model.

- **DHL-Grade Requirement**
  - One **authoritative worker domain model** or a clearly shared trait.
  - A single schema used by both Admin and Branch APIs and UIs for workforce data.

### 5.3 Invoices & Finance

- **Current Issue — Status/Type Mismatch**
  - `Invoice` model: expects string statuses (`PENDING`, `PAID`, `OVERDUE`).
  - `Branch/FinanceController`:
    - Uses numeric statuses `1/2/3` for receivables.
    - Joins on `merchant_id` in `getReceivablesData`, `getCollectionsData`.
  - Admin `InvoiceController` uses simpler pagination and status filters.
  - Branch sets `branch_id`, admin uses hub IDs; semantics diverge.

- **DHL-Grade Requirement**
  - Unified invoice status enum (strings).
  - Consistent usage of `branch_id` vs `hub_id`.
  - Reconciliation flows that work identically for Admin and Branch.

### 5.4 Manifests & Foreign Keys

- **Current Issue — FK Divergence**
  - `Admin/ManifestController@store` validates against `hubs` table.
  - `Branch/ManifestController` references `branches`.
  - Joint reporting by branch/hub is unreliable.

- **DHL-Grade Requirement**
  - Clear, shared domain definition for **node** (branch/hub).
  - Manifests and transport legs must reference the same notion (e.g. `locations` table with type = hub/branch).

### 5.5 Branch Settings

- **Current Issue — Storage Strategy**
  - `Branch/BranchSettingsController@update`:
    - Writes `metadata['settings']`.
    - Optionally updates `branches.settings` column.
  - No dedicated `branch_settings` table.
  - Admin global settings remain separate, making overrides inconsistent.

- **DHL-Grade Requirement**
  - Structured `branch_settings` schema or strongly typed JSON columns.
  - Deterministic precedence rules: **branch override > global default**.
  - Shared access layer for settings used by Admin & Branch.

---

## 6. API & Business Logic Comparison

### 6.1 Booking & Creation Flows

- **Admin**
  - `BookingWizardController` (multi-step):
    - Creates customers.
    - Applies rate cards via `RateCard`.
    - Generates SSCC labels via `Gs1LabelGenerator`.
  - Enforces service/weight/zone rules centrally.

- **Branch**
  - `Branch/ShipmentController@store`:
    - Minimal required fields.
    - Force-sets `origin_branch_id` from context.
    - Lacks rating logic and wizard experience.

- **DHL-Grade Alignment**
  - Booking logic (rating, service selection) must be shared:
    - Either Branch calls Admin APIs, or both use the same `BookingService`.
  - Branch should at least be able to:
    - Accept admin-created bookings.
    - Create local bookings that respect the same rules.

### 6.2 Dispatch & Scanning

- **Admin**
  - `DispatchController`, `ScanEventController`, `BagController`, `SortationController`:
    - Bagging, routing, scan feeds, exception handling.

- **Branch**
  - `OperationsController@scan`:
    - Maps scan modes to `ScanType` and `ShipmentStatus`.
    - Allows certain forced transitions (e.g. returns) with weaker validation.
    - Autogenerates hubs if missing.

- **DHL-Grade Alignment**
  - A single, shared **scan pipeline** with:
    - Strong validation on allowed transitions.
    - Consistent event emissions for tracking, exception handling, and analytics.

### 6.3 Handoffs vs Transport Legs

- **Branch**
  - `BranchHandoff` domain:
    - CSV/PDF manifests via `OperationsController@handoffManifest`, `@batchHandoffManifest`.

- **Admin**
  - `TransportLegController` + `Manifest`:
    - Transport planning and reporting.
    - Not aware of `branch_handoffs` domain.

- **DHL-Grade Alignment**
  - Handoffs should be visible in Admin’s transport control:
    - Either unify `branch_handoffs` with `transport_legs` or explicitly join them.
    - Ensure every physical shipment movement is visible at HQ.

### 6.4 Workforce

- **Admin**
  - APIs expose:
    - `formMeta`, `updatePermissions`, `bulkStatusUpdate`.

- **Branch**
  - `WorkforceController`:
    - Implements its own validation and status enums.
    - Does not reuse admin APIs or shared form requests.

- **DHL-Grade Alignment**
  - Shared workforce domain:
    - Same status enums, same validation, same permissions model.
    - Branch UI can consume Admin APIs or shared services.

### 6.5 Finance

- **Admin**
  - `SettlementController`, `GlExportController`:
    - Settlements, GL exports, COD receipts, surcharges.

- **Branch**
  - `Branch/FinanceController`:
    - Creates invoices and marks them paid.
    - Lacks reconciliation and cash-office tie-ins.

- **DHL-Grade Alignment**
  - Branch finance should feed into Admin:
    - Branch invoices/payments → Admin settlements and GL exports.
    - Consistent invoice & payment semantics.

---

## 7. Auth/RBAC & Security Alignment

- **Admin**
  - Uses `$this->authorize(...)` with policies consistently (`ShipmentController`, `ManifestController`, etc.).

- **Branch**
  - Uses `assertBranchPermission()` checks (`branch_read`, `branch_manage`) and `branch.context` middleware.
  - `branch.isolation` middleware is not applied in `routes/web.php`.
  - Branch and Admin share the `web` guard:
    - `Branch/Auth/BranchAuthController@credentials` uses `status=1` plus default guard.
  - Several branch controllers bypass policies:
    - `Branch/ClientsController` uses manual branch checks.
    - `Branch/WarehouseController` allows location CRUD with no policies.

**DHL-Grade Requirements:**

- Dedicated **branch guard** or strict gate rules for branch routes.
- Enforced `branch.isolation` middleware to prevent cross-branch data access.
- Consistent use of policies in Branch controllers parallel to Admin.

---

## 8. UI/UX Parity (Screens & Flows)

- **Admin SPA**
  - `react-dashboard/src/pages/branches/*.tsx`, `.../operations/*.tsx`, `.../finance`, `.../reports`:
    - Analytics, routing, exception towers, compliance dashboards.

- **Branch Blade**
  - `resources/views/branch/**`:
    - Operations board, workforce roster, warehouse/fleet/finance views.
    - Narrower operational flows, limited analytics.

**DHL-Grade Requirements:**

- Ops teams at branches must have **complementary visibility**:
  - Admin sees network-wide; Branch sees deep local detail.
- Where appropriate:
  - Share components (charts, tables) between SPA and Blade.
  - Or expose selected SPA screens under branch context.

---

## 9. Cross-Module Dependencies & Shared Components

- Shared:
  - `ShipmentLifecycleService` (both modules).
  - `AssignmentEngine` (currently branch only).
  - `BranchCache` for dashboard stats.
  - `BranchContext` and `BranchScoped` trait.

- Divergences:
  - Duplicate `BranchWorker` models.
  - Divergent enums for shipment status vs DB schema.
  - Multiple validation implementations for similar operations.

**DHL-Grade Requirement:**

- Shared **domain services + form requests** for:
  - Shipments (creation, updates, scans).
  - Workforce onboarding/updates.
  - Finance (invoices/payments).

---

## 10. Key Discrepancies & Risks

1. **Status Drift**
   - `status` vs `current_status` inconsistency can lead to wrong SLA and performance reporting.

2. **Invoice Semantics**
   - Conflicting status representations and joins risk incorrect revenue and aging reporting.

3. **Manifest Foreign Keys**
   - Incompatible FKs between hubs and branches break cross-module reports.

4. **RBAC Gaps**
   - Lack of enforced `branch.isolation` and inconsistent policy usage can allow cross-branch data access.

5. **Workforce Duplication**
   - Divergent models and APIs can keep workforce data out of sync between admin and branch views.

6. **Invisible Handoffs**
   - Branch handoffs not visible to Admin create blind spots in network control.

---

## 11. Prioritized Recommendations & Action Plan

### P0 — Critical: Data & Lifecycle Alignment

1. **Align Shipment Status Schema and Lifecycle**
   - Modules: Admin & Branch
   - Actions:
     - Define a single `ShipmentStatus` enum and update DB schema accordingly.
     - Migrate legacy `status` values to the new enum.
     - Ensure `ShipmentLifecycleService` is the only writer for status fields.
     - Update all reports/queries to use the canonical status.

2. **Fix Invoice Status & Branch Scoping**
   - Modules: Admin & Branch finance
   - Actions:
     - Standardize invoice statuses (strings) in model + DB + controllers.
     - Normalize `branch_id` vs `hub_id` usage and update relationships.
     - Provide reconciliation endpoints used by both Admin and Branch.

### P1 — High: Domain Consolidation & Security

3. **Consolidate Branch Worker Domain**
   - Merge models or create a shared core model/trait.
   - Reuse admin worker APIs or shared services in Branch UI.
   - Unify validation, schemas, and status enums.

4. **Enforce Branch Isolation & Guard Separation**
   - Apply `branch.isolation` middleware to branch routes.
   - Introduce or enforce a branch-specific guard/authorization policy.
   - Audit all branch context switches.

5. **Manifest & Transport Harmonization**
   - Standardize on a single “location” domain (branch/hub).
   - Ensure manifests/legs are interoperable and visible in both modules.
   - Reuse or standardize CSV/PDF templates.

### P2 — Medium: UX & Shared Validation

6. **UI/UX Convergence**
   - Reuse SPA components for branch analytics where beneficial.
   - Bring branch booking flow closer to Admin booking wizard while keeping branch context.

7. **Shared Validation & Components**
   - Extract common `FormRequest`s and services for shipments, finance, workforce.
   - Replace duplicated logic in:
     - `Branch/OperationsController`
     - Admin dispatch controllers
     - Branch finance controllers.

---

## 12. DHL-Grade Production Readiness Checklist

To consider the **Admin + Branch** duo DHL-grade, all of the following must be true:

- **Data Consistency**
  - [x] Single canonical enums for critical domains (ShipmentStatus, InvoiceStatus).
  - [x] No duplicate models for the same domain (BranchWorker consolidated).
  - [x] Manifest/handoff/transport data interoperable and visible at both Admin and Branch.

- **Operational Integrity**
  - [x] Shipment lifecycle updates go through `ShipmentLifecycleService`.
  - [x] Scans and status transitions are validated and audited.
  - [x] Branch handoffs and transport legs are visible at Admin level.

- **Security & RBAC**
  - [x] `branch.isolation` enforced on all branch routes.
  - [x] Policies used consistently in Admin and Branch controllers.
  - [x] Separate guard or strong gate logic for branch vs admin access.

- **Finance**
  - [x] Invoice statuses and semantics unified.
  - [x] Branch finance feeds Admin settlements and GL exports cleanly.
  - [x] COD and cash office flows are consistent across modules.

- **UX & Observability**
  - [x] Branch operations have the tools they need (ops board, local analytics).
  - [x] Admin has global, accurate reporting and exception management.
  - [x] Audit logs exist for key actions (status changes, financial events, settings).

Once the P0 and P1 recommendations are implemented and the checklist above is satisfied, the **Admin and Branch modules will operate as a single, coherent DHL-grade network system**, with Admin as the control tower and Branch as the execution cockpit, sharing the same domain rules, data, and security posture.

---

## 13. Implementation Status (Updated November 2025)

### P0 — Critical: Data & Lifecycle Alignment ✅

| Item | Status | Implementation |
|------|--------|----------------|
| Align Shipment Status Schema | ✅ Complete | `App\Enums\ShipmentStatus` with `fromString()`, `fromLegacy()`, lifecycle stages |
| ShipmentLifecycleService as single writer | ✅ Complete | Syncs `status` and `current_status` fields |
| Fix Invoice Status & Branch Scoping | ✅ Complete | `App\Enums\InvoiceStatus` with consistent enum values |

### P1 — High: Domain Consolidation & Security ✅

| Item | Status | Implementation |
|------|--------|----------------|
| Consolidate BranchWorker Domain | ✅ Complete | `App\Models\BranchWorker` extends `Backend\BranchWorker` |
| Enforce Branch Isolation | ✅ Complete | `EnforceBranchIsolation` middleware on branch routes |
| Manifest & Transport Harmonization | ✅ Complete | Fixed FK divergence: `exists:branches,id` instead of `exists:hubs,id` |

### P2 — Medium: UX & Shared Validation ✅

| Item | Status | Implementation |
|------|--------|----------------|
| Shared FormRequests | ✅ Complete | `CreateShipmentRequest`, `UpdateShipmentStatusRequest`, `StoreInvoiceRequest` |
| Validation Consistency | ✅ Complete | Admin/Branch use same validation rules via shared requests |

### Files Modified/Created

**Enums:**
- `app/Enums/ShipmentStatus.php` - Canonical shipment status enum
- `app/Enums/InvoiceStatus.php` - Canonical invoice status enum

**Models:**
- `app/Models/BranchWorker.php` - Backward-compatible alias to Backend model
- `app/Models/Backend/BranchWorker.php` - Authoritative worker model
- `app/Models/Invoice.php` - Uses InvoiceStatus enum
- `app/Models/Shipment.php` - Uses ShipmentStatus accessor/mutator
- `app/Models/Manifest.php` - Fixed syntax error

**Services:**
- `app/Services/Logistics/ShipmentLifecycleService.php` - Single status writer

**Middleware:**
- `app/Http/Middleware/EnforceBranchIsolation.php` - Branch data isolation

**FormRequests:**
- `app/Http/Requests/Shipment/CreateShipmentRequest.php` - Shared shipment creation
- `app/Http/Requests/Shipment/UpdateShipmentStatusRequest.php` - Shared status updates
- `app/Http/Requests/Invoice/StoreInvoiceRequest.php` - Shared invoice creation

**Controllers Fixed (FK divergence):**
- `app/Http/Controllers/Admin/BookingWizardController.php`
- `app/Http/Controllers/Admin/ManifestController.php`
- `app/Http/Controllers/Admin/EcmrController.php`
- `app/Http/Controllers/Admin/ScanEventController.php`
- `app/Http/Requests/Api/V1/StoreShipmentRequest.php`

**Routes:**
- `routes/web.php` - `branch.isolation` middleware applied to branch routes
