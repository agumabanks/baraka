BARAKA POS HARDENING & EVOLUTION PLAN
0. Meta & Setup
0.1 Project Scaffolding

 POS-SETUP-01 – Create POS Plan Folder

Create /plans/pos/ in repo.

Save this file as /plans/pos/pos_hardening_v1.md.

 POS-SETUP-02 – Codebase Mapping

Identify and write down:

POS front-end entry file(s) (e.g. resources/js/admin/shipments/PosApp.jsx).

POS backend controller (e.g. App\Http\Controllers\Admin\PosController).

Shipment model + table (shipments).

Any existing rating/pricing logic.

Document paths in /plans/pos/pos_code_map.md.

 POS-SETUP-03 – Feature Flag

Add a config flag pos.enhanced_enabled so you can safely toggle new POS on/off per environment.

1. UX & Workflow Polish
1.1 POS Wizard & Layout

 POS-UX-01 – Convert POS to 5-step wizard

Steps:

Sender & receiver

Route & service level

Package & weight

Pricing & payment

Review & confirm

Each step is its own component, with shared state in a parent PosWizard component.

Acceptance:

User can move next/previous between steps without losing data.

“Review” shows fully-populated summary before save.

 POS-UX-02 – Persistent Summary Panel

Add a right-side summary panel that shows live updates:

Origin / destination / service / ETA

Price breakdown (base, surcharges, tax, total)

Payer (sender/receiver/third party)

Any warnings (cut-off missed, overweight, etc.)

Acceptance:

Summary updates instantly when relevant fields change.

Visible on all wizard steps.

1.2 Keyboard-First & Scanner-Friendly

 POS-UX-03 – Tab order & shortcuts

Define logical tab order for all inputs.

Implement keyboard shortcuts:

Alt+N – New shipment

Alt+S – Save & Print

Alt+P – Go to Payment step

Alt+R – Review step

Show tooltips / hint text for shortcuts.

 POS-UX-04 – Scanner input handler

Implement a hidden/explicit input that captures scanner data:

If scan starts with prefix C- → treat as client code.

If S- → treat as shipment ID.

On scan:

Auto-lookup client and pre-fill sender/receiver.

Acceptance:

Scanning from a USB scanner behaves like keyboard typing and triggers lookup.

1.3 Smart Defaults & Templates

 POS-UX-05 – Default values

Default origin = user’s branch.

Default service = last used by this user.

Default payer = account client (if client has credit account configured).

 POS-UX-06 – Route templates

New DB table: route_templates:

id, name, origin_branch_id, destination_branch_id, default_service_level, active.

UI: dropdown “Apply Route Template”.

Acceptance:

Selecting a template auto-fills route + service.

Template management page (list, create, deactivate).

2. Validation & Business Rules
2.1 Weight, Dimensions & Volumetric Weight

 POS-BR-01 – Volumetric weight calculation

Add config: pos.volumetric_divisor (e.g. 5000).

On package step:

Calculate volumetric_weight = (L * W * H) / divisor.

Show both actual and chargeable weight (max of actual/volumetric).

 POS-BR-02 – Service weight limits

New table: service_constraints with:

service_level, origin_branch_id, destination_branch_id, min_weight, max_weight.

Enforce:

If weight outside constraints → block and show clear error.

2.2 Route & Service Eligibility

 POS-BR-03 – Route-capability matrix

New table: route_capabilities:

origin_branch_id, destination_branch_id, service_level,
max_weight, hazmat_allowed, cod_allowed, status.

POS backend endpoint GET /api/pos/route-capabilities:

Used to populate allowed services when origin/destination are selected.

 POS-BR-04 – Dynamic service filtering

When user selects route:

Only show service levels that exist in route_capabilities.

If user tries to call endpoint with invalid combo → validation error.

2.3 Dangerous / Restricted Goods

 POS-BR-05 – Contents classification

Add fields to shipment:

