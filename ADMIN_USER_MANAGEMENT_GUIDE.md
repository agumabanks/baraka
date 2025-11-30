# 🛡️ Admin User Management & Impersonation System

## Overview

A comprehensive admin panel for managing all users across the Baraka system with secure impersonation capabilities for support and debugging.

---

## 🎯 **Features**

### 1. **User Management Dashboard**
- ✅ View all users across all branches
- ✅ Advanced filtering (search, branch, role, status)
- ✅ User statistics and analytics
- ✅ Real-time status indicators
- ✅ One-click impersonation

### 2. **Branch Managers View**
- ✅ Dedicated view for all branch managers
- ✅ Grid layout with contact details
- ✅ Quick access to impersonate
- ✅ Filter by branch

### 3. **User Impersonation**
- ✅ Login as any user (except yourself)
- ✅ Security warnings before impersonation
- ✅ Optional reason tracking
- ✅ Complete audit logging
- ✅ Easy "Stop Impersonation" button

### 4. **Audit Logging**
- ✅ Full impersonation history
- ✅ IP address tracking
- ✅ User agent logging
- ✅ Session duration tracking
- ✅ Reason documentation

---

## 🔐 **Access Control**

### Who Can Access?
Admin user management requires one of these roles:
- `admin`
- `super-admin`
- `hq_admin`
- `support`

### Permissions
- **View all users:** All admin roles
- **Impersonate users:** All admin roles  
- **View impersonation logs:** Admin, super-admin, hq_admin only

---

## 🚀 **How to Use**

### Access the Admin Panel

```
Main Dashboard: /admin/users
Branch Managers: /admin/users/branch-managers
Impersonation Logs: /admin/users/impersonation-logs
```

### Step-by-Step: Impersonate a User

1. **Navigate to Admin Users**
   - Go to `/admin/users`
   - Use filters to find the user

2. **Click "Login As"**
   - Click the yellow "Login As" button
   - Security modal will appear

3. **Provide Reason (Optional)**
   - Enter why you're impersonating
   - Examples: "Debugging shipping issue", "Customer support request #123"

4. **Confirm Impersonation**
   - Click "Login As User"
   - You'll be logged in as that user
   - Session is fully logged

5. **Work as the User**
   - You have full access to what they see
   - All actions are performed as them
   - Red "Stop Impersonation" button appears in navbar

6. **Stop Impersonation**
   - Click "Stop Impersonation" in navbar
   - You'll be logged back as yourself
   - Session end time is logged

---

## 📊 **Dashboard Features**

### Statistics Cards

| Card | Description |
|------|-------------|
| **Total Users** | Count of all users in system |
| **Active Users** | Users with active status |
| **Branch Managers** | Count of all branch managers |
| **Branch Workers** | Count of all branch workers |
| **System Admins** | Count of admin-level users |

### User Table Columns

| Column | Information |
|--------|-------------|
| **User** | Avatar, name, and ID |
| **Branch** | Assigned branch with badge |
| **Role** | User role badges |
| **Contact** | Email and mobile number |
| **Status** | Active/Inactive badge |
| **Last Login** | Human-readable time |
| **Actions** | "Login As" button |

### Filtering Options

```
Search: Name, email, or phone number
Branch: Filter by specific branch
Role: Filter by user role
Status: Active or inactive users
```

---

## 🔍 **Branch Managers View**

### Features
- **Grid Layout:** Easy-to-scan card view
- **Manager Details:** Name, branch, contact info
- **Quick Actions:** One-click impersonation
- **Last Login:** See manager activity
- **Search:** Find managers quickly
- **Branch Filter:** View managers by branch

### Access
```
URL: /admin/users/branch-managers
```

---

## 📝 **Impersonation Logs**

### What's Logged?

Every impersonation session records:
- **Admin User:** Who initiated the impersonation
- **Target User:** Who was impersonated
- **Start Time:** When session began
- **End Time:** When session ended
- **Duration:** How long the session lasted
- **Reason:** Why the impersonation occurred
- **IP Address:** Admin's IP address
- **User Agent:** Browser/device information
- **Status:** Active or stopped

### Access Logs
```
URL: /admin/users/impersonation-logs
Required Roles: admin, super-admin, hq_admin
```

