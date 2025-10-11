# Branch Management Module - Action Plan to Complete

**Current Status**: 65% Complete  
**Target**: 100% Functional  
**Estimated Time**: 8 hours

---

## Critical Path: Missing Views

### Task 1: Create Branch Manager Views (Priority: 🔴 CRITICAL)

**Time Estimate**: 2-3 hours

#### Files to Create:

1. **`resources/views/backend/branch-managers/index.blade.php`**
   - List all branch managers with filters
   - Table columns: Name, Branch, Business Name, Balance, Status, Actions
   - Pagination
   - Search and filter controls
   - Reference: Copy structure from `backend/branches/index.blade.php`

2. **`resources/views/backend/branch-managers/create.blade.php`**
   - Form to create new branch manager
   - Fields: Branch selection, User selection, Business name, COD charges
   - Validation messages
   - Reference: Copy structure from `backend/branches/create.blade.php`

3. **`resources/views/backend/branch-managers/edit.blade.php`**
   - Form to edit existing manager
   - Same fields as create
   - Pre-filled with current data
   - Reference: Copy structure from `backend/branches/edit.blade.php`

4. **`resources/views/backend/branch-managers/show.blade.php`**
   - Display manager details
   - Show: Branch info, Balance, Analytics, Settlements, Payment history
   - Action buttons: Edit, Delete, Update Balance
   - Reference: Copy structure from `backend/branches/show.blade.php`

#### Implementation Steps:

```bash
# Create directory
mkdir -p resources/views/backend/branch-managers

# Create files (use branches views as templates)
cp resources/views/backend/branches/index.blade.php \
   resources/views/backend/branch-managers/index.blade.php

cp resources/views/backend/branches/create.blade.php \
   resources/views/backend/branch-managers/create.blade.php

cp resources/views/backend/branches/edit.blade.php \
   resources/views/backend/branch-managers/edit.blade.php

cp resources/views/backend/branches/show.blade.php \
   resources/views/backend/branch-managers/show.blade.php

# Then customize each file for manager-specific fields
```

---

### Task 2: Create Branch Worker Views (Priority: 🔴 CRITICAL)

**Time Estimate**: 2-3 hours

#### Files to Create:

1. **`resources/views/backend/branch-workers/index.blade.php`**
   - List all branch workers
   - Table columns: Name, Branch, Role, Status, Assigned Date, Actions
   - Pagination and filters
   - Bulk actions

2. **`resources/views/backend/branch-workers/create.blade.php`**
   - Form to assign worker to branch
   - Fields: Branch, User, Role, Work schedule, Hourly rate
   - User availability check

3. **`resources/views/backend/branch-workers/edit.blade.php`**
   - Form to edit worker assignment
   - Update role, schedule, rate
   - Unassign option

4. **`resources/views/backend/branch-workers/show.blade.php`**
   - Worker details and analytics
   - Show: Current assignments, Performance metrics, Work logs
   - Action buttons: Edit, Unassign, Assign Shipment

#### Implementation Steps:

```bash
# Create directory
mkdir -p resources/views/backend/branch-workers

# Create files (use branches views as templates)
cp resources/views/backend/branches/index.blade.php \
   resources/views/backend/branch-workers/index.blade.php

cp resources/views/backend/branches/create.blade.php \
   resources/views/backend/branch-workers/create.blade.php

cp resources/views/backend/branches/edit.blade.php \
   resources/views/backend/branch-workers/edit.blade.php

cp resources/views/backend/branches/show.blade.php \
   resources/views/backend/branch-workers/show.blade.php

# Then customize each file for worker-specific fields
```

---

### Task 3: Create Hierarchy Visualization View (Priority: ⚠️ MEDIUM)

**Time Estimate**: 1-2 hours

#### File to Create:

**`resources/views/backend/branches/hierarchy.blade.php`**
- Interactive tree visualization
- Use jsTree or D3.js library
- Show: Branch name, type, manager, worker count
- Actions: Expand/collapse, drag to move, click to view details

#### Implementation:

```bash
# Use existing controller method that returns JSON
# Create view that fetches and displays the tree

# Libraries to consider:
# - jsTree: https://www.jstree.com/
# - Org Chart: https://github.com/dabeng/OrgChart
# - D3.js hierarchy: https://d3js.org/
```

#### Example Structure:
```php
@extends('backend.layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h3>Branch Hierarchy</h3>
    </div>
    <div class="card-body">
        <div id="branch-hierarchy-tree"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="path/to/jstree.js"></script>
<script>
    // Fetch hierarchy data and render tree
    fetch('/admin/branches/hierarchy/tree')
        .then(res => res.json())
        .then(data => {
            $('#branch-hierarchy-tree').jstree({
                'core': { 'data': data.hierarchy }
            });
        });
</script>
@endpush
```

---

### Task 4: Seed Database with Sample Data (Priority: ⚠️ MEDIUM)

**Time Estimate**: 30 minutes

#### Steps:

```bash
# Run seeders in order
php artisan db:seed --class=UnifiedBranchesSeeder
php artisan db:seed --class=BranchManagersSeeder
php artisan db:seed --class=BranchWorkersSeeder

# Verify data was created
php artisan tinker
>>> Branch::count()
>>> BranchManager::count()
>>> BranchWorker::count()
```

---

### Task 5: End-to-End Testing (Priority: ✅ FINAL)

**Time Estimate**: 1-2 hours

#### Test Checklist:

**Branch Managers**
- [ ] View list of managers at `/admin/branch-managers`
- [ ] Create new manager
- [ ] Edit existing manager
- [ ] View manager details
- [ ] Update manager balance
- [ ] Delete manager
- [ ] View manager settlements
- [ ] View manager analytics

