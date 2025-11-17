# Branch Module Complete Access Guide
**Baraka Logistics Management System**

---

## Table of Contents
1. [Overview](#overview)
2. [User Roles & Permissions](#user-roles--permissions)
3. [Admin Access & Capabilities](#admin-access--capabilities)
4. [Branch Manager Access & Capabilities](#branch-manager-access--capabilities)
5. [Branch Worker Access & Capabilities](#branch-worker-access--capabilities)
6. [Shipment Lifecycle Workflow](#shipment-lifecycle-workflow)
7. [Booking & Creation Process](#booking--creation-process)
8. [Scanning System Guide](#scanning-system-guide)
9. [Status Updates & Tracking](#status-updates--tracking)
10. [Delivery Process](#delivery-process)
11. [API Reference](#api-reference)
12. [Troubleshooting](#troubleshooting)

---

## Overview

The Branch Module is the core operational system that manages:
- Branch hierarchy (HUB → REGIONAL → LOCAL)
- Shipment creation and tracking
- Worker assignments and roles
- Scanning and status updates
- End-to-end delivery workflow

### Branch Types
- **HUB**: Central sorting and distribution center
- **REGIONAL**: Regional distribution centers
- **LOCAL**: Local delivery branches

---

## User Roles & Permissions

### 1. System Administrator
**Access Level**: Full System Access
- Manage all branches across the network
- Create/edit/delete branches
- Assign branch managers
- View all shipments system-wide
- Access all reports and analytics
- Configure system settings

### 2. Branch Manager
**Access Level**: Branch-Specific Management
- Manage their assigned branch only
- Assign and supervise branch workers
- Create and manage shipments for their branch
- View branch performance metrics
- Handle branch-specific customer inquiries
- Manage branch operations

### 3. Branch Workers (Multiple Roles)

#### Operations Supervisor
- Oversee daily branch operations
- Assign tasks to workers
- Monitor workflow efficiency
- Handle escalations

#### Operations Agent
- Process shipments
- Update shipment statuses
- Handle customer walk-ins
- General operations support

#### Sortation Agent
- Scan and sort incoming shipments
- Create bags for linehaul
- Organize warehouse operations
- Inventory management

#### Courier/Driver
- Scan shipments for pickup
- Scan shipments for delivery
- Update delivery status
- Collect proof of delivery

#### Customer Support
- Handle customer inquiries
- Track shipments for customers
- Resolve delivery issues
- Process returns

#### Dispatcher
- Assign routes to couriers
- Monitor delivery schedules
- Optimize routing
- Track courier performance

#### Finance Officer
- Handle COD collections
- Process branch payments
- Generate financial reports
- Reconcile accounts

#### Security
- Monitor branch premises
- Verify shipment integrity
- Access control
- Incident reporting

---

## Admin Access & Capabilities

### Dashboard Access
**URL**: `/admin/dashboard` or `/dashboard`

### Branch Management

#### View All Branches
```
Navigation: Dashboard → Branches
URL: /admin/branches
```

**Features:**
- View branch hierarchy tree
- Filter by type (HUB, REGIONAL, LOCAL)
- Search by name, code, or location
- View branch status (Active/Inactive)
- See capacity utilization

#### Create New Branch
```
Navigation: Branches → Create Branch
URL: /admin/branches/create
```

**Required Information:**
- Branch Name
- Branch Code (unique identifier)
- Branch Type (HUB/REGIONAL/LOCAL)
- Parent Branch (for hierarchy)
- Address & Contact Details
- Operating Hours
- Capabilities (sorting, customs, storage, etc.)
- Capacity (parcels per day)

**API Endpoint:**
```
POST /api/v10/branches
```

#### Edit Branch
```
URL: /admin/branches/{id}/edit
```

**Editable Fields:**
- All branch details
- Status (Active/Inactive)
- Operating hours
- Capabilities
- Capacity limits

#### View Branch Details
```
URL: /admin/branches/{id}
```

**Displays:**
- Branch information
- Assigned manager
- Active workers
- Recent shipments (origin and destination)
- Capacity metrics
- Performance analytics
- Hierarchy position

### Branch Worker Management

#### View All Workers
```
Navigation: Dashboard → Branch Workers
URL: /admin/branch-workers
```

#### Create Branch Worker
```
URL: /admin/branch-workers/create
```

**Required Information:**
- User Account (email)
- Branch Assignment
- Role Selection (from 10 available roles)
- Employment Status (Full-time, Part-time, Contract)
- Contact Information
- Work Schedule
- Permissions

**API Endpoint:**
```
POST /api/admin/branch-workers
```

#### Assign Worker to Branch
Workers can be:
- Assigned to a branch
- Reassigned to different branch
- Unassigned (but retained in system)
- Given multiple role permissions

### Shipment Management

#### View All Shipments
```
Navigation: Dashboard → Shipments
URL: /admin/shipments
```

**Filters:**
- By origin branch
- By destination branch
- By status
- By date range
- By customer

#### View Shipments by Branch
```
URL: /admin/branches/shipments?branch_id={id}
```

### Analytics & Reports

#### Branch Performance Analytics
```
URL: /api/v10/analytics/operational/metrics
```

**Metrics Available:**
- Shipments processed
- On-time delivery rate
- Worker utilization
- Capacity usage
- Revenue by branch
- Exception rates

#### Branch Capacity Planning
```
URL: /api/backend/branches/{id}/capacity
```

**Shows:**
- Current capacity utilization
- Active workers count
- Current shipment load
- Recommendations for optimization

---

## Branch Manager Access & Capabilities

### Dashboard Access
**URL**: `/branch` or `/branch/portal`

### Branch Portal Overview
```
API Endpoint: GET /api/v10/branch-portal/overview
```

**Displays:**
- Branch information
- Active shipments count
- Delivered today count
- Pending pickups count
- Recent shipments list
- Quick action links

### Worker Management

#### View Branch Workers
Branch managers can:
- View all workers assigned to their branch
- See worker roles and schedules
- Monitor worker performance
- Request worker assignments (approval required)

#### Assign Shipments to Workers
```
URL: /api/admin/branch-workers/{id}/assign-shipment
```

Branch managers can assign specific shipments to couriers for pickup or delivery.

### Shipment Operations

#### Create Shipment (Booking Wizard)
```
URL: /admin/booking
Navigation: Branch Portal → Create Shipment
```

**Step-by-Step Process:**

**Step 1: Customer Information**
- Select existing customer OR create new
- Customer name, email, phone
- Pickup address
- Delivery address

**Step 2: Shipment Details**
- Origin branch (auto-filled with manager's branch)
- Destination branch
- Service level (Standard, Express, Overnight)
- Package details (weight, dimensions)
- Package description
- Special instructions

**Step 3: Pricing**
- Calculate delivery charges
- Add COD amount (if applicable)
- Apply surcharges
- Confirm total

**Step 4: Confirmation**
- Review all details
- Generate tracking number
- Generate SSCC barcode
- Print shipping label
- Provide tracking info to customer

#### Track Shipments
Branch managers can track:
- All shipments originating from their branch
- All shipments destined to their branch
- Shipments currently at their branch
- Shipments assigned to their workers

### Performance Monitoring
Branch managers have access to:
- Daily shipment volume
- Delivery success rate
- Worker productivity
- Customer satisfaction
- Exception reports

---

## Branch Worker Access & Capabilities

### Mobile/Handheld Access
Workers primarily use:
- Mobile app for scanning
- Handheld scanners
- Web portal for updates

### Common Operations

#### 1. Clock In/Out
Workers log their work hours:
```
POST /api/v10/driver-time-logs
```

#### 2. View Assigned Tasks
```
GET /api/v10/workflow-items?assigned_to={worker_id}
```

#### 3. Scan Shipments
```
POST /api/v10/scan-events
```

### Role-Specific Operations

#### Courier/Driver Operations

**Pickup Workflow:**
1. View assigned pickups
2. Navigate to pickup location
3. Scan shipment barcode (PICKUP_COMPLETED)
4. Confirm pickup with customer
5. Update status
6. Return to branch

**Delivery Workflow:**
1. View assigned deliveries
2. Scan shipment (OUT_FOR_DELIVERY)
3. Navigate to delivery address
4. Scan shipment (DELIVERY_CONFIRMED)
5. Collect proof of delivery
6. Collect COD (if applicable)
7. Complete delivery

#### Sortation Agent Operations

**Sorting Workflow:**
1. Scan incoming shipments (ORIGIN_ARRIVAL)
2. Verify destination
3. Sort by destination branch
4. Create bags for linehaul
5. Scan shipments into bags (BAGGED)
6. Seal and label bags
7. Stage for loading

**Warehouse Management:**
1. Organize shipments by zone
2. Maintain inventory
3. Handle exceptions
4. Process returns

#### Operations Agent Operations

**Front Desk:**
1. Receive walk-in customers
2. Create shipments using booking wizard
3. Accept shipments for sending
4. Provide tracking information
5. Handle inquiries

**Shipment Processing:**
1. Scan received shipments
2. Update statuses
3. Generate reports
4. Handle exceptions

---

## Shipment Lifecycle Workflow

### Complete Status Flow

```
BOOKED (Created)
    ↓
PICKUP_SCHEDULED (Assigned to courier)
    ↓
PICKED_UP (Courier scanned at pickup)
    ↓
AT_ORIGIN_HUB (Arrived at origin branch)
    ↓
BAGGED (Sorted and bagged for transport)
    ↓
LINEHAUL_DEPARTED (Left origin for destination)
    ↓
LINEHAUL_ARRIVED (Arrived at destination hub)
    ↓
AT_DESTINATION_HUB (Received at destination)
    ↓
CUSTOMS_HOLD (If international, customs processing)
    ↓
CUSTOMS_CLEARED (Cleared customs)
    ↓
OUT_FOR_DELIVERY (Assigned to courier)
    ↓
DELIVERED (Successfully delivered)
```

### Return Flow
```
DELIVERED
    ↓
RETURN_INITIATED (Customer requests return)
    ↓
RETURN_IN_TRANSIT (Being returned to sender)
    ↓
RETURNED (Returned to sender)
```

### Exception Flow
```
ANY STATUS
    ↓
EXCEPTION (Problem occurred)
    ↓
(Resolution) → Resume normal flow
```

---

## Booking & Creation Process

### Method 1: Client Portal (Customer Self-Service)
**URL**: `/client/login` → `/client/create-shipment`

**Process:**
1. Client logs in
2. Selects origin and destination branches
3. Enters sender/receiver information
4. Provides package details
5. Reviews and confirms
6. Receives tracking number

**API Endpoint:**
```
POST /api/v10/customer/shipments
```

**Shipment is automatically:**
- Assigned to origin branch
- Visible to branch manager
- Status: BOOKED
- Awaiting pickup assignment

### Method 2: Front Desk Booking (Branch Staff)
**URL**: `/admin/booking`

**Process:**
1. Staff opens booking wizard
2. Enters or selects customer
3. Fills shipment details
4. Calculates pricing
5. Generates label with barcode
6. Prints label
7. Accepts package

**Result:**
- Shipment created
- SSCC barcode generated
- Tracking number assigned
- Status: BOOKED or PICKED_UP (if accepting package immediately)

### Method 3: API Integration (Merchant Integration)
**Endpoint**: `POST /api/v1/shipments`

Merchants with API access can create shipments programmatically.

---

## Scanning System Guide

### Barcode Format
The system uses **SSCC (Serial Shipping Container Code)** format:
- 18-digit unique identifier
- Encoded as Code 128 barcode
- Also available as QR code

### Scan Types & Purposes

#### 1. BOOKING_CONFIRMED
**When**: At shipment creation
**Who**: Front desk staff
**Result**: Shipment enters system (Status: BOOKED)

#### 2. PICKUP_CONFIRMED
**When**: Courier assigned for pickup
**Who**: Dispatcher/Operations
**Result**: Status → PICKUP_SCHEDULED

#### 3. PICKUP_COMPLETED
**When**: Courier collects package
**Who**: Courier (mobile scan)
**Result**: Status → PICKED_UP
**Location**: Customer's location

#### 4. ORIGIN_ARRIVAL
**When**: Shipment arrives at origin hub
**Who**: Receiving clerk/Sortation agent
**Result**: Status → AT_ORIGIN_HUB
**Location**: Origin branch warehouse

#### 5. BAGGED
**When**: Shipment sorted and bagged
**Who**: Sortation agent
**Result**: Status → BAGGED
**Additional**: Bag ID associated with shipment

#### 6. LINEHAUL_DEPARTED
**When**: Loaded on vehicle for transport
**Who**: Dispatcher/Driver
**Result**: Status → LINEHAUL_DEPARTED
**Additional**: Route ID, Vehicle ID

#### 7. LINEHAUL_ARRIVED
**When**: Vehicle arrives at destination hub
**Who**: Receiving clerk
**Result**: Status → LINEHAUL_ARRIVED

#### 8. DESTINATION_ARRIVAL
**When**: Unloaded at destination hub
**Who**: Sortation agent
**Result**: Status → AT_DESTINATION_HUB

#### 9. OUT_FOR_DELIVERY
**When**: Assigned to courier for delivery
**Who**: Courier (mobile scan)
**Result**: Status → OUT_FOR_DELIVERY
**Location**: Destination branch

#### 10. DELIVERY_CONFIRMED
**When**: Successfully delivered to recipient
**Who**: Courier (mobile scan)
**Result**: Status → DELIVERED
**Additional**: POD (Proof of Delivery), Signature, Photo

#### 11. CUSTOMS_HOLD
**When**: Held for customs clearance
**Who**: Customs officer/Operations
**Result**: Status → CUSTOMS_HOLD

#### 12. CUSTOMS_CLEARED
**When**: Released from customs
**Who**: Customs officer/Operations
**Result**: Status → CUSTOMS_CLEARED

#### 13. RETURN_INITIATED
**When**: Return requested
**Who**: Customer support/Courier
**Result**: Status → RETURN_INITIATED

#### 14. RETURN_RECEIVED
**When**: Return received at hub
**Who**: Sortation agent
**Result**: Status → RETURN_IN_TRANSIT

#### 15. RETURN_COMPLETED
**When**: Returned to sender
**Who**: Courier
**Result**: Status → RETURNED

#### 16. EXCEPTION
**When**: Problem occurs (damage, lost, etc.)
**Who**: Any authorized staff
**Result**: Status → EXCEPTION
**Required**: Exception reason and note

### Scanning API

#### Record Scan Event
```
POST /api/v10/scan-events
Authorization: Bearer {token}

{
  "sscc": "123456789012345678",
  "type": "PICKUP_COMPLETED",
  "branch_id": 123,
  "user_id": 456,
  "occurred_at": "2025-11-07T10:30:00Z",
  "location_type": "customer_address",
  "location_id": 789,
  "geojson": {
    "type": "Point",
    "coordinates": [-1.9403, 29.8739]
  },
  "note": "Package received in good condition"
}
```

#### Response
```json
{
  "data": {
    "id": 12345,
    "sscc": "123456789012345678",
    "shipment_id": 9876,
    "type": "PICKUP_COMPLETED",
    "status_after": "PICKED_UP",
    "branch_id": 123,
    "user_id": 456,
    "occurred_at": "2025-11-07T10:30:00Z",
    "created_at": "2025-11-07T10:30:05Z"
  }
}
```

### Mobile Scanning

#### Using Mobile App
1. Open scanning module
2. Point camera at barcode/QR code
3. Auto-detect and scan
4. Select scan type
5. Add optional note
6. Capture location (GPS)
7. Submit scan
8. Receive confirmation

#### Using Handheld Scanner
1. Scan barcode with device
2. Device sends SSCC to system
3. User selects scan type on screen
4. Confirm scan
5. System updates status automatically

---

## Status Updates & Tracking

### Manual Status Update
**URL**: `/admin/shipments/{id}/edit`

Authorized users can manually update status:
1. Select new status
2. Add reason/note
3. Confirm update
4. System records update in history

### Automatic Status Updates
Status automatically updates when:
- Scan event is recorded
- Workflow action is triggered
- Time-based rule is met (e.g., delivery deadline)
- Exception is flagged

### Tracking for Customers

#### Public Tracking Page
**URL**: `/track?tracking_number={number}`

Customers can track without logging in by entering:
- Tracking number
- AWB number
- Reference number

#### Customer Portal Tracking
**URL**: `/client/dashboard`

Logged-in customers see:
- All their shipments
- Real-time status
- Estimated delivery date
- Delivery history
- Exception notifications

### Tracking API
```
GET /api/v1/tracking?tracking_number={number}
```

**Response:**
```json
{
  "success": true,
  "shipment": {
    "tracking_number": "BRK123456789",
    "current_status": "OUT_FOR_DELIVERY",
    "origin": "Riyadh Hub",
    "destination": "Kigali Branch",
    "estimated_delivery": "2025-11-08",
    "events": [
      {
        "status": "BOOKED",
        "timestamp": "2025-11-06T09:00:00Z",
        "location": "Riyadh Hub"
      },
      {
        "status": "PICKED_UP",
        "timestamp": "2025-11-06T14:30:00Z",
        "location": "Customer Address"
      },
      {
        "status": "AT_ORIGIN_HUB",
        "timestamp": "2025-11-06T16:00:00Z",
        "location": "Riyadh Hub"
      },
      {
        "status": "OUT_FOR_DELIVERY",
        "timestamp": "2025-11-07T08:00:00Z",
        "location": "Kigali Branch"
      }
    ]
  }
}
```

---

## Delivery Process

### Standard Delivery Flow

#### Step 1: Preparation
**Location**: Destination hub
**Actor**: Sortation Agent

1. Scan shipment (DESTINATION_ARRIVAL)
2. Sort by delivery route
3. Assign to courier
4. Load on delivery vehicle

#### Step 2: Dispatch
**Actor**: Dispatcher

1. Create delivery route
2. Assign shipments to courier
3. Optimize route order
4. Provide route manifest to courier

#### Step 3: Out for Delivery
**Actor**: Courier

1. Scan all shipments (OUT_FOR_DELIVERY)
2. Load verification
3. Begin route
4. Navigate to delivery addresses

#### Step 4: Delivery Attempt
**Actor**: Courier
**Location**: Delivery address

1. Arrive at address
2. Contact recipient
3. Scan shipment (DELIVERY_CONFIRMED)
4. Collect proof of delivery:
   - Recipient signature
   - Photo of package
   - Photo of recipient (if required)
   - ID verification (if required)
5. Collect COD payment (if applicable)
6. Complete delivery

#### Step 5: Completion
1. Return to branch
2. Submit delivery proof
3. Remit COD collections
4. Complete route

### Failed Delivery Scenarios

#### Recipient Not Available
1. Attempt contact via phone
2. Leave notification card
3. Update status to DELIVERY_RE_SCHEDULE
4. Schedule next attempt
5. Notify customer via SMS/email

#### Address Issues
1. Contact customer for clarification
2. Attempt to locate correct address
3. If unresolved:
   - Update status to EXCEPTION
   - Document issue
   - Escalate to customer support

#### Refused Delivery
1. Document refusal reason
2. Collect signature of refusal
3. Update status to RETURN_INITIATED
4. Return to hub for reverse logistics

### COD Handling

#### Collection
1. Calculate COD amount
2. Collect from recipient
3. Provide receipt
4. Record in app

#### Remittance
1. Return to branch
2. Count and verify collections
3. Submit to finance officer
4. Receive confirmation

#### Reconciliation
1. Finance officer verifies amounts
2. Matches with delivery records
3. Updates accounts
4. Remits to merchants per schedule

---

## API Reference

### Authentication
All API calls require authentication:
```
Authorization: Bearer {access_token}
```

### Branch Portal API

#### Get Branch Overview
```
GET /api/v10/branch-portal/overview
```

#### Get Branch Shipments
```
GET /api/v10/customer/shipments
```

### Branch Management API

#### List Branches
```
GET /api/v10/branches
```

#### Get Branch Details
```
GET /api/v10/branches/{id}
```

#### Create Branch
```
POST /api/v10/branches
```

#### Update Branch
```
PUT /api/v10/branches/{id}
```

### Shipment API

#### Create Shipment (Client)
```
POST /api/v10/customer/shipments
```

#### Create Shipment (Admin)
```
POST /api/v10/shipments
```

#### Get Shipment
```
GET /api/v10/shipments/{id}
```

#### Update Shipment Status
```
PATCH /api/v10/shipments/{id}/status
```

### Scanning API

#### Record Scan Event
```
POST /api/v10/scan-events
```

### Worker API

#### Get Worker Tasks
```
GET /api/v10/workflow-items?assigned_to={worker_id}
```

#### Update Task Status
```
PATCH /api/v10/workflow-items/{id}/status
```

#### Log Work Time
```
POST /api/v10/driver-time-logs
```

---

## Troubleshooting

### Common Issues

#### 1. Cannot Create Shipment
**Symptoms**: Form validation errors, submission fails

**Solutions:**
- Verify all required fields are filled
- Check branch is active
- Ensure origin and destination are different
- Verify user has permission to create shipments
- Check customer information is valid

#### 2. Scan Not Working
**Symptoms**: Barcode won't scan, wrong status update

**Solutions:**
- Clean barcode/QR code
- Ensure good lighting
- Check scanner battery
- Verify network connection
- Confirm user has scan permission for that branch
- Check if shipment already in terminal status

#### 3. Status Not Updating
**Symptoms**: Scan recorded but status unchanged

**Solutions:**
- Verify scan type is appropriate for current status
- Check if shipment is in correct branch
- Ensure scan event was recorded (check scan history)
- Verify no pending exceptions blocking updates
- Contact system administrator

#### 4. Cannot Assign Worker
**Symptoms**: Worker assignment fails

**Solutions:**
- Verify worker is active
- Check worker is assigned to correct branch
- Ensure worker has appropriate role
- Verify worker capacity not exceeded
- Check worker availability schedule

#### 5. Missing Shipment in Branch Portal
**Symptoms**: Shipment not visible to branch staff

**Solutions:**
- Verify shipment origin or destination matches branch
- Check shipment status is not in terminal state
- Ensure user has correct branch assignment
- Refresh portal data
- Check network connection

#### 6. Delivery Proof Not Uploading
**Symptoms**: Photo/signature upload fails

**Solutions:**
- Check internet connection
- Verify image size is under limit (5MB)
- Ensure camera permissions enabled
- Try alternative format (photo vs signature)
- Save draft and retry later

### Contact Support

For unresolved issues:
- **Email**: support@baraka.sanaa.co
- **Phone**: Branch Manager → Operations Center
- **In-App**: Use help/support feature
- **Escalation**: Create ticket in admin panel

---

## Quick Reference Card

### Scan Types Quick Reference
| Scan Type | Status After | Who Scans | When |
|-----------|--------------|-----------|------|
| BOOKING_CONFIRMED | BOOKED | Front Desk | Creation |
| PICKUP_COMPLETED | PICKED_UP | Courier | At pickup |
| ORIGIN_ARRIVAL | AT_ORIGIN_HUB | Warehouse | Hub arrival |
| BAGGED | BAGGED | Sorter | After sorting |
| LINEHAUL_DEPARTED | LINEHAUL_DEPARTED | Driver | Vehicle departs |
| DESTINATION_ARRIVAL | AT_DESTINATION_HUB | Warehouse | Hub arrival |
| OUT_FOR_DELIVERY | OUT_FOR_DELIVERY | Courier | Start delivery |
| DELIVERY_CONFIRMED | DELIVERED | Courier | At delivery |

### Emergency Contacts
- **Operations Center**: +966-XXX-XXXX
- **IT Support**: +966-XXX-XXXX
- **Customer Service**: +966-XXX-XXXX

### Important URLs
- **Admin Dashboard**: `/admin/dashboard`
- **Branch Portal**: `/branch`
- **Client Portal**: `/client/login`
- **Booking Wizard**: `/admin/booking`
- **Track Shipment**: `/track`

---

## Appendices

### Appendix A: Branch Codes
- **HUB-RYD-001**: Riyadh Central Hub (Saudi Arabia)
- **HUB-IST-001**: Istanbul International Hub (Turkey)
- **REG-JED-001**: Jeddah Regional Center (Saudi Arabia)
- **REG-DMM-001**: Dammam Regional Center (Saudi Arabia)
- **REG-KIN-001**: Kinshasa Regional Center (DRC)
- **REG-KGL-001**: Rwanda Regional Center (Rwanda)
- **LOC-GOM-001**: Goma Local Branch (DRC)

### Appendix B: Worker Role Permissions Matrix

| Role | Create Shipment | Scan | Assign | View Reports | Manage Workers |
|------|----------------|------|--------|--------------|----------------|
| Branch Manager | ✓ | ✓ | ✓ | ✓ | ✓ |
| Ops Supervisor | ✓ | ✓ | ✓ | ✓ | ✗ |
| Ops Agent | ✓ | ✓ | ✗ | Limited | ✗ |
| Sortation Agent | ✗ | ✓ | ✗ | ✗ | ✗ |
| Courier | ✗ | ✓ | ✗ | ✗ | ✗ |
| Dispatcher | ✗ | ✓ | ✓ | Limited | ✗ |
| Customer Support | ✓ | ✗ | ✗ | Limited | ✗ |
| Finance Officer | ✗ | ✗ | ✗ | ✓ | ✗ |

### Appendix C: Status Transition Rules

Valid transitions from each status ensure data integrity:

**FROM BOOKED:**
- → PICKUP_SCHEDULED
- → CANCELLED

**FROM PICKUP_SCHEDULED:**
- → PICKED_UP
- → PICKUP_RE_SCHEDULE
- → CANCELLED

**FROM PICKED_UP:**
- → AT_ORIGIN_HUB
- → EXCEPTION

**FROM AT_ORIGIN_HUB:**
- → BAGGED
- → EXCEPTION

**FROM BAGGED:**
- → LINEHAUL_DEPARTED
- → EXCEPTION

And so on...

---

**Document Version**: 1.0  
**Last Updated**: November 7, 2025  
**Maintained By**: Baraka Logistics IT Team
