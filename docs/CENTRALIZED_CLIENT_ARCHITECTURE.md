# Centralized Client System Architecture

## Overview

The Baraka ERP implements a **centralized client/customer management system** where customer data is system-wide but access is controlled based on user roles and branch associations.

---

## 🎯 Core Principles

### 1. **System-Wide Customer Data**
- Customers exist at the **system level**, not branch level
- Each customer has a `primary_branch_id` indicating their main branch
- Customer data is shared across the entire system
- No duplication of customer records

### 2. **Branch-Scoped Visibility**
- **Branch Users**: Can only see customers associated with their branch
- **Admin/Super Admin**: Can see ALL customers with their branch associations
- **Regional Managers**: Can see customers in their region's branches

### 3. **CRM Activity Tracking**
- All CRM activities (calls, visits, emails) track which branch recorded them
- Activities are visible based on branch context
- System maintains complete audit trail

---

## 🗄️ Database Structure

### Customers Table
```sql
primary_branch_id          -- Main branch customer belongs to
created_by_branch_id       -- Which branch created this customer
created_by_user_id         -- Which user created this customer
account_manager_id         -- Branch worker managing this account
status                     -- active, inactive, suspended, blacklisted
customer_type              -- vip, regular, prospect
```

### CRM Tables (Activities, Reminders, Addresses)
```sql
customer_id                -- Reference to customer
branch_id                  -- Branch where this record was created
user_id                    -- User who created/performed action
```

---

## 🔐 Access Control

### Branch Users
```php
// Sees only their branch's customers
Customer::query()
    ->where('primary_branch_id', $userBranchId)
    ->get();

// Or use scope
Customer::visibleToUser($user)->get();
```

### Admin Users
```php
// Sees ALL customers with branch information
Customer::query()
    ->with('primaryBranch')
    ->get();
```

### CRM Activities
```php
// Branch users see only their branch's activities
CrmActivity::query()
    ->where('branch_id', $userBranchId)
    ->get();

// Or use scope
CrmActivity::visibleToUser($user)->get();
```

---

## 📊 Data Flow Examples

### Example 1: Customer Onboarding
**Scenario**: Kampala branch creates new customer

```php
Customer::create([
    'company_name' => 'ABC Ltd',
    'primary_branch_id' => 1,        // Kampala branch
    'created_by_branch_id' => 1,     // Created by Kampala
    'created_by_user_id' => 42,      // Created by John
    'status' => 'active',
]);
```

**Result**:
- Customer appears in Kampala branch's customer list
- Admin can see customer with "Kampala" branch tag
- Other branches cannot see this customer
- Customer can create shipments through Kampala branch

### Example 2: CRM Activity Recording
**Scenario**: Sales rep at Entebbe makes customer call

```php
CrmActivity::create([
    'customer_id' => 123,
    'branch_id' => 2,                // Entebbe branch
    'user_id' => 56,                 // Sales rep Sarah
    'activity_type' => 'call',
    'subject' => 'Follow-up on quote',
    'outcome' => 'positive',
]);
```

**Result**:
- Activity visible to Entebbe branch team
- Activity associated with customer record
- Admin can see all activities for this customer across branches
- Kampala branch (if different) won't see Entebbe's activities

### Example 3: Admin View
**Scenario**: Admin views all customers

```php
// Admin dashboard shows:
Customer::query()
    ->with('primaryBranch:id,name')
    ->withCount('shipments', 'crmActivities')
    ->get()
    ->groupBy('primary_branch_id');
```

**Result**:
```
Kampala Branch (15 customers)
  - ABC Ltd (50 shipments, 12 activities)
  - XYZ Co (30 shipments, 8 activities)

Entebbe Branch (8 customers)
  - DEF Inc (20 shipments, 5 activities)
  - GHI Corp (15 shipments, 3 activities)
```

---

## 🔄 Customer Lifecycle

### Stage 1: Creation
```
Branch User creates customer
  ↓
primary_branch_id = user's branch
created_by_branch_id = user's branch
created_by_user_id = current user
  ↓
Customer visible to that branch only
```

### Stage 2: Operations
```
Branch records activities
  ↓
CRM activities tagged with branch_id
Shipments created with branch context
Invoices generated with branch context
  ↓
Activities visible per branch
```

### Stage 3: Administration
```
Admin reviews performance
  ↓
Sees all customers with branch breakdown
Can reassign customers to different branches
Can view cross-branch analytics
```

---

## 🎛️ Controller Implementation

### Branch Controller (ClientsController)
```php
public function index(Request $request)
{
    $user = $request->user();
    $branch = $this->resolveBranch($request);

    $customers = Customer::query()
        ->visibleToUser($user)  // Auto-scopes based on role
        ->with('primaryBranch')
        ->withCount('shipments', 'invoices')
        ->paginate(15);

    return view('branch.clients', compact('customers'));
}
```

### Admin Controller (Future Implementation)
```php
public function index(Request $request)
{
    $branchFilter = $request->get('branch_id');

    $customers = Customer::query()
        ->with('primaryBranch:id,name')
        ->when($branchFilter, fn($q) => $q->where('primary_branch_id', $branchFilter))
        ->withCount('shipments', 'crmActivities')
        ->paginate(50);

    $branches = Branch::all();

    return view('admin.customers', compact('customers', 'branches'));
}
```

---

## 🔍 Query Scopes

### Customer Model Scopes
```php
// Filter by branch
Customer::forBranch($branchId)->get();

// Visible to current user (auto-applies role-based filtering)
Customer::visibleToUser($user)->get();

// Active customers only
Customer::active()->get();

// VIP customers
Customer::vip()->get();

// With credit issues
Customer::creditIssues()->get();
```

