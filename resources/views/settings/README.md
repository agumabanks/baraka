# Settings Layout System Documentation

## Overview

A standalone Blade layout system for Laravel 10+ Settings module with navigation shell, sidebar, breadcrumbs, and Toastr-ready alert areas.

## Features

✅ **Navigation Shell**: Fixed header with brand, notifications, and user authentication  
✅ **Sidebar**: Collapsible navigation with settings menu items  
✅ **Breadcrumbs**: Automatic breadcrumb generation  
✅ **Toastr Integration**: Success/error message system  
✅ **Responsive Design**: Mobile-friendly collapsible interface  
✅ **Standalone Operation**: Independent from React SPA assets  
✅ **Laravel Conventions**: Proper Blade structure with sections  

## Layout Structure

```
resources/views/settings/
├── layouts/
│   └── app.blade.php           # Main layout template
└── examples/                   # Usage examples (to be created)
```

## Usage

### Basic View Extension

```blade
@extends('settings.layouts.app')

@section('title', 'General Settings')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
    <li class="breadcrumb-item active">General</li>
@endsection

@section('page_header')
    <h1 class="page-title">General Settings</h1>
    <div class="page-actions">
        <button class="btn btn-primary">
            <i class="fas fa-save"></i> Save Changes
        </button>
    </div>
@endsection

@section('content')
    <div class="settings-card">
        <div class="settings-card-header">
            <h5 class="settings-card-title">Application Settings</h5>
        </div>
        <div class="settings-card-body">
            <form>
                <!-- Your form content here -->
            </form>
        </div>
    </div>
@endsection
```

### Route Examples

```php
// routes/web.php
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::get('/general', [SettingsController::class, 'general'])->name('general');
    Route::get('/branding', [SettingsController::class, 'branding'])->name('branding');
    Route::get('/operations', [SettingsController::class, 'operations'])->name('operations');
    Route::get('/finance', [SettingsController::class, 'finance'])->name('finance');
    Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
    Route::get('/integrations', [SettingsController::class, 'integrations'])->name('integrations');
    Route::get('/system', [SettingsController::class, 'system'])->name('system');
    Route::get('/website', [SettingsController::class, 'website'])->name('website');
});
```

### Controller Examples

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index');
    }

    public function general()
    {
        return view('settings.general');
    }

    public function branding()
    {
        return view('settings.branding');
    }
}
```

## Layout Sections

### Available Sections

| Section | Purpose | Required |
|---------|---------|----------|
| `title` | Page title | No |
| `breadcrumbs` | Custom breadcrumb navigation | No |
| `page_header` | Page title and actions | No |
| `content` | Main page content | **Yes** |
| `styles` | Additional CSS styles | No |
| `scripts` | Additional JavaScript | No |

### Breadcrumb Examples

#### Default Breadcrumb (Automatic)
```blade
@section('breadcrumb_current')
    <li class="breadcrumb-item active">Current Page</li>
@endsection
```

#### Custom Breadcrumb
```blade
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
    <li class="breadcrumb-item"><a href="{{ route('settings.general') }}">General</a></li>
    <li class="breadcrumb-item active">User Profile</li>
@endsection
```

## JavaScript Helpers

### Toastr Messages

```javascript
// Success message
showToast('Settings saved successfully!', 'success');

// Error message
showToast('Failed to save settings', 'error');

// Warning message
showToast('Please check your input', 'warning');

// Info message
showToast('New feature available', 'info');
```

### Form Handling

```javascript
// Automatic loading states for forms with 'ajax-form' class
// Add this class to forms for AJAX handling with loading states

<form class="ajax-form" method="POST" action="{{ route('settings.update') }}">
    <!-- Form content -->
</form>
```

### Confirmation Dialogs

```javascript
confirmAction('Are you sure you want to delete this item?', function() {
    // Execute the confirmed action
    window.location.href = '/delete-item';
});
```

### Loading States

```javascript
// Manually set loading state
setLoading(buttonElement, true); // Show loading
setLoading(buttonElement, false); // Hide loading
```

## Session Flash Messages

### From Controller

```php
// Success message
return redirect()->route('settings.general')->with('success', 'Settings updated successfully!');

// Error message
return back()->with('error', 'Failed to update settings');

// Warning message
return back()->with('warning', 'Please review your changes');

// Info message
return back()->with('info', 'Settings have been saved as draft');
```

### Validation Errors

```php
// Automatically displayed in the layout
return back()->withErrors([
    'email' => 'The email field is required.',
    'password' => 'Password must be at least 8 characters.',
]);
```

## CSS Classes

### Cards

```html
<!-- Settings Card -->
<div class="settings-card">
    <div class="settings-card-header">
        <h5 class="settings-card-title">Card Title</h5>
    </div>
    <div class="settings-card-body">
        <!-- Card content -->
    </div>
</div>
```

### Buttons

```html
<!-- Primary Button -->
<button class="btn btn-primary">
    <i class="fas fa-save"></i> Save Changes
</button>

<!-- Secondary Button -->
<button class="btn btn-secondary">
    <i class="fas fa-times"></i> Cancel
</button>
```

### Form Elements

```html
<!-- Form Group -->
<div class="mb-3">
    <label class="form-label">Field Label</label>
    <input type="text" class="form-control" placeholder="Enter value">
</div>
```

## Responsive Behavior

### Desktop (≥768px)
- Sidebar always visible
- Header shows brand and user info
- Content has left margin for sidebar

### Mobile (<768px)
- Sidebar collapses by default
- Hamburger menu in header
- Overlay background when sidebar is open
- Full-width content area

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Dependencies

- Bootstrap 5.3.0
- Bootstrap Icons 1.10.0
- Toastr.js (latest)
- Font Awesome 6.0.0

## Customization

### Color Scheme

CSS custom properties in `:root`:

```css
:root {
    --primary-color: #0d6efd;
    --secondary-color: #6c757d;
    --success-color: #198754;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
    --info-color: #0dcaf0;
}
```

### Sidebar Width

```css
:root {
    --sidebar-width: 280px;
}
```

### Header Height

```css
:root {
    --header-height: 60px;
}
```

## Tips

1. **Use route names** for active state highlighting in navigation
2. **Implement proper authorization** using Laravel policies
3. **Add CSRF protection** for all forms
4. **Use proper validation** with error messages
5. **Implement AJAX forms** for better UX
6. **Add loading states** for long-running operations
7. **Use proper HTTP status codes** in responses
8. **Implement rate limiting** for sensitive operations

## Troubleshooting

### Sidebar Not Showing on Mobile
- Check if Bootstrap 5.3.0 is loaded
- Verify JavaScript is enabled
- Check browser console for errors

### Toastr Messages Not Working
- Ensure Toastr.js is loaded
- Check if `showToast()` function is available
- Verify Toastr CSS is loaded

### Active Navigation Not Working
- Ensure route names match the sidebar links
- Check `request()->routeIs()` pattern matching
- Verify routes are properly defined

## Security Considerations

- Always validate user permissions
- Use CSRF tokens in forms
- Sanitize all user inputs
- Implement rate limiting
- Log important actions
- Use HTTPS in production
- Regularly update dependencies

---

**Created**: November 2025  
**Version**: 1.0.0  
**Compatibility**: Laravel 10+, PHP 8.1+