### Log Statistics
- Total sessions
- Active sessions
- Today's sessions
- This month's sessions

---

## 🛡️ **Security Features**

### 1. **Role-Based Access**
- Only admins/support staff can access
- Middleware enforces role requirements
- Unauthorized access = 403 error

### 2. **Audit Trail**
- Every impersonation logged to database
- IP address and user agent captured
- Start and end times tracked
- Optional reason field

### 3. **Prevent Nesting**
- Cannot impersonate while already impersonating
- Must stop current session first
- Prevents complex nested sessions

### 4. **Security Warnings**
- Modal warning before impersonation
- Clearly indicates action is logged
- Requires explicit confirmation

### 5. **Self-Protection**
- Cannot impersonate yourself
- "You" badge shown for your account
- Button disabled for your user

### 6. **Visual Indicators**
- Red "Stop Impersonation" button always visible
- Warning badges when impersonating
- Clear indication of who you're logged in as

---

## 🗄️ **Database Schema**

### impersonation_logs Table

```sql
CREATE TABLE impersonation_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    admin_id BIGINT NOT NULL,              -- Who performed impersonation
    impersonated_user_id BIGINT NOT NULL,  -- Who was impersonated
    reason TEXT NULL,                       -- Optional reason
    status VARCHAR(20) DEFAULT 'started',  -- started, stopped
    started_at TIMESTAMP NOT NULL,         -- When session began
    ended_at TIMESTAMP NULL,               -- When session ended
    ip VARCHAR(45) NULL,                   -- Admin's IP
    user_agent TEXT NULL,                  -- Browser info
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (admin_id) REFERENCES users(id),
    FOREIGN KEY (impersonated_user_id) REFERENCES users(id)
);
```

---

## 🔧 **Technical Implementation**

### Controllers

**UserManagementController:**
- `index()` - Display all users with filters
- `branchManagers()` - Display branch managers
- `impersonationLogs()` - Display impersonation history

**ImpersonationController:**
- `start()` - Begin impersonation session
- `stop()` - End impersonation session

### Routes

```php
// Admin User Management
Route::prefix('admin/users')->group(function () {
    Route::get('/', 'UserManagementController@index');
    Route::get('/branch-managers', 'UserManagementController@branchManagers');
    Route::get('/impersonation-logs', 'UserManagementController@impersonationLogs');
    Route::post('/{user}/impersonate', 'ImpersonationController@start');
});

// Stop Impersonation
Route::post('/admin/impersonation/stop', 'ImpersonationController@stop');
```

### Session Management

```php
// Start impersonation
session([
    'impersonator_id' => $admin->id,
    'impersonation_started_at' => now()->toDateTimeString(),
]);

// Check if impersonating
if (session('impersonator_id')) {
    // Show stop button
}

// Stop impersonation
session()->forget(['impersonator_id', 'impersonation_started_at']);
```

---

## 📋 **Common Use Cases**

### 1. **Customer Support**
**Scenario:** Customer reports they can't see their shipments  
**Solution:**
1. Find customer in admin panel
2. Click "Login As"
3. Add reason: "Support ticket #1234 - shipment visibility issue"
4. Verify issue while logged in as customer
5. Stop impersonation
6. Fix issue as admin

### 2. **Bug Debugging**
**Scenario:** Branch manager reports dashboard error  
**Solution:**
1. Go to branch managers view
2. Find the specific manager
3. Impersonate to reproduce bug
4. Document the issue
5. Stop impersonation
6. Fix and deploy

### 3. **Training & Demos**
**Scenario:** Need to show how branch interface works  
**Solution:**
1. Impersonate a branch manager account
2. Demonstrate features live
3. All actions are safe (reversible)
4. Stop when demo is complete

### 4. **Data Verification**
**Scenario:** Verify permissions are working correctly  
**Solution:**
1. Impersonate user with specific role
2. Test what they can/cannot see
3. Verify access controls
4. Stop impersonation

---

## ⚠️ **Best Practices**

### DO's ✅
- ✅ Always provide a reason when impersonating
- ✅ Stop impersonation immediately after task completion
- ✅ Document what you did while impersonating
- ✅ Review impersonation logs regularly
- ✅ Use for legitimate support/debugging only

