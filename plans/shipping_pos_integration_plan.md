# Shipping POS Integration Plan

## Goals
- Align POS bookings with core shipment, finance, and tracking workflows.
- Eliminate data-model drift (customer linkage, tracking numbers, payments).
- Harden authorization and operational safety for branch desks.
- Preserve existing UX while removing dead/duplicated logic.

## Scope
- Admin/Branch POS (`admin/pos`, `branch/pos`) flows and APIs in `ShipmentPosController`.
- Data model touchpoints: shipments, customers, payments, tracking/labels.
- Downstream modules: dispatch/lifecycle, finance (COD/remittance), analytics, notifications.

## Key Risks / Constraints
- Existing shipments use `customer_id` pointing to `users`; migration must be reversible/backfilled.
- Rate cards may be missing; default pricing must remain functional.
- POS desks rely on speed; added validations must not slow UX.
- Coordination with finance (COD/open balances) to avoid double-posting.

## Plan of Action
1) **Customer Model Alignment**
   - Migrate `shipments.customer_id` to reference `customers` table (add `customer_user_id` fallback if needed).
   - Update `Shipment::customer` relation to `Customer`; adjust label/receipt/customer display to use `display_name`/phones.
   - Backfill existing shipments to the correct customer record or map to user via email/phone; maintain audit trail.
   - Tighten POS validation to ensure selected customer is scoped to the branch/user visibility rules.

2) **POS Creation Pipeline Refactor**
   - Route POS creation through `ShipmentService::createShipment` (or a POS wrapper) instead of raw `Shipment::create`.
   - Pass parcel data; persist parcels (not just metadata) when schema supports it.
   - Use centralized tracking/waybill generator (`SystemSettings::trackingPrefix`); enforce uniqueness/branch prefixing.
   - Record lifecycle events via `ShipmentLifecycleService` and emit notifications/analytics.
   - Ensure metadata flags `created_via = pos` and stores POS inputs (payer, pieces, special instructions).

3) **Pricing & Service Levels**
   - Normalize service-level input to the rate service; handle `success=false` responses gracefully in UI (show fallback/disable create).
   - Keep service-level comparison cards in sync with rate responses; hide stale prices on error.
   - Preserve volumetric calculations and display; validate length/width/height bounds server-side.

4) **Payments & Finance Coordination**
   - Reconcile allowed payment methods (settings, UI, validation) and remove mismatches (`account` vs `credit` vs `bank_transfer`).
   - Persist payments via `Payment` model (metadata: cashier, branch, POS transaction id); avoid raw `DB::table` inserts.
   - For COD, record receivable/outstanding instead of marking paid; hook into COD settlement/remittance flows.
   - Link payments to invoices where applicable; ensure currency/FX handling matches finance settings.

5) **Authorization & Scoping**
   - Enforce policy checks on POS reads (quick track, label/receipt views) and scope to branch where applicable using `BranchContext`.
   - Prevent cross-branch data leakage in quick-track and recent shipments dashboard.
   - Maintain admin override with explicit policy allowance.

6) **Receipts, Labels, and Tracking**
   - Ensure labels/receipts use the corrected customer relation and branch/company branding from settings.
   - Provide consistent tracking/waybill formats for downstream EDI/partners; include prefixes in metadata.
   - Keep ZPL/PDF generation paths intact; add error handling for missing PDF driver.

7) **UI Hardening**
   - Remove duplicate utility definitions (toast/sound) and unreachable “coming soon” buttons or gate them.
   - Improve button enablement state to reflect validation (customer, destination, weight, rate success).
   - Maintain keyboard shortcuts and hold/repeat flows; persist localStorage keys as-is.

8) **Telemetry & Auditing**
   - Log POS transactions with branch/user context; include pricing payload for reconciliation.
   - Add activity log entries on shipment creation and payment capture.

9) **Migration & Backfill Steps**
   - Schema migration for `shipments.customer_id` FK to `customers` (and optional `customer_user_id`).
   - Data backfill script to map legacy user-based customer IDs to `customers` rows (via phone/email/company).
   - Rollback plan: retain old column or shadow column during cutover if needed.

10) **Testing & Rollout**
    - Unit/feature tests: POS create (all payment methods), rate calc error paths, quick-track scoping, label/receipt rendering.
    - Seeded fixtures for branches, customers, rate cards, payment settings.
    - Staged rollout: migrate, deploy with feature flag for POS creation path; monitor logs/payments/dispatch queues.

## Deliverables
- Migrations and model relation updates for customer alignment.
- Refactored POS controller/service wiring, payments integration, and UI fixes.
- Tests covering POS create, rate calc, and access control.
- Ops playbook for backfill and rollback.

## Assessment & Progress Checks
- **Data Model**: Customer FK migration applied; backfill script run; `Shipment::customer` returns `Customer` in labels/receipts.
- **POS Flow**: `createShipment` uses service pipeline (parcels, lifecycle, analytics); tracking/waybill from central generator.
- **Pricing**: Rate calc handles failure paths; service-level cards show/hide based on `success`; volumetric math verified.
- **Payments**: Allowed methods consistent (settings/UI/validation); payments via `Payment` model with cashier/branch metadata; COD recorded as receivable.
- **Auth/Scope**: Quick-track and POS endpoints enforce policy and branch scoping; branch UI matches admin capabilities where permitted.
- **UX Hardening**: Duplicate utilities removed; disabled state tied to validation; placeholders gated/hidden.
- **Reliability**: Retry/fallback for rate calc/payment capture; logging/telemetry capturing POS context.
- **Tests**: Feature tests for POS create (all payment methods), rate error handling, quick-track scoping, label/receipt rendering; CI passing.
