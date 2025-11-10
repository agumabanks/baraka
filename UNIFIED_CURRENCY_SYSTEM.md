# Unified Currency System Implementation

## Overview
The system now has a unified currency configuration set from system preferences in the General Settings. This ensures consistency across the entire application and provides a single source of truth for currency settings.

## Changes Made

### 1. Database Schema
- **Added `currency_id` column** to `general_settings` table as a foreign key to the `currencies` table
- **Kept `currency` symbol field** for backward compatibility
- **Auto-populated** existing currency_id based on currency symbols during migration

### 2. Model Updates
**GeneralSettings Model** (`app/Models/Backend/GeneralSettings.php`):
- Added `systemCurrencyRelation()` - relationship to Currency model
- Added `getSystemCurrencyAttribute()` - accessor for getting full currency details
- Added `getCurrencySymbolAttribute()` - accessor for currency symbol
- Added `getCurrencyCodeAttribute()` - accessor for currency code
- Added `getCurrencyNameAttribute()` - accessor for currency name

### 3. Helper Functions
**New Helper Functions** (`app/Http/Helper/Helper.php`):
- `system_currency()` - Returns the full Currency model object
- `currency_symbol()` - Returns currency symbol (e.g., '$', '€', 'UGX')
- `currency_code()` - Returns currency code (e.g., 'USD', 'EUR', 'UGX')
- `currency_name()` - Returns currency name (e.g., 'Dollars', 'Euro', 'Uganda Shilling')

### 4. Repository Updates
**GeneralSettingsRepository** (`app/Repositories/GeneralSettings/GeneralSettingsRepository.php`):
- Updated to handle `currency_id` when creating/updating settings
- Ensures currency_id and currency symbol are synchronized
- Auto-loads currency relationship for better performance

### 5. View Updates
**General Settings Page** (`resources/views/backend/general_settings/index.blade.php`):
- Updated currency dropdown to use `currency_id` instead of `currency`
- Shows full currency details: Name (Symbol) - Code
- Better user experience with more informative currency selection

## Usage

### Getting Currency Information

```php
// Get full currency object
$currency = system_currency();
echo $currency->name;   // "Dollars"
echo $currency->symbol; // "$"
echo $currency->code;   // "USD"
echo $currency->exchange_rate; // 1.00

// Get specific currency attributes (recommended)
$symbol = currency_symbol(); // "$"
$code = currency_code();     // "USD"
$name = currency_name();     // "Dollars"

// Legacy method (still works)
$symbol = settings()->currency; // "$"
```

### In Blade Templates

```blade
<!-- Using new helpers -->
<p>Price: {{ currency_symbol() }}{{ number_format($amount, 2) }}</p>
<p>Currency: {{ currency_name() }} ({{ currency_code() }})</p>

<!-- Using system_currency() for full details -->
@php
    $currency = system_currency();
@endphp
<p>{{ $currency->name }}: {{ $currency->symbol }}{{ number_format($amount, 2) }}</p>

<!-- Legacy method (still works) -->
<p>{{ settings()->currency }}{{ number_format($amount, 2) }}</p>
```

### Updating Currency in Settings

When updating general settings, you can now pass `currency_id`:

```php
// Using currency_id (recommended)
$request->merge(['currency_id' => $selectedCurrencyId]);

// Using currency symbol (legacy, still works)
$request->merge(['currency' => '$']);
```

## Benefits

1. **Single Source of Truth**: Currency is managed from one location in system preferences
2. **Data Integrity**: Foreign key constraint ensures currency_id always references valid currency
3. **Full Currency Details**: Access to exchange rates, codes, and names from the currencies table
4. **Backward Compatible**: Existing code using `settings()->currency` continues to work
5. **Better Performance**: Eager loading of currency relationship reduces database queries
6. **Consistency**: All parts of the application use the same currency configuration

## Migration Details

**Migration File**: `database/migrations/2025_11_07_122724_add_currency_id_to_general_settings_table.php`

The migration:
1. Adds `currency_id` column as nullable foreign key
2. Populates `currency_id` based on existing `currency` symbols
3. Adds index for better query performance
4. Can be rolled back if needed

## Testing

Verify the currency system is working:

```bash
# Clear cache
php artisan cache:clear
php artisan config:clear

# Test in tinker
php artisan tinker
>>> currency_symbol()
>>> currency_code()
>>> currency_name()
>>> system_currency()
```

Expected output:
```
currency_symbol() => "$"
currency_code() => "USD"
currency_name() => "Dollars"
system_currency() => App\Models\Backend\Currency {#...}
```

## Notes

- The `currency` column is kept for backward compatibility
- When updating settings, both `currency` and `currency_id` are synchronized
- Default currency is UGX (Uganda Shilling) if no currency is set
- All 141 currencies from the `currencies` table are available for selection

## Frontend Integration (React Dashboard)

### React Context
The React dashboard now uses a `SettingsProvider` that fetches and manages system settings including currency:

**Location**: `react-dashboard/src/contexts/SettingsContext.tsx`

**Features**:
- Auto-fetches settings on app load
- Caches settings in React context
- Provides helper functions for currency formatting
- Falls back to USD if API fails

### Usage in React Components

```tsx
import { useSettings } from '../contexts/SettingsContext';

function MyComponent() {
  const { formatCurrency, getCurrencySymbol, getCurrencyCode, settings } = useSettings();

  return (
    <div>
      <p>Total: {formatCurrency(1500.50)}</p>
      <p>Currency: {getCurrencySymbol()} ({getCurrencyCode()})</p>
      <p>System: {settings?.name}</p>
    </div>
  );
}
```

### Updated Components
The following components now use the unified currency system:
- `pages/MerchantDetail.tsx`
- `pages/Merchants.tsx`
- `pages/BranchDetail.tsx`
- `pages/MerchantPayments.tsx`
- `pages/settings/UsersManagement.tsx`
- `components/shipments/CreateShipmentModal.tsx`

### API Integration
The React app fetches currency from:
- **Endpoint**: `GET /api/v10/general-settings`
- **Response includes**: `currency_symbol`, `currency_code`, `currency_name`, `system_currency` object

## Testing the Full Stack

1. **Backend Test**:
```bash
php artisan tinker
>>> currency_symbol()  // Should return your currency symbol
>>> currency_code()    // Should return your currency code
```

2. **Frontend Test**:
- Login to the dashboard
- Navigate to Merchants or Branch pages
- Verify currency displays correctly (should be USD, not UGX)
- Open browser console, no errors should appear

3. **Change Currency Test**:
- Go to Settings > General Settings
- Select a different currency
- Save changes
- Refresh dashboard
- Verify new currency is displayed everywhere

## Future Enhancements

Consider implementing:
1. Multi-currency support for different branches
2. Automatic exchange rate updates via API
3. Currency conversion helpers
4. Currency-based pricing rules
5. Per-merchant currency preferences
6. Real-time currency rates from external APIs
