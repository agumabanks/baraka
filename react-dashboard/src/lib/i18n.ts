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
  fr: {
    'dashboard.total_parcel': 'Total des colis',
    'dashboard.total_user': 'Total des utilisateurs',
    'dashboard.total_merchant': 'Total des marchands',
    'dashboard.total_delivery_man': 'Total des livreurs',
    'dashboard.total_hubs': 'Total des hubs',
    'dashboard.total_accounts': 'Total des comptes',
    'dashboard.total_customers': 'Total des clients',
    'dashboard.book_shipment': 'Programmer une expédition',
    'dashboard.total_partial_deliverd': 'Livraisons partielles',
    'dashboard.total_deliverd': 'Livraisons effectuées',
    'dashboard.delivery_man': 'Livreurs',
    'dashboard.merchant': 'Marchands',
    'dashboard.statements': 'Relevés',
    'dashboard.balance': 'Solde',
    'dashboard.courier': 'Coursier',
    'dashboard.revenue': 'Revenus',
    'dashboard.trends': 'Tendances',
    'dashboard.bulk_upload': 'Importation groupée',
    'dashboard.view_all_parcels': 'Voir tous les colis',
    'income.title': 'Revenus',
    'expense.title': 'Dépenses',
    'levels.filter': 'Filtrer',
    'menus.dashboard': 'Tableau de bord',
    'hub.title': 'Hubs',
  },
  sw: {
    'dashboard.total_parcel': 'Jumla ya vifurushi',
    'dashboard.total_user': 'Watumiaji jumla',
    'dashboard.total_merchant': 'Wafanyabiashara jumla',
    'dashboard.total_delivery_man': 'Wawasilishaji jumla',
    'dashboard.total_hubs': 'Vituo jumla',
    'dashboard.total_accounts': 'Akaunti jumla',
    'dashboard.total_customers': 'Wateja jumla',
    'dashboard.book_shipment': 'Panga usafirishaji',
    'dashboard.total_partial_deliverd': 'Uwasilishaji sehemu',
    'dashboard.total_deliverd': 'Uwasilishaji uliokamilika',
    'dashboard.delivery_man': 'Wawasilishaji',
    'dashboard.merchant': 'Wafanyabiashara',
    'dashboard.statements': 'Taarifa za kifedha',
    'dashboard.balance': 'Salio',
    'dashboard.courier': 'Msafirishaji',
    'dashboard.revenue': 'Mapato',
    'dashboard.trends': 'Mwelekeo',
    'dashboard.bulk_upload': 'Pakia kwa wingi',
    'dashboard.view_all_parcels': 'Ona vifurushi vyote',
    'income.title': 'Mapato',
    'expense.title': 'Matumizi',
    'levels.filter': 'Chuja',
    'menus.dashboard': 'Dashibodi',
    'hub.title': 'Vituo',
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