### CRM Activity Scopes
```php
// Activities for specific branch
CrmActivity::forBranch($branchId)->get();

// Visible to current user
CrmActivity::visibleToUser($user)->get();

// Recent activities
CrmActivity::recent(30)->get();

// By type
CrmActivity::ofType('call')->get();
```

---

## 📈 Reporting & Analytics

### Branch-Level Reports
```php
// Branch manager sees only their branch's data
$stats = [
    'total_customers' => Customer::forBranch($branchId)->count(),
    'active_customers' => Customer::forBranch($branchId)->active()->count(),
    'vip_customers' => Customer::forBranch($branchId)->vip()->count(),
    'activities_this_month' => CrmActivity::forBranch($branchId)
        ->whereBetween('created_at', [now()->startOfMonth(), now()])
        ->count(),
];
```

### System-Wide Reports (Admin Only)
```php
// Admin sees aggregated data across all branches
$stats = Branch::all()->map(function ($branch) {
    return [
        'branch' => $branch->name,
        'customers' => Customer::forBranch($branch->id)->count(),
        'revenue' => Invoice::where('branch_id', $branch->id)
            ->where('status', 'paid')
            ->sum('total_amount'),
        'activities' => CrmActivity::forBranch($branch->id)->count(),
    ];
});
```

---

## 🚀 Benefits of This Architecture

### 1. **No Data Duplication**
- Single source of truth for customer data
- Consistent customer information across system
- Easy to update customer details system-wide

### 2. **Branch Isolation**
- Branches can't see each other's customers
- Privacy and security maintained
- Competition between branches prevented

### 3. **Central Administration**
- Admin has full visibility
- Can perform system-wide analytics
- Can reassign customers between branches
- Can monitor all activities

### 4. **Scalability**
- Easy to add new branches
- No migration of customer data needed
- Relationships maintained automatically

### 5. **Complete Audit Trail**
- Every action tracked with branch context
- Full history of customer interactions
- Clear attribution of activities

---

## 🔧 Implementation Checklist

### Models ✅
- [x] Customer model without BranchScoped trait
- [x] `visibleToUser()` scope on Customer
- [x] `forBranch()` scope on Customer
- [x] CrmActivity with branch_id
- [x] CrmReminder with branch_id
- [x] ClientAddress with branch_id
- [x] All scopes implemented
- [x] `visibleToUser()` scope on CrmActivity
- [x] `forBranch()` scope on CrmActivity
- [x] `visibleToUser()` scope on CrmReminder
- [x] `forBranch()` scope on CrmReminder
- [x] `visibleToUser()` scope on ClientAddress
- [x] `forBranch()` scope on ClientAddress

### Controllers ✅
- [x] Branch ClientsController uses `visibleToUser()`
- [x] Access control checks for updates
- [x] Branch attribution on create
- [x] Admin ClientController with branch filter

### Migrations ✅
- [x] Add `created_by_branch_id` to customers
- [x] Add `created_by_user_id` to customers
- [x] Add `branch_id` to crm_activities
- [x] Add `branch_id` to crm_reminders
- [x] Add `branch_id` to client_addresses
- [x] Add indexes for performance

### Views ✅
- [x] Branch client list shows only their customers
- [x] Admin client list shows all with branch filter
- [x] CRM activity views respect branch context

---

## 📝 Usage Examples

### Creating a Customer (Branch User)
```php
$customer = Customer::create([
    'company_name' => 'New Company Ltd',
    'primary_branch_id' => auth()->user()->branchWorker->branch_id,
    'created_by_branch_id' => auth()->user()->branchWorker->branch_id,
    'created_by_user_id' => auth()->id(),
    'status' => 'active',
    'customer_type' => 'regular',
]);
```

### Recording CRM Activity (Branch User)
```php
$activity = CrmActivity::create([
    'customer_id' => $customer->id,
    'branch_id' => auth()->user()->branchWorker->branch_id,
    'user_id' => auth()->id(),
    'activity_type' => 'call',
    'subject' => 'Product inquiry',
    'outcome' => 'positive',
]);
```

### Viewing Customers (Auto-scoped)
```php
// In controller
$customers = Customer::visibleToUser(auth()->user())
    ->with('primaryBranch')
    ->paginate(15);

// Branch user sees only their branch's customers
// Admin sees ALL customers
```

---

## 🎯 Future Enhancements

### Multi-Branch Customers
For customers who operate with multiple branches:
```php
// Add pivot table
customer_branches (customer_id, branch_id, relationship_type)

// Customer can be:
// - primary at one branch
// - secondary at other branches
// - accessible to all involved branches
```

### Regional Management
For regional managers overseeing multiple branches:
```php
// Add regional filtering
Customer::query()
    ->whereHas('primaryBranch', function ($q) use ($regionId) {
        $q->where('region_id', $regionId);
    })
    ->get();
```

### Customer Portal
Customer self-service portal:
```php
// Customers log in and see:
// - Their account across all branches
// - Shipments from any branch
// - Invoices from any branch
// - Unified view of their business
```

---

## 📚 Related Documentation

- [Branch Module Progress](../plans/branch_module_progress.md)
- [CRM System Documentation](./CRM_SYSTEM.md)
- [Security & Access Control](./SECURITY.md)
- [API Documentation](./API.md)

---

**Status**: ✅ Implemented  
**Version**: 1.0  
**Last Updated**: November 26, 2025  
**Maintainer**: Development Team