content_type (document, parcel, battery, liquid, hazmat, other).

For hazmat: un_number, hazmat_class, packaging_group.

Business rules:

If hazmat chosen but route hazmat_allowed = false → block.

2.4 Address & Contact Validation

 POS-BR-06 – Contact validation

Add backend validation rules:

Phone regex per country (config-driven).

Email format validation when email supplied.

Enforce minimal address length (e.g. > 10 chars).

3. Rating Engine & Pricing
3.1 Dedicated Rating Service

 POS-RATE-01 – Create RatingService

New class App\Services\RatingService.

Public method quote(ShipmentDraft $draft): RatingResult.

Uses:

Route capability

Rate tables

Weight, options, COD, insurance.

 POS-RATE-02 – API endpoint /quote

Endpoint: POST /api/pos/quote.

Input: draft payload (route, weight, options).

Output:

{
  "base_freight": 0,
  "weight_charge": 0,
  "fuel_surcharge": 0,
  "insurance_fee": 0,
  "cod_fee": 0,
  "tax": 0,
  "total": 0,
  "currency": "USD",
  "rate_table_version": "string",
  "warnings": []
}


 POS-RATE-03 – POS uses quote response

On package or options change, POS calls /quote and updates the summary panel.

UI fields become read-only (no manual price editing) except for approved overrides.

3.2 Tariffs & Customer Contracts

 POS-RATE-04 – Tariff tables

New tables:

tariffs (public tariff sets per service, zone, weight break).

customer_contracts + customer_contract_items.

RatingService:

If shipment customer has active contract, use contract rules.

Else use public tariff.

 POS-RATE-05 – Versioning

Add rate_table_version column to shipments.

Ensure RatingService always resolves a version string and persists it.

3.3 Overrides & Discounts

 POS-RATE-06 – Discount workflow

UI:

Button “Request Discount”.

Requires % or absolute amount + reason text.

Backend:

Validate max discount vs branch policy.

Require supervisor approval (see security section).

Log:

Original rating vs final rating in shipment_audits table.

4. Payment & Finance Integration
4.1 Payment Scenarios

 POS-PAY-01 – Payer & method matrix

Payer enum:

sender, receiver, third_party, account.

Payment method enum:

cash, card, mobile_money, bank_transfer, on_account.

Rules:

If on_account → ensure client has credit account and available balance.

If receiver or third_party → mark shipment as “unpaid” at creation but with payer assigned.

4.2 Idempotent Transactions

 POS-PAY-02 – Payment transaction model

New payment_transactions table:

id, shipment_id, idempotency_key, amount, currency, status, method, external_reference, created_by.

When “Pay & Print” clicked:

Generate idempotency_key.

POST to /api/pos/pay (server ensures idempotent).

4.3 Ledger Integration

 POS-PAY-03 – Accounting posting

New service App\Services\Finance\PostingService.

On successful payment:

Post GL entries (for now at least store in accounting_entries table).

Prepare interface for later sync to external accounting / ERP.

5. Reliability, Idempotency & Offline Tolerance
5.1 Shipment Drafts & Idempotent Create

 POS-REL-01 – Shipment drafts

Before final “Confirm”, POS works on a shipment_drafts resource.

Table shipment_drafts:

id (UUID), payload (JSON), created_by, branch_id, status.

Endpoint POST /api/pos/draft to save/refresh.

 POS-REL-02 – Idempotent create endpoint

Endpoint: POST /api/pos/shipments/create.

Body includes draft_id and idempotency_key.

Server:

If shipment already created for idempotency_key → return that shipment.

Else create, link to draft, and mark draft as completed.

5.2 Print Safety

 POS-REL-03 – Reprint support

Label printing separated from creation:

POST /api/pos/shipments/{id}/print-label.

Record last_label_printed_at, count of prints.

UI: “Reprint label” available to admins with audit log entry.

5.3 Performance

 POS-REL-04 – Caching & preload

On POS load, prefetch:

Branch list

Service levels

Recent clients for that branch.

Cache in browser storage for X minutes.

6. Security, RBAC & Audit
6.1 Roles & Permissions

 POS-SEC-01 – Define roles

New roles in DB / config:

counter_agent, branch_admin, hq_admin, support.

Map what each can do in POS:

Counter agent: create, view own branch, no discount > X%.

Branch admin: approve discounts, reassign drivers, reprint labels.

HQ admin: all.

 POS-SEC-02 – Enforce permissions

Use middleware or policy classes on POS-related APIs:

ShipmentPolicy, DiscountPolicy.

Return 403 on unauthorized actions.

6.2 Supervisor Overrides

 POS-SEC-03 – Supervisor override flow

UI:

When agent requests restricted action (discount, cancel, backdate):

Popup “Supervisor approval required”.

Supervisor enters PIN or does 2FA action.

Backend:

Endpoint /api/pos/approve-override:

Checks supervisor role + password/OTP.

Logs override_action, requested_by, approved_by, reason.

6.3 Audit Trail

 POS-SEC-04 – Shipment audit log

Table shipment_audits:

shipment_id, event_type (created, updated, discount_applied, label_reprinted, cancelled), old_values, new_values, created_by, created_at.

In all critical paths (create, update, discount, payment, print) write an audit event.

6.4 Hardening Basics

 POS-SEC-05 – Disable debug bar in production

Ensure APP_DEBUG=false in env.

Remove / restrict debug toolbar.

 POS-SEC-06 – Rate limiting

Apply throttling (Laravel rate limiting) to:

Client search

Quote endpoint

Create endpoint

7. Integration & Labels
7.1 Barcode & Label Standards

 POS-INT-01 – Standardize waybill ID format

Decide on format (e.g. alphanumeric, length 12–18).

Ensure uniqueness and non-sequential enough for security.

 POS-INT-02 – Barcode content

Use Code-128 or GS1-128.

Encode at least:

Shipment ID

Optional check digit.

 POS-INT-03 – Label layout

Update label template to include:

Origin / destination

Service level

COD flag & amount (if any)

Sender / Receiver names

Barcode.

7.2 API-first Design

 POS-INT-04 – Public-ish POS APIs

Ensure the following endpoints are clean, documented and stable:

/api/pos/clients/search

/api/pos/quote

/api/pos/shipments/create

/api/pos/shipments/{id}

Write OpenAPI/Swagger spec in /docs/api/pos.yaml.

8. Observability & QA
8.1 Metrics & Dashboards

 POS-QA-01 – POS metrics

Log and track:

Time from POS load → shipment creation.

Count of validation errors by field.

Discount request frequency by user/branch.

Payment failures.

8.2 Testing Strategy

 POS-QA-02 – Unit tests

RatingService unit tests for:

Route rules

Volumetric vs actual weight

Contracts vs public tariffs.

 POS-QA-03 – API tests

Feature tests for:

/quote

/shipments/create (idempotency)

/pay

Authorization failures.

 POS-QA-04 – End-to-end tests

Use Cypress/Playwright:

Full wizard flow (happy path).

Double-click “Pay & Print” protection.

Invalid route/service; validation messages.

Discount + supervisor approval.

9. Rollout & Phasing
9.1 Internal Pilot

 POS-ROLLOUT-01 – Pilot branch

Enable enhanced POS for a single branch (feature flag).

Collect feedback & metrics for 2–4 weeks.

 POS-ROLLOUT-02 – Adjust & fix

Prioritize:

Top UX complaints from agents.

Any pricing or validation bugs detected.

9.2 Network-wide Rollout

 POS-ROLLOUT-03 – Enable for all branches

Gradually enable pos.enhanced_enabled=true for:

Destination branches

Hubs once created.

 POS-ROLLOUT-04 – Sunset old POS

Once stable:

Remove legacy POS code paths.

Archive plan and mark version (e.g. POS v2.0).