**Branch Workers**
- [ ] View list of workers at `/admin/branch-workers`
- [ ] Assign new worker to branch
- [ ] Edit worker details
- [ ] View worker details
- [ ] Assign shipment to worker
- [ ] Unassign worker from branch
- [ ] View worker analytics
- [ ] Bulk status update

**Hierarchy**
- [ ] View hierarchy tree at `/admin/branches/hierarchy`
- [ ] Expand/collapse branches
- [ ] Move branch in hierarchy
- [ ] View branch details from tree

**Integration**
- [ ] Click Branch Management in React Dashboard → redirects to Laravel
- [ ] Click each submenu item → correct page loads
- [ ] All translations display correctly
- [ ] No 404 errors

---

## Quick Fix Commands

### Create All Missing Directories and Files

```bash
# Create directories
mkdir -p resources/views/backend/branch-managers
mkdir -p resources/views/backend/branch-workers

# Copy templates from branches
for view in index create edit show; do
    cp resources/views/backend/branches/index.blade.php \
       resources/views/backend/branch-managers/${view}.blade.php
    
    cp resources/views/backend/branches/index.blade.php \
       resources/views/backend/branch-workers/${view}.blade.php
done

echo "✅ Views created! Now customize each file for specific fields."
```

### Run Seeders

```bash
php artisan db:seed --class=UnifiedBranchesSeeder
php artisan db:seed --class=BranchManagersSeeder
php artisan db:seed --class=BranchWorkersSeeder
```

---

## Field Mappings for View Customization

### Branch Manager Fields (for views)

**Table Columns (index.blade.php)**:
- ID
- Manager Name (from user relationship)
- Business Name
- Branch Name (from branch relationship)
- Current Balance
- Status
- Actions (Edit, View, Delete)

**Form Fields (create/edit.blade.php)**:
- Branch (dropdown)
- User (dropdown - available users)
- Business Name (text)
- COD Charges (JSON array)
- Payment Info (JSON)
- Settlement Config (JSON)
- Status (dropdown: active, inactive, suspended)

**Detail View (show.blade.php)**:
- Manager Information
- Branch Details
- Current Balance
- COD Charges Configuration
- Payment Information
- Recent Shipments
- Analytics Dashboard
- Settlements History

---

### Branch Worker Fields (for views)

**Table Columns (index.blade.php)**:
- ID
- Worker Name (from user relationship)
- Branch Name (from branch relationship)
- Role
- Assigned Date
- Status
- Actions (Edit, View, Unassign)

**Form Fields (create/edit.blade.php)**:
- Branch (dropdown)
- User (dropdown - available users)
- Role (text: delivery, sorting, customer_service, etc.)
- Work Schedule (JSON: days, hours)
- Hourly Rate (decimal)
- Permissions (JSON array)
- Notes (textarea)
- Status (dropdown: active, inactive)

**Detail View (show.blade.php)**:
- Worker Information
- Branch Details
- Role and Permissions
- Work Schedule
- Assigned Shipments (current)
- Performance Metrics
- Work Logs
- Analytics

---

## Validation Rules Reference

### Branch Manager Validation

```php
// Controller already has these rules
'branch_id' => 'required|exists:branches,id',
'user_id' => 'required|exists:users,id|unique:branch_managers',
'business_name' => 'required|string|max:255',
'cod_charges' => 'nullable|array',
'payment_info' => 'nullable|array',
'status' => 'required|in:active,inactive,suspended',
```

### Branch Worker Validation

```php
// Controller already has these rules
'branch_id' => 'required|exists:branches,id',
'user_id' => 'required|exists:users,id',
'role' => 'required|string|max:50',
'work_schedule' => 'nullable|array',
'hourly_rate' => 'nullable|numeric|min:0',
'permissions' => 'nullable|array',
'status' => 'required|in:active,inactive',
```

---

## Expected Controller Variables

### BranchManagerController

**index() passes**:
- `$managers` - Paginated collection
- `$branches` - All branches for filter

**create() passes**:
- `$availableBranches` - Branches without managers

**show() passes**:
- `$manager` - Manager instance
- `$analytics` - Analytics data array

**edit() passes**:
- `$manager` - Manager instance
- `$availableBranches` - Branches for reassignment

---

### BranchWorkerController

**index() passes**:
- `$workers` - Paginated collection
- `$branches` - All branches for filter

**create() passes**:
- `$branches` - All active branches
- `$availableUsers` - Users not assigned as workers

**show() passes**:
- `$worker` - Worker instance
- `$analytics` - Analytics data array

**edit() passes**:
- `$worker` - Worker instance
- `$branches` - All active branches

---

## Success Criteria

✅ **Module is 100% Complete When:**

1. All 8 views created and customized
2. No 404 errors on any branch management route
3. All CRUD operations work for managers and workers
4. Hierarchy tree displays visually
5. Database has sample data
6. All tests pass
7. Navigation works from React Dashboard
8. Documentation updated

---

## Timeline

| Task | Time | Cumulative |
|------|------|------------|
| Branch Manager Views | 2-3h | 3h |
| Branch Worker Views | 2-3h | 6h |
| Hierarchy Visualization | 1-2h | 8h |
| Database Seeding | 0.5h | 8.5h |
| Testing & Bug Fixes | 1-2h | 10h |
| **Total** | **~10h** | **10h** |

---

## Notes

- Controllers already have all logic - just need views
- Use branches views as templates to maintain consistency
- All routes, models, and backend logic work perfectly
- Just a frontend display issue

---

**Created**: January 8, 2025  
**Status**: Ready for Implementation  
**Priority**: HIGH - Required for module to be functional