### DON'Ts ❌
- ❌ Never leave an impersonation session active
- ❌ Don't impersonate without a valid reason
- ❌ Don't perform destructive actions while impersonating
- ❌ Don't impersonate for personal curiosity
- ❌ Don't share that you can impersonate users publicly

---

## 🐛 **Troubleshooting**

### "Already impersonating another user"
**Problem:** Trying to impersonate while already in a session  
**Solution:** Click "Stop Impersonation" button first

### "Unauthorized" or 403 Error
**Problem:** Your account doesn't have admin role  
**Solution:** Contact super-admin to assign appropriate role

### Can't see "Login As" button
**Problem:** Trying to impersonate yourself  
**Solution:** Can only impersonate other users, not yourself

### Impersonation logs empty
**Problem:** No impersonation sessions exist yet  
**Solution:** Normal - logs will populate after first impersonation

### Redirect issues after impersonation
**Problem:** Not redirected to correct dashboard  
**Solution:** System detects role and redirects appropriately:
  - Branch users → `/branch/dashboard`
  - Other users → `/dashboard`

---

## 📈 **Analytics & Reporting**

### What You Can Track

1. **Usage Metrics**
   - Total impersonation sessions
   - Sessions per admin
   - Average session duration
   - Peak usage times

2. **User Activity**
   - Most impersonated users (may indicate issues)
   - Least accessed users
   - Branch-level impersonation patterns

3. **Audit Compliance**
   - Complete audit trail
   - Who, what, when, why
   - IP and device tracking
   - Session duration analysis

### Sample Queries

```sql
-- Most active admins
SELECT admin_id, COUNT(*) as sessions
FROM impersonation_logs
GROUP BY admin_id
ORDER BY sessions DESC;

-- Average session duration
SELECT AVG(TIMESTAMPDIFF(MINUTE, started_at, ended_at)) as avg_minutes
FROM impersonation_logs
WHERE ended_at IS NOT NULL;

-- Sessions today
SELECT COUNT(*) FROM impersonation_logs
WHERE DATE(started_at) = CURDATE();
```

---

## 🔄 **Migration Instructions**

### Run Migration

```bash
php artisan migrate --path=database/migrations/2025_11_26_220000_create_impersonation_logs_table.php
```

### Clear Caches

```bash
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

---

## 🎓 **Training Guide**

### For Admins

1. **First Login to Admin Panel**
   - Navigate to `/admin/users`
   - Familiarize with the interface
   - Explore filters and search

2. **Practice Impersonation**
   - Use a test account first
   - Add meaningful reasons
   - Stop immediately after

3. **Review Logs**
   - Check `/admin/users/impersonation-logs`
   - Understand what's being tracked
   - Note your own sessions

4. **Learn Branch Manager View**
   - Visit `/admin/users/branch-managers`
   - Understand the grid layout
   - Practice filtering

### For Super Admins

1. **Assign Admin Roles**
   - Grant appropriate roles to support staff
   - Consider creating `support` role for limited access
   - Document who has admin access

2. **Monitor Usage**
   - Regularly review impersonation logs
   - Look for unusual patterns
   - Ensure reasons are documented

3. **Set Policies**
   - Define when impersonation is appropriate
   - Require documentation of actions taken
   - Set maximum session durations

---

## 📞 **Support**

### Questions?
- **Technical Issues:** Contact development team
- **Access Issues:** Contact super-admin
- **Feature Requests:** Submit to product team

### Related Documentation
- System Architecture: `/docs/CENTRALIZED_CLIENT_ARCHITECTURE.md`
- Deployment Guide: `/DEPLOYMENT_GUIDE.md`
- Branch Module: `/plans/branch_module_progress.md`

---

## ✅ **Checklist for Admins**

Before going live, ensure:

- [ ] Migration run successfully
- [ ] At least one admin account created
- [ ] Tested impersonation with test user
- [ ] Reviewed impersonation logs
- [ ] Documented policies for team
- [ ] Trained support staff
- [ ] Set up monitoring for unusual activity
- [ ] Configured alerts for extended sessions

---

**Created:** November 26, 2025  
**Version:** 1.0.0  
**Status:** Production Ready  
**Maintainer:** Development Team  

🎊 **Your admin user management system is ready for production!** 🚀
