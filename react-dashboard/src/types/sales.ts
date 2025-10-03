export interface SalesSelectOption {
  value: string;
  label: string;
}

export interface PaginationMeta {
  current_page: number;
  per_page: number;
  last_page: number;
  total: number;
}

export type SalesAccountStatus = 'active' | 'inactive';
export type SalesEngagementStatus = 'active' | 'at_risk' | 'dormant';

export interface SalesCustomer {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  hub: { id: number; name: string } | null;
  shipments: { this_month: number; total: number };
  status: {
    account: SalesAccountStatus;
    engagement: SalesEngagementStatus;
  };
  last_shipment_at: string | null;
  last_activity_human: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface SalesCustomerSummary {
  totals: {
    customers: number;
    active: number;
    at_risk: number;
    dormant: number;
  };
  shipments: {
    this_month: number;
    lifetime: number;
  };
  generated_at: string;
}

export interface SalesCustomerFilters {
  engagement_options: SalesSelectOption[];
  status_options: SalesSelectOption[];
  hub_options: SalesSelectOption[];
}

export interface SalesCustomerListResponse {
  items: SalesCustomer[];
  pagination: PaginationMeta;
  summary: SalesCustomerSummary;
  filters: SalesCustomerFilters;
}

export type QuotationStatus = 'draft' | 'sent' | 'accepted' | 'expired';

export interface SalesQuotation {
  id: number;
  reference: string;
  customer: { id: number; name: string; email: string | null } | null;
  service_type: string;
  destination_country: string | null;
  pieces: number;
  weight_kg: number;
  volume_cm3: number | null;
  dim_factor: number | null;
  base_charge: number;
  total_amount: number;
  currency: string;
  status: QuotationStatus;
  valid_until: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface SalesQuotationSummary {
  totals: {
    all: number;
    by_status: Partial<Record<QuotationStatus, number>>;
  };
  value: {
    total: number;
    average: number;
    currency: string;
  };
  latest_valid_until: string | null;
  generated_at: string;
}

export interface SalesQuotationFilters {
  status_options: SalesSelectOption[];
}

export interface SalesQuotationListResponse {
  items: SalesQuotation[];
  pagination: PaginationMeta;
  summary: SalesQuotationSummary;
  filters: SalesQuotationFilters;
}

export type ContractStatus = 'active' | 'suspended' | 'ended';

export interface SalesContract {
  id: number;
  name: string;
  customer: { id: number; name: string; email: string | null } | null;
  status: ContractStatus;
  start_date: string | null;
  end_date: string | null;
  duration_days: number | null;
  rate_card_id: number | null;
  notes: string | null;
  sla: Record<string, unknown>;
  created_at: string | null;
  updated_at: string | null;
}

export interface SalesContractSummary {
  totals: {
    all: number;
    by_status: Partial<Record<ContractStatus, number>>;
  };
  expiring_soon: number;
  average_duration_days: number;
  generated_at: string;
}

export interface SalesContractFilters {
  status_options: SalesSelectOption[];
}

export interface SalesContractListResponse {
  items: SalesContract[];
  pagination: PaginationMeta;
  summary: SalesContractSummary;
  filters: SalesContractFilters;
}

export type AddressBookType = 'shipper' | 'consignee' | 'payer';

export interface SalesAddressBookEntry {
  id: number;
  type: AddressBookType;
  name: string;
  phone: string;
  email: string | null;
  country: string;
  city: string;
  address_line: string;
  tax_id: string | null;
  customer: { id: number; name: string; email: string | null } | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface SalesAddressBookSummary {
  totals: {
    all: number;
    by_type: Partial<Record<AddressBookType, number>>;
  };
  generated_at: string;
}

export interface SalesAddressBookFilters {
  type_options: SalesSelectOption[];
}

export interface SalesAddressBookListResponse {
  items: SalesAddressBookEntry[];
  pagination: PaginationMeta;
  summary: SalesAddressBookSummary;
  filters: SalesAddressBookFilters;
}

export interface SalesShipmentSummary {
  total: number;
  total_value?: number;
  currency: string;
  by_status: Record<string, number>;
}

export interface SalesShipmentPreview {
  id: number;
  service_level: string | null;
  status: string;
  value: number | null;
  currency: string | null;
  created_at: string | null;
}

export interface SalesInvoicePreview {
  id: number;
  number: string | null;
  status: string;
  total_amount: number;
  currency: string;
  due_date: string | null;
  paid_at: string | null;
  created_at: string | null;
}

export interface SalesQuotationPreview {
  id: number;
  service_type: string;
  destination_country: string | null;
  total_amount: number;
  currency: string | null;
  status: string;
  valid_until: string | null;
  created_at: string | null;
}

export interface SalesContractPreview {
  id: number;
  name: string;
  status: string;
  start_date: string | null;
  end_date: string | null;
  notes: string | null;
}

export interface SalesCustomerDetail {
  customer: SalesCustomer;
  addresses: Array<{
    id: number;
    type: string;
    name: string;
    phone: string;
    email: string | null;
    country: string;
    city: string;
    address_line: string;
    tax_id: string | null;
    created_at: string | null;
  }>;
  shipments: {
    summary: SalesShipmentSummary;
    recent: SalesShipmentPreview[];
  };
  billing: {
    open_amount: number;
    paid_amount: number;
    overdue_count: number;
    currency: string;
    recent: SalesInvoicePreview[];
  };
  payments: {
    paid_invoices_total: number;
    open_invoices_total: number;
    currency: string;
    recent: SalesInvoicePreview[];
  };
  quotations: {
    total: number;
    accepted: number;
    recent: SalesQuotationPreview[];
  };
  contracts: {
    total: number;
    active: number;
    recent: SalesContractPreview[];
  };
  preferences: {
    preferred_hub: string | null;
    primary_contact: string | null;
    communication_channels: string[];
    default_address: Record<string, unknown> | null;
  };
  feedback: Record<string, number>;
  history: Array<{
    type: string;
    reference: string | number;
    status: string;
    timestamp: string | null;
  }>;
}
