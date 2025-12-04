BARAKA POS HARDENING & EVOLUTION PLAN

## 0. Meta & Setup
- [x] POS-SETUP-01 – Create POS Plan Folder  
  Create `/plans/pos/` in repo and save this file as `/plans/pos/pos_hardening_v1.md`.
- [x] POS-SETUP-02 – Codebase Mapping  
  Identify and write down: POS front-end entry file(s), POS backend controller, Shipment model/table, rating/pricing logic. Document paths in `/plans/pos/pos_code_map.md`.
- [x] POS-SETUP-03 – Feature Flag  
  Add config flag `pos.enhanced_enabled` to toggle new POS per environment.

## 1. UX & Workflow Polish
### 1.1 POS Wizard & Layout
- [ ] POS-UX-01 – Convert POS to 5-step wizard  
  Steps: sender/receiver; route/service; package/weight; pricing/payment; review/confirm. User can move back/forward without losing data; review shows full summary.
- [ ] POS-UX-02 – Persistent Summary Panel  
  Right-side live summary: origin/destination/service/ETA; price breakdown; payer; warnings. Updates instantly on field changes and visible on all steps.

### 1.2 Keyboard-First & Scanner-Friendly
- [ ] POS-UX-03 – Tab order & shortcuts  
  Logical tab order; shortcuts: Alt+N (New), Alt+S (Save & Print), Alt+P (Payment step), Alt+R (Review). Show hints.
- [ ] POS-UX-04 – Scanner input handler  
  Hidden/explicit input for scanner: `C-` -> client code, `S-` -> shipment ID. Auto-lookup and pre-fill sender/receiver.

### 1.3 Smart Defaults & Templates
- [ ] POS-UX-05 – Default values  
  Default origin = user’s branch; default service = last used by user; default payer = account client (if credit account configured).
- [ ] POS-UX-06 – Route templates  
  New table `route_templates` (id, name, origin_branch_id, destination_branch_id, default_service_level, active). UI dropdown “Apply Route Template”; management page to list/create/deactivate.

## 2. Validation & Business Rules
### 2.1 Weight, Dimensions & Volumetric Weight
- [ ] POS-BR-01 – Volumetric weight calculation  
  Config `pos.volumetric_divisor` (e.g., 5000). Show actual vs chargeable (max of actual/volumetric).
- [ ] POS-BR-02 – Service weight limits  
  Table `service_constraints` with service_level, origin_branch_id, destination_branch_id, min_weight, max_weight. Block out-of-range with clear error.

### 2.2 Route & Service Eligibility
- [ ] POS-BR-03 – Route-capability matrix  
  Table `route_capabilities` with origin_branch_id, destination_branch_id, service_level, max_weight, hazmat_allowed, cod_allowed, status. Endpoint GET `/api/pos/route-capabilities` for POS.
- [ ] POS-BR-04 – Dynamic service filtering  
  Only show services that exist in `route_capabilities`; invalid combos -> validation error.

### 2.3 Dangerous / Restricted Goods
- [ ] POS-BR-05 – Contents classification  
  Shipment fields: content_type (document, parcel, battery, liquid, hazmat, other); hazmat details (un_number, hazmat_class, packaging_group). Block hazmat if route disallows.

### 2.4 Address & Contact Validation
- [ ] POS-BR-06 – Contact validation  
  Phone regex per country (config-driven); email format validation; minimum address length (>10 chars).

## 3. Rating Engine & Pricing
- [ ] POS-RT-01 – Rate-card completeness  
  Ensure rate_cards cover service_level, currency, min_charge, weight_breaks, origin/dest zones; fallbacks seeded; validation on missing cards.
- [ ] POS-RT-02 – Pricing guardrails  
  Block save when pricing fails; show clear error; log failures with context.

## 4. Payments & Finance
- [ ] POS-PAY-01 – Payment methods alignment  
  Cash/card/mobile_money/credit/account/bank_transfer/COD; enforce via validation and UI; record via Payment model with branch/cashier metadata.
- [ ] POS-PAY-02 – COD handling  
  Treat COD as receivable; do not mark paid unless collected; ensure remittance/settlement flows.

## 5. Data Consistency & Tracking
- [ ] POS-DATA-01 – Customer linkage  
  Use customer profiles (`customers` table) with optional linked user; ensure labels/receipts pull profile.
- [ ] POS-DATA-02 – Metadata & lifecycle  
  Persist pricing breakdown, payer, parcels; route creation via ShipmentService; lifecycle events logged.
- [ ] POS-DATA-03 – Branch scoping  
  Quick-track and stats scoped to branch (admin can override); recent shipments filtered accordingly.

## 6. Resilience, Telemetry & Tests
- [ ] POS-RES-01 – Telemetry  
  Log POS rate calc failures, payment captures, creation attempts with branch/user context.
- [ ] POS-RES-02 – Tests  
  Feature tests for POS create (all payment methods), pricing error path, quick-track scoping, receipt/label rendering; fixtures for rate cards/branches/customers.

## 7. Rollout & Verification
- [ ] POS-REL-01 – Migrations & seeding  
  Run migrations (rate card fields, customer profile, templates/capabilities) and seed defaults.
- [ ] POS-REL-02 – Smoke & staging verification  
  Manual: calculate rate, verify summary, create shipment, verify payment/receipt/label, quick-track; staging sign-off.
