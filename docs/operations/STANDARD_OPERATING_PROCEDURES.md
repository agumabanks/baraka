# Baraka Logistics - Standard Operating Procedures (SOPs)

**Document Version:** 1.0  
**Effective Date:** November 28, 2025  
**Last Updated:** November 28, 2025

---

## Table of Contents

1. [Exception Handling](#1-exception-handling)
2. [Lost Shipment Procedures](#2-lost-shipment-procedures)
3. [COD Discrepancy Resolution](#3-cod-discrepancy-resolution)
4. [Damaged Goods Handling](#4-damaged-goods-handling)
5. [Customer Complaints](#5-customer-complaints)
6. [Return Shipment Processing](#6-return-shipment-processing)

---

## 1. Exception Handling

### 1.1 Exception Types

| Type | Code | Description | SLA Response |
|------|------|-------------|--------------|
| Address Issue | ADDR | Invalid/incomplete address | 2 hours |
| Customer Unavailable | CUST_UA | No one to receive | 4 hours |
| Refused Delivery | REFUSED | Customer refused shipment | 2 hours |
| Damaged in Transit | DMG | Package damaged | Immediate |
| Customs Hold | CUST_HOLD | Held at customs | 24 hours |
| Missing Documentation | DOC | Missing customs/shipping docs | 4 hours |
| Weather Delay | WEATHER | Weather-related delay | 24 hours |
| Security Hold | SEC | Security inspection required | Immediate |
| Payment Issue | PAY | COD/payment problem | 2 hours |

### 1.2 Exception Handling Workflow

```
1. IDENTIFY
   └── Driver/Operator identifies exception
   └── Log in system with exception type
   └── Take photos if applicable

2. NOTIFY
   └── System auto-notifies branch manager
   └── SMS/Email sent to customer
   └── Escalate to HQ if critical (DMG, SEC)

3. INVESTIGATE
   └── Branch manager reviews within SLA
   └── Contact customer if needed
   └── Gather additional information

4. RESOLVE
   └── Select resolution action
   └── Update shipment status
   └── Document resolution

5. CLOSE
   └── Verify customer satisfaction
   └── Update final status
   └── Generate exception report
```

### 1.3 Resolution Actions

**Address Issue (ADDR):**
1. Call customer within 30 minutes
2. Verify correct address
3. Update in system
4. Reschedule delivery
5. If unreachable after 3 attempts → Return to Sender

**Customer Unavailable (CUST_UA):**
1. Leave delivery attempt notice (if applicable)
2. Send SMS notification
3. Attempt redelivery next business day
4. After 3 attempts → Hold at branch for 7 days
5. After 7 days → Return to Sender

**Refused Delivery (REFUSED):**
1. Document reason for refusal
2. Notify sender immediately
3. Hold for 24 hours for sender decision
4. Process return if no response

---

## 2. Lost Shipment Procedures

### 2.1 Definition

A shipment is considered "lost" when:
- No scan events for 72+ hours (domestic)
- No scan events for 7+ days (international)
- Last known location cannot be verified
- Physical search at last known location fails

### 2.2 Lost Shipment Investigation Process

**Step 1: Verification (Day 0-1)**
```
□ Confirm no recent scans in system
□ Check all possible tracking numbers/barcodes
□ Verify shipment wasn't delivered without scan
□ Contact last known handler/branch
□ Review CCTV footage if available
```

**Step 2: Physical Search (Day 1-2)**
```
□ Search last known branch/hub
□ Check vehicles used on route
□ Inspect loading/unloading areas
□ Contact downstream branches
□ Check return/exception areas
```

**Step 3: Escalation (Day 2-3)**
```
□ Escalate to Operations Manager
□ File internal incident report
□ Notify customer of investigation
□ Contact all parties in chain of custody
□ Review manifests and handoffs
```

**Step 4: Resolution (Day 3-5)**
```
□ If found → Expedite delivery
□ If not found → Initiate claim process
□ Update customer with outcome
□ Complete loss report
□ Process insurance claim if applicable
```

### 2.3 Customer Communication Templates

**Initial Notification:**
```
Subject: Shipment Investigation - [TRACKING_NUMBER]

Dear [CUSTOMER_NAME],

We are currently investigating the status of your shipment 
[TRACKING_NUMBER]. Our team is actively searching for your 
package and will provide an update within 48 hours.

We apologize for any inconvenience caused.

Reference: [CASE_NUMBER]
```

**Resolution - Found:**
```
Subject: Shipment Located - [TRACKING_NUMBER]

Dear [CUSTOMER_NAME],

We are pleased to inform you that your shipment [TRACKING_NUMBER] 
has been located. It will be delivered on [DATE].

We sincerely apologize for the delay.
```

**Resolution - Lost:**
```
Subject: Shipment Claim Process - [TRACKING_NUMBER]

Dear [CUSTOMER_NAME],

Despite our extensive search, we were unable to locate your 
shipment [TRACKING_NUMBER]. We sincerely apologize for this 
unfortunate situation.

We have initiated the claims process. Please provide:
- Proof of value (invoice/receipt)
- Item description
- Bank details for reimbursement

Claim Reference: [CLAIM_NUMBER]
```

### 2.4 Compensation Guidelines

| Declared Value | Insurance Type | Compensation |
|----------------|----------------|--------------|
| No declaration | None | Up to $100 |
| With declaration | Basic | Up to $1,000 |
| With declaration | Full | Up to $10,000 |
| With declaration | Premium | Full declared value |

---

## 3. COD Discrepancy Resolution

### 3.1 COD Discrepancy Types

| Type | Description | Action Required |
|------|-------------|-----------------|
| Short Collection | Less than expected | Immediate escalation |
| Over Collection | More than expected | Return excess to customer |
| Currency Mismatch | Wrong currency collected | Convert and reconcile |
| No Collection | COD not collected | Verify delivery status |
| Partial Collection | Partial amount received | Document reason |

### 3.2 Resolution Workflow

**Step 1: Identification**
```
□ Driver reports discrepancy in app/system
□ System flags mismatched amounts
□ Branch manager notified
□ Amount difference calculated
```

**Step 2: Investigation (Same Day)**
```
□ Review driver's collection records
□ Verify delivery POD
□ Contact customer if needed
□ Check for data entry errors
□ Review any notes/comments
```

**Step 3: Resolution**

**Short Collection:**
1. Contact customer to collect balance
2. If customer disputes:
   - Review POD and signatures
   - Check SMS/notifications sent
   - Escalate to dispute resolution
3. If driver error:
   - Deduct from driver account
   - Issue warning if recurring
4. Update financial records

**Over Collection:**
1. Contact customer immediately
2. Arrange refund:
   - Mobile money transfer (preferred)
   - Cash return on next visit
   - Bank transfer
3. Document refund confirmation
4. Update financial records

### 3.3 COD Reconciliation Schedule

| Action | Frequency | Responsible |
|--------|-----------|-------------|
| Daily Cash Handover | Daily (EOD) | Driver → Branch |
| Branch Reconciliation | Daily | Branch Cashier |
| HQ Reconciliation | Weekly | Finance Team |
| Driver Account Audit | Monthly | Finance Manager |
| Full COD Audit | Quarterly | Internal Audit |

### 3.4 Driver COD Accountability

**Daily Limits:**
- Maximum COD per driver: $5,000 equivalent
- Must remit collections EOD
- Shortage deducted from salary
- 3 discrepancies = suspension

**Cash Handling Rules:**
1. Count cash in front of customer
2. Issue receipt immediately
3. Secure cash in locked pouch
4. Never mix personal/business cash
5. Report any issues immediately

---

## 4. Damaged Goods Handling

### 4.1 Damage Assessment

**At Pickup:**
```
□ Inspect packaging condition
□ Document any pre-existing damage
□ Take photos
□ Note on pickup receipt
□ Alert sender if severe
```

**During Transit:**
```
□ Report immediately if damage occurs
□ Photograph damage
□ Secure remaining contents
□ Complete damage report form
□ Notify branch manager
```

**At Delivery:**
```
□ Allow customer inspection
□ Document customer observations
□ If refused due to damage:
   - Complete damage report
   - Return to branch
   - Initiate claim
```

### 4.2 Damage Report Requirements

| Field | Required | Example |
|-------|----------|---------|
| Tracking Number | Yes | TRK-XXXXXX |
| Date/Time Discovered | Yes | 2025-11-28 14:30 |
| Location | Yes | Kinshasa Hub |
| Description | Yes | Crushed corner, contents visible |
| Photos | Yes | Min 3 photos |
| Witness | Recommended | John Driver, Jane Hub |
| Estimated Value | Yes | $500 |

### 4.3 Claims Processing

**Timeline:**
- Day 0: Damage reported
- Day 1: Investigation begins
- Day 3: Initial assessment complete
- Day 5: Customer notified of decision
- Day 10: Payment processed (if approved)

---

## 5. Customer Complaints

### 5.1 Complaint Categories

| Category | Priority | Response SLA |
|----------|----------|--------------|
| Lost/Missing | High | 2 hours |
| Damaged | High | 2 hours |
| Delay | Medium | 4 hours |
| Poor Service | Medium | 4 hours |
| Billing Issue | Medium | 24 hours |
| General Inquiry | Low | 24 hours |

### 5.2 Complaint Resolution Process

```
1. RECEIVE
   - Log complaint in CRM
   - Assign ticket number
   - Send acknowledgment

2. CLASSIFY
   - Determine category
   - Assign priority
   - Route to appropriate team

3. INVESTIGATE
   - Gather all information
   - Review shipment history
   - Contact involved parties

4. RESOLVE
   - Propose solution
   - Get customer approval
   - Implement resolution

5. FOLLOW UP
   - Confirm satisfaction
   - Update records
   - Close ticket
```

### 5.3 Escalation Matrix

| Level | Trigger | Contact |
|-------|---------|---------|
| L1 | Initial complaint | Customer Service |
| L2 | Unresolved >24hrs | Branch Manager |
| L3 | Unresolved >48hrs | Operations Manager |
| L4 | Unresolved >72hrs | Country Manager |
| L5 | Unresolved >1 week | CEO |

---

## 6. Return Shipment Processing

### 6.1 Return Reasons

| Code | Reason | Processing Time |
|------|--------|-----------------|
| RTS | Return to Sender (customer request) | 3-5 days |
| RFD | Refused Delivery | 3-5 days |
| UAB | Undeliverable - address issue | 3-5 days |
| DMG | Damaged - customer refused | Immediate |
| EXP | Expired hold period | 5-7 days |

### 6.2 Return Processing Steps

**Step 1: Initiation**
```
□ Create return request in system
□ Generate return tracking number
□ Notify original sender
□ Update shipment status to "RETURN_INITIATED"
```

**Step 2: Pickup/Collection**
```
□ Schedule return pickup (if at customer)
□ Collect from branch (if held)
□ Scan as "RETURN_IN_TRANSIT"
□ Load onto return manifest
```

**Step 3: Transit**
```
□ Follow standard routing
□ Scan at each hub/checkpoint
□ Update tracking in real-time
```

**Step 4: Delivery to Sender**
```
□ Contact sender for delivery
□ Obtain POD
□ Update status to "RETURNED"
□ Close original shipment
```

### 6.3 Return Charges

| Scenario | Charge to |
|----------|-----------|
| Customer requested | Customer (sender) |
| Address issue (customer error) | Customer (sender) |
| Refused delivery | Customer (sender) |
| Damaged by carrier | No charge |
| Carrier error | No charge |

---

## Appendix A: Quick Reference Cards

### Driver Quick Reference

```
EXCEPTION → TAP "EXCEPTION" → SELECT TYPE → ADD PHOTO → SUBMIT

DAMAGED PACKAGE:
1. Stop and secure package
2. Take 3+ photos
3. Report in app
4. Call branch immediately
5. Do NOT proceed with delivery

COD COLLECTION:
1. Count in front of customer
2. Issue receipt
3. Record in app
4. Secure in pouch
5. Remit at EOD
```

### Branch Manager Quick Reference

```
LOST SHIPMENT:
Day 0: Verify no scans → Search branch
Day 1: Search vehicles → Contact handlers
Day 2: Escalate if not found
Day 3: File incident report
Day 5: Process claim if lost

EXCEPTION SLA:
- Address issue: 2 hours
- Customer unavailable: 4 hours
- Damaged: Immediate
- Customs hold: 24 hours
```

---

## Appendix B: Forms & Templates

### Damage Report Form

```
DAMAGE REPORT

Tracking #: ________________
Date: ____________________
Location: _________________
Reporter: _________________

DAMAGE DESCRIPTION:
□ Crushed  □ Torn  □ Wet  □ Open
□ Contents damaged  □ Contents missing

Details: ___________________
__________________________

PHOTOS ATTACHED: □ Yes  □ No (explain)

Estimated Value: $__________

Signatures:
Reporter: _____________ Date: _____
Witness: ______________ Date: _____
Manager: _____________ Date: _____
```

---

**Document Control:**
- Approved by: Operations Director
- Review frequency: Quarterly
- Next review: February 2026
