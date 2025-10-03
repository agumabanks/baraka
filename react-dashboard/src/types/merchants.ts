export interface MerchantContact {
  name: string | null;
  email: string | null;
  phone: string | null;
}

export interface MerchantShopSummary {
  id: number;
  name: string;
  contact_no: string | null;
  address: string | null;
}

export interface MerchantMetrics {
  active_shipments: number;
  delivered_shipments: number;
  total_shipments: number;
  cod_open_balance: number;
  cod_collected: number;
  active_shops: number;
}

export interface MerchantListItem {
  id: number;
  business_name: string;
  current_balance: number;
  status: number | string | null;
  primary_contact: MerchantContact | null;
  metrics: MerchantMetrics;
  shops: MerchantShopSummary[];
}

export interface MerchantPaymentAccount {
  id: number;
  payment_method: string | null;
  bank_name: string | null;
  account_no: string | null;
  mobile_company: string | null;
  mobile_no: string | null;
}

export interface MerchantParcelSummary {
  id: number;
  tracking_id: string | null;
  status: number | string | null;
  cash_collection: number | null;
  cod_amount: number | null;
  delivery_charge: number | null;
  total_delivery_amount: number | null;
  created_at: string | null;
  merchant_shop: string | null;
}

export interface MerchantFinanceSummary {
  current_balance: number;
  cod_outstanding: number;
  cod_collected: number;
}

export interface MerchantDetail extends MerchantListItem {
  finance: MerchantFinanceSummary;
  payment_accounts: MerchantPaymentAccount[];
  recent_parcels: MerchantParcelSummary[];
}

export interface MerchantListResponse {
  items: MerchantListItem[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  filters: {
    statuses: Array<number | string>;
  };
}

export interface MerchantDetailResponse {
  merchant: MerchantDetail;
}

export interface MerchantListParams {
  page?: number;
  per_page?: number;
  status?: string | number;
  search?: string;
}
