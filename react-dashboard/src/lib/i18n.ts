/**
 * i18n Helper
 * Translates Laravel translation keys
 * Preserves __(key) pattern from Blade
 */

// Translation dictionary - mirrors Laravel lang files
const translations: Record<string, Record<string, string>> = {
  en: {
    // Dashboard keys
    'dashboard.total_parcel': 'Total Parcels',
    'dashboard.total_user': 'Total Users',
    'dashboard.total_merchant': 'Total Merchants',
    'dashboard.total_delivery_man': 'Total Delivery Personnel',
    'dashboard.total_hubs': 'Total Hubs',
    'dashboard.total_accounts': 'Total Accounts',
    'dashboard.total_customers': 'Total Customers',
    'dashboard.book_shipment': 'Book Shipment',
    'dashboard.total_partial_deliverd': 'Partial Delivered',
    'dashboard.total_deliverd': 'Delivered',
    'dashboard.delivery_man': 'Delivery Personnel',
    'dashboard.merchant': 'Merchants',
    'dashboard.statements': 'Statements',
    'dashboard.balance': 'Balance',
    'dashboard.courier': 'Courier',
    'dashboard.revenue': 'Revenue',
    'dashboard.trends': 'Trends',
    'dashboard.bulk_upload': 'Bulk Upload',
    'dashboard.view_all_parcels': 'View All Parcels',
    
    // Income/Expense
    'income.title': 'Income',
    'expense.title': 'Expense',
    
    // Levels
    'levels.filter': 'Filter',
    
    // Menus
    'menus.dashboard': 'Dashboard',
    
    // Hub
    'hub.title': 'Hubs',
  },
};

// Current locale (can be set dynamically)
let currentLocale = 'en';

/**
 * Translate a key
 * @param key Translation key (e.g., 'dashboard.total_parcel')
 * @param replacements Optional replacements object
 * @returns Translated string
 */
export function t(key: string, replacements?: Record<string, string | number>): string {
  const translation = translations[currentLocale]?.[key] || key;
  
  if (!replacements) {
    return translation;
  }
  
  // Replace :key patterns
  return Object.entries(replacements).reduce(
    (str, [key, value]) => str.replace(new RegExp(`:${key}`, 'g'), String(value)),
    translation
  );
}

/**
 * Set current locale
 * @param locale Locale code (e.g., 'en', 'es')
 */
export function setLocale(locale: string): void {
  currentLocale = locale;